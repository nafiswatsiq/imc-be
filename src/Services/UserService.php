<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ConflictException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\ValidationException;
use App\Models\Role;
use App\Models\User;
use App\Support\PasswordHasher;

class UserService
{
    public function __construct(
        private readonly PasswordHasher $hasher,
        private readonly PermissionService $permissions,
    ) {
    }

    /**
     * @param array<string, mixed> $data validated input including a plain `password`
     */
    public function create(array $data, User $actor): User
    {
        $this->assertUnique($data);
        $this->assertRoleAssignable($data['role_id'], $actor);

        $password = $data['password'];
        unset($data['password']);

        $user = new User();
        $user->fill($data);
        $user->password_hash = $this->hasher->hash($password);
        $user->save();

        return $user->load('role');
    }

    /**
     * @param array<string, mixed> $data validated (partial) input, `password` optional
     */
    public function update(User $user, array $data, User $actor): User
    {
        $this->assertUnique($data, $user->id);

        if (isset($data['role_id']) && (int) $data['role_id'] !== $user->role_id) {
            $this->assertRoleAssignable($data['role_id'], $actor);
        }

        $demoting = $this->wouldRemoveSuperAdmin($user, $data);
        if ($demoting && $this->isLastActiveSuperAdmin($user)) {
            throw new ConflictException('The last active super admin cannot be deactivated or moved to another role.');
        }

        if (isset($data['password']) && $data['password'] !== '') {
            $user->password_hash = $this->hasher->hash($data['password']);
        }
        unset($data['password']);

        $user->fill($data)->save();
        $this->permissions->flush();

        return $user->load('role');
    }

    public function delete(User $user, User $actor): void
    {
        if ($user->id === $actor->id) {
            throw new ConflictException('You cannot delete your own account.');
        }

        if ($this->isLastActiveSuperAdmin($user)) {
            throw new ConflictException('The last active super admin cannot be deleted.');
        }

        $user->delete();
        $this->permissions->flush();
    }

    /**
     * @param array<string, mixed> $data
     */
    private function wouldRemoveSuperAdmin(User $user, array $data): bool
    {
        if (isset($data['is_active']) && $data['is_active'] === false) {
            return true;
        }

        if (isset($data['role_id']) && (int) $data['role_id'] !== $user->role_id) {
            return true;
        }

        return false;
    }

    private function isLastActiveSuperAdmin(User $user): bool
    {
        $user->loadMissing('role');
        if (!$user->is_active || !$user->role->is_super) {
            return false;
        }

        return !User::query()
            ->where('id', '!=', $user->id)
            ->where('is_active', true)
            ->whereHas('role', fn ($q) => $q->where('is_super', true))
            ->exists();
    }

    private function assertRoleAssignable(int|string $roleId, User $actor): void
    {
        $role = Role::query()->find($roleId);
        if ($role === null) {
            throw new ValidationException(['role_id' => ['Selected role does not exist.']]);
        }

        if ($role->is_super && !$actor->role->is_super) {
            throw new ForbiddenException('Only a super admin can assign a super role.');
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function assertUnique(array $data, ?int $ignoreId = null): void
    {
        $errors = [];
        foreach (['username' => 'Username', 'email' => 'Email'] as $field => $label) {
            if (!isset($data[$field])) {
                continue;
            }

            $query = User::query()->whereRaw("LOWER({$field}) = ?", [mb_strtolower($data[$field])]);
            if ($ignoreId !== null) {
                $query->where('id', '!=', $ignoreId);
            }
            if ($query->exists()) {
                $errors[$field] = [$label . ' is already used.'];
            }
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }
}
