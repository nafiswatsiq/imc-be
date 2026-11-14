<?php

declare(strict_types=1);

namespace App\Support;

use App\Exceptions\UnauthenticatedException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Throwable;

final class JwtService
{
    private const ALGO = 'HS256';

    /**
     * @param array{secret: string, issuer: string, access_ttl: int, refresh_ttl: int} $settings
     */
    public function __construct(private readonly array $settings)
    {
    }

    public function accessTtl(): int
    {
        return $this->settings['access_ttl'];
    }

    public function refreshTtl(): int
    {
        return $this->settings['refresh_ttl'];
    }

    /**
     * @param int $sessionId refresh_tokens.id this access token is bound to; revoking it kills the token
     */
    public function issueAccessToken(int $userId, int $sessionId): string
    {
        $now = time();

        return JWT::encode([
            'iss' => $this->settings['issuer'],
            'sub' => (string) $userId,
            'sid' => $sessionId,
            'iat' => $now,
            'exp' => $now + $this->settings['access_ttl'],
        ], $this->settings['secret'], self::ALGO);
    }

    /**
     * @return array{user_id: int, session_id: int}
     */
    public function decode(string $jwt): array
    {
        try {
            $claims = JWT::decode($jwt, new Key($this->settings['secret'], self::ALGO));
        } catch (Throwable) {
            throw new UnauthenticatedException('Invalid or expired token.');
        }

        if (($claims->iss ?? null) !== $this->settings['issuer'] || !isset($claims->sub) || !ctype_digit((string) $claims->sub) || !isset($claims->sid) || !is_int($claims->sid)) {
            throw new UnauthenticatedException('Invalid or expired token.');
        }

        return ['user_id' => (int) $claims->sub, 'session_id' => $claims->sid];
    }
}
