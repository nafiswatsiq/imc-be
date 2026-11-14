<?php

declare(strict_types=1);

namespace App\Support;

use Psr\Http\Message\ResponseInterface as Response;

final class ApiResponse
{
    /**
     * @param array<string, mixed>|null $meta
     */
    public static function json(Response $response, mixed $data, int $status = 200, ?array $meta = null): Response
    {
        $payload = ['data' => $data];
        if ($meta !== null) {
            $payload['meta'] = $meta;
        }

        return self::write($response, $payload, $status);
    }

    /**
     * @param array<string, mixed>|null $details
     */
    public static function error(Response $response, int $status, string $code, string $message, ?array $details = null): Response
    {
        $error = ['code' => $code, 'message' => $message];
        if ($details !== null) {
            $error['details'] = $details;
        }

        return self::write($response, ['error' => $error], $status);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function write(Response $response, array $payload, int $status): Response
    {
        $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json');
    }
}
