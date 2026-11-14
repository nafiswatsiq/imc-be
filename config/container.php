<?php

declare(strict_types=1);

use App\Handlers\HttpErrorHandler;
use App\Middleware\CorsMiddleware;
use App\Support\JwtService;
use App\Support\LoginThrottle;
use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Slim\Psr7\Factory\ResponseFactory;

return static function (array $settings): ContainerInterface {
    $builder = new ContainerBuilder();
    $builder->addDefinitions([
        'settings' => $settings,
        ResponseFactoryInterface::class => static fn () => new ResponseFactory(),
        JwtService::class => static fn (ContainerInterface $c) => new JwtService($c->get('settings')['jwt']),
        LoginThrottle::class => static fn (ContainerInterface $c) => new LoginThrottle($c->get('settings')['throttle']),
        CorsMiddleware::class => static fn (ContainerInterface $c) => new CorsMiddleware(
            $c->get('settings')['cors']['origins'],
            $c->get(ResponseFactoryInterface::class),
        ),
        HttpErrorHandler::class => static fn (ContainerInterface $c) => new HttpErrorHandler(
            $c->get(ResponseFactoryInterface::class),
            $c->get('settings')['app']['debug'],
            $c->get('settings')['app']['log_file'],
        ),
    ]);

    return $builder->build();
};
