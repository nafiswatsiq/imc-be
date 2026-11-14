<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

if (file_exists(__DIR__ . '/.env')) {
    Dotenv\Dotenv::createImmutable(__DIR__)->safeLoad();
}

$env = static fn (string $k, string $d = '') => $_ENV[$k] ?? getenv($k) ?: $d;

return [
    'paths' => [
        'migrations' => '%%PHINX_CONFIG_DIR%%/database/migrations',
        'seeds'      => '%%PHINX_CONFIG_DIR%%/database/seeds',
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment'     => 'default',
        'default' => [
            'adapter' => $env('DB_DRIVER', 'pgsql'),
            'host'    => $env('DB_HOST', '127.0.0.1'),
            'name'    => $env('DB_NAME', 'imc_uam'),
            'user'    => $env('DB_USER', 'postgres'),
            'pass'    => $env('DB_PASS', ''),
            'port'    => (int) $env('DB_PORT', '5432'),
            'charset' => 'utf8',
        ],
    ],
    'version_order' => 'creation',
];
