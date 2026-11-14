<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\BadRequestException;
use App\Models\User;
use Psr\Http\Message\ServerRequestInterface as Request;

abstract class Controller
{
    /**
     * @return array<string, mixed>
     */
    protected function input(Request $request): array
    {
        $raw = (string) $request->getBody();
        if (trim($raw) === '') {
            return [];
        }

        $data = json_decode($raw, true);
        if (!is_array($data) || array_is_list($data) && $data !== []) {
            throw new BadRequestException('Request body must be a valid JSON object.');
        }

        return $data;
    }

    protected function actor(Request $request): User
    {
        /** @var User $user */
        $user = $request->getAttribute('user');

        return $user;
    }

    protected function id(array $args): int
    {
        return (int) ($args['id'] ?? 0);
    }
}
