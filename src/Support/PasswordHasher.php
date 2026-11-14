<?php

declare(strict_types=1);

namespace App\Support;

final class PasswordHasher
{
    private ?string $dummyHash = null;

    public function hash(string $plain): string
    {
        return password_hash($plain, PASSWORD_BCRYPT);
    }

    public function verify(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }

    /**
     * Verify against a throwaway hash so unknown users cost the same time as known ones.
     */
    public function verifyDummy(string $plain): void
    {
        $this->dummyHash ??= $this->hash('not-a-real-password');
        password_verify($plain, $this->dummyHash);
    }
}
