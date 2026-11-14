<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

return static function (array $dbSettings): Capsule {
    $config = $dbSettings;

    if ($config['driver'] === 'sqlite') {
        $config = [
            'driver' => 'sqlite',
            'database' => $config['database'],
            'prefix' => '',
            'foreign_key_constraints' => true,
        ];
    }

    $capsule = new Capsule();
    $capsule->addConnection($config);
    $capsule->setAsGlobal();
    $capsule->bootEloquent();

    return $capsule;
};
