<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Exceptions\ForbiddenException;
use App\Exceptions\UnauthenticatedException;
use App\Models\User;
use App\Services\PermissionService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;

final class PermissionMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly PermissionService $permissions,
        private readonly string $permission,
    ) {
    }

    public function process(Request $request, Handler $handler): Response
    {
        $user = $request->getAttribute('user');
        if (!$user instanceof User) {
            throw new UnauthenticatedException();
        }

        if (!$this->permissions->userCan($user, $this->permission)) {
            throw new ForbiddenException();
        }

        return $handler->handle($request);
    }
}
