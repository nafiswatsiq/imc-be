<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\RoleController;
use App\Controllers\PageController;
use App\Controllers\PermissionController;
use App\Controllers\UserController;
use App\Middleware\JwtAuthMiddleware;
use App\Middleware\PermissionMiddleware;
use App\Services\PermissionService;
use Psr\Container\ContainerInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return static function (App $app, ContainerInterface $container): void {
    $can = static fn (string $permission): PermissionMiddleware => new PermissionMiddleware(
        $container->get(PermissionService::class),
        $permission,
    );

    $app->group('/api/v1', function (RouteCollectorProxy $api) use ($can): void {
        // Public
        $api->post('/auth/login', AuthController::class . ':login');
        $api->post('/auth/refresh', AuthController::class . ':refresh');

        // Authenticated
        $api->group('', function (RouteCollectorProxy $auth) use ($can): void {
            $auth->post('/auth/logout', AuthController::class . ':logout');
            $auth->get('/auth/me', AuthController::class . ':me');

            $auth->get('/users', UserController::class . ':index')->add($can('users.view'));
            $auth->post('/users', UserController::class . ':store')->add($can('users.create'));
            $auth->get('/users/{id:[0-9]+}', UserController::class . ':show')->add($can('users.view'));
            $auth->put('/users/{id:[0-9]+}', UserController::class . ':update')->add($can('users.update'));
            $auth->delete('/users/{id:[0-9]+}', UserController::class . ':destroy')->add($can('users.delete'));

            $auth->get('/roles', RoleController::class . ':index')->add($can('roles.view'));
            $auth->post('/roles', RoleController::class . ':store')->add($can('roles.create'));
            $auth->get('/roles/{id:[0-9]+}', RoleController::class . ':show')->add($can('roles.view'));
            $auth->put('/roles/{id:[0-9]+}', RoleController::class . ':update')->add($can('roles.update'));
            $auth->delete('/roles/{id:[0-9]+}', RoleController::class . ':destroy')->add($can('roles.delete'));

            $auth->get('/pages', PageController::class . ':index')->add($can('pages.view'));
            $auth->post('/pages', PageController::class . ':store')->add($can('pages.create'));
            $auth->get('/pages/{id:[0-9]+}', PageController::class . ':show')->add($can('pages.view'));
            $auth->put('/pages/{id:[0-9]+}', PageController::class . ':update')->add($can('pages.update'));
            $auth->delete('/pages/{id:[0-9]+}', PageController::class . ':destroy')->add($can('pages.delete'));

            $auth->get('/permissions', PermissionController::class . ':index')->add($can('permissions.view'));
            $auth->get('/roles/{id:[0-9]+}/permissions', PermissionController::class . ':showRole')->add($can('permissions.view'));
            $auth->put('/roles/{id:[0-9]+}/permissions', PermissionController::class . ':updateRole')->add($can('permissions.update'));
            $auth->get('/users/{id:[0-9]+}/permissions', PermissionController::class . ':showUser')->add($can('permissions.view'));
            $auth->put('/users/{id:[0-9]+}/permissions', PermissionController::class . ':updateUser')->add($can('permissions.update'));
        })->add(JwtAuthMiddleware::class);
    });
};
