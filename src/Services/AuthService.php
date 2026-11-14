<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\UnauthenticatedException;
use App\Models\RefreshToken;
use App\Models\User;
use App\Support\JwtService;
use App\Support\LoginThrottle;
use App\Support\PasswordHasher;
use Illuminate\Database\Capsule\Manager as DB;
use Carbon\Carbon;

class AuthService
{
    public function __construct(
        private readonly JwtService $jwt,
        private readonly PasswordHasher $hasher,
        private readonly PermissionService $permissions,
        private readonly LoginThrottle $throttle,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function login(string $identifier, string $password, string $ip): array
    {
        $this->throttle->assertAllowed($ip, $identifier);

        $needle = mb_strtolower(trim($identifier));
        $user = User::query()
            ->with('role')
            ->where(function ($q) use ($needle): void {
                $q->whereRaw('LOWER(username) = ?', [$needle])->orWhereRaw('LOWER(email) = ?', [$needle]);
            })
            ->first();

        if ($user === null) {
            $this->hasher->verifyDummy($password);
            $this->throttle->hit($ip, $identifier);
            throw new UnauthenticatedException('Invalid credentials.');
        }

        if (!$this->hasher->verify($password, $user->password_hash)) {
            $this->throttle->hit($ip, $identifier);
            throw new UnauthenticatedException('Invalid credentials.');
        }

        // Only reveal the inactive state once the password has been proven.
        if (!$user->is_active) {
            throw new UnauthenticatedException('Your account is inactive. Please contact an administrator.');
        }

        $this->throttle->clear($ip, $identifier);
        $user->forceFill(['last_login_at' => Carbon::now()])->save();

        return $this->issueTokens($user);
    }

    /**
     * Rotates the refresh token: the presented one is revoked and a new pair is issued.
     *
     * @return array<string, mixed>
     */
    public function refresh(string $refreshToken): array
    {
        $record = RefreshToken::query()->where('token_hash', $this->hashToken($refreshToken))->first();

        if ($record === null || $record->revoked_at !== null || $record->expires_at->isPast()) {
            throw new UnauthenticatedException('Invalid or expired refresh token.');
        }

        $user = User::query()->with('role')->find($record->user_id);
        if ($user === null || !$user->is_active) {
            throw new UnauthenticatedException('Invalid or expired refresh token.');
        }

        return DB::connection()->transaction(function () use ($record, $user): array {
            $record->forceFill(['revoked_at' => Carbon::now()])->save();

            return $this->issueTokens($user);
        });
    }

    public function logout(User $user, int $sessionId, ?string $refreshToken): void
    {
        $query = RefreshToken::query()->where('user_id', $user->id)->whereNull('revoked_at');
        $query->where(function ($q) use ($sessionId, $refreshToken): void {
            $q->where('id', $sessionId);
            if ($refreshToken !== null && $refreshToken !== '') {
                $q->orWhere('token_hash', $this->hashToken($refreshToken));
            }
        });

        $query->update(['revoked_at' => Carbon::now()]);
    }

    /**
     * @return array<string, mixed>
     */
    public function me(User $user): array
    {
        $user->loadMissing('role');
        $perms = $this->permissions->effectivePermissions($user);

        return [
            'user' => [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'username' => $user->username,
                'email' => $user->email,
                'is_active' => $user->is_active,
                'role' => [
                    'id' => $user->role->id,
                    'name' => $user->role->name,
                    'is_super' => $user->role->is_super,
                ],
            ],
            'permissions' => $perms['is_super']
                ? ['is_super' => true, 'names' => \App\Models\Permission::query()->orderBy('name')->pluck('name')->all()]
                : ['is_super' => false, 'names' => $perms['names']],
            'menu' => $this->permissions->menuFor($user),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function issueTokens(User $user): array
    {
        $plain = bin2hex(random_bytes(32));

        $session = RefreshToken::query()->create([
            'user_id' => $user->id,
            'token_hash' => $this->hashToken($plain),
            'expires_at' => Carbon::now()->addSeconds($this->jwt->refreshTtl()),
        ]);

        return [
            'access_token' => $this->jwt->issueAccessToken($user->id, $session->id),
            'token_type' => 'Bearer',
            'expires_in' => $this->jwt->accessTtl(),
            'refresh_token' => $plain,
        ];
    }

    private function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }
}
