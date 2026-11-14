<?php

declare(strict_types=1);

namespace App\Support;

use App\Exceptions\TooManyRequestsException;

/**
 * Minimal file-backed login throttle keyed by IP + identifier.
 */
final class LoginThrottle
{
    /**
     * @param array{max_attempts: int, window: int, dir: string} $settings
     */
    public function __construct(private readonly array $settings)
    {
    }

    public function assertAllowed(string $ip, string $identifier): void
    {
        $state = $this->read($ip, $identifier);

        if ($state['count'] >= $this->settings['max_attempts']) {
            throw new TooManyRequestsException();
        }
    }

    public function hit(string $ip, string $identifier): void
    {
        $state = $this->read($ip, $identifier);
        $state['count']++;
        $state['first'] = $state['first'] ?: time();
        $this->write($ip, $identifier, $state);
    }

    public function clear(string $ip, string $identifier): void
    {
        $file = $this->file($ip, $identifier);
        if (is_file($file)) {
            @unlink($file);
        }
    }

    /**
     * @return array{count: int, first: int}
     */
    private function read(string $ip, string $identifier): array
    {
        $file = $this->file($ip, $identifier);
        if (!is_file($file)) {
            return ['count' => 0, 'first' => 0];
        }

        $state = json_decode((string) file_get_contents($file), true);
        if (!is_array($state) || time() - (int) ($state['first'] ?? 0) > $this->settings['window']) {
            return ['count' => 0, 'first' => 0];
        }

        return ['count' => (int) $state['count'], 'first' => (int) $state['first']];
    }

    /**
     * @param array{count: int, first: int} $state
     */
    private function write(string $ip, string $identifier, array $state): void
    {
        if (!is_dir($this->settings['dir'])) {
            @mkdir($this->settings['dir'], 0775, true);
        }
        file_put_contents($this->file($ip, $identifier), json_encode($state), LOCK_EX);
    }

    private function file(string $ip, string $identifier): string
    {
        return $this->settings['dir'] . '/' . hash('sha256', $ip . '|' . strtolower($identifier)) . '.json';
    }
}
