<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Exceptions\UnauthenticatedException;
use App\Models\RefreshToken;
use App\Models\User;
use App\Support\JwtService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;

final class JwtAuthMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly JwtService $jwt)
    {
    }

    public function process(Request $request, Handler $handler): Response
    {
        if (!preg_match('/^Bearer\s+(\S+)$/i', $request->getHeaderLine('Authorization'), $m)) {
            throw new UnauthenticatedException();
        }

        $claims = $this->jwt->decode($m[1]);

        $sessionActive = RefreshToken::query()
            ->where('id', $claims['session_id'])
            ->where('user_id', $claims['user_id'])
            ->whereNull('revoked_at')
            ->exists();
        if (!$sessionActive) {
            throw new UnauthenticatedException('Invalid or expired token.');
        }

        $user = User::query()->with('role')->find($claims['user_id']);
        if ($user === null || !$user->is_active || $user->role === null) {
            throw new UnauthenticatedException('Invalid or expired token.');
        }

        return $handler->handle(
                    $request->withAttribute('user', $user)->withAttribute('session_id', $claims['session_id'])
                );
    }
}
