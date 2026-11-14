<?php

declare(strict_types=1);

$env = static fn (string $key, mixed $default = null): mixed => $_ENV[$key] ?? (getenv($key) !== false ? getenv($key) : $default);

return [
    'app' => [
        'env' => $env('APP_ENV', 'production'),
        'debug' => filter_var($env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN),
        'log_file' => dirname(__DIR__) . '/logs/app.log',
    ],
    'db' => [
        'driver' => $env('DB_DRIVER', 'pgsql'),
        'host' => $env('DB_HOST', '127.0.0.1'),
        'port' => $env('DB_PORT', '5432'),
        'database' => $env('DB_NAME', 'imc_uam'),
        'username' => $env('DB_USER', 'postgres'),
        'password' => $env('DB_PASS', ''),
        'charset' => 'utf8',
        'prefix' => '',
        'schema' => 'public',
        'timezone' => 'UTC',
    ],
    'jwt' => [
        'secret' => $env('JWT_SECRET', ''),
        'issuer' => $env('JWT_ISSUER', 'imc-uam'),
        'access_ttl' => (int) $env('JWT_ACCESS_TTL', 900), // 15 minutes
        'refresh_ttl' => (int) $env('JWT_REFRESH_TTL', 604800), // 7 days
    ],
    'cors' => [
        'origins' => array_filter(array_map('trim', explode(',', (string) $env('CORS_ORIGIN', 'http://localhost:4200')))),
    ],
    'throttle' => [
        'max_attempts' => (int) $env('LOGIN_MAX_ATTEMPTS', 5),
        'window' => (int) $env('LOGIN_WINDOW_SECONDS', 900),
        'dir' => dirname(__DIR__) . '/var/throttle',
    ],
];
