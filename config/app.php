<?php

declare(strict_types=1);

use App\Handlers\HttpErrorHandler;
use App\Middleware\CorsMiddleware;
use Slim\App;
use Slim\Factory\AppFactory;

/**
 * Builds the Slim app. Settings are read from the environment (see config/settings.php).
 */
return static function (): App {
    $settings = require __DIR__ . '/settings.php';

    (require __DIR__ . '/database.php')($settings['db']);

    $container = (require __DIR__ . '/container.php')($settings);

    AppFactory::setContainer($container);
    $app = AppFactory::create();

    (require __DIR__ . '/routes.php')($app, $container);

    $app->addRoutingMiddleware();

    $errorMiddleware = $app->addErrorMiddleware($settings['app']['debug'], true, true);
    $errorMiddleware->setDefaultErrorHandler($container->get(HttpErrorHandler::class));

    // Added last so it is outermost: error responses and preflights carry CORS headers too.
    $app->add($container->get(CorsMiddleware::class));

    return $app;
};
