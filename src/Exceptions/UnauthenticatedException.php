<?php

declare(strict_types=1);

namespace App\Exceptions;

class UnauthenticatedException extends ApiException
{
    public function __construct(string $message = 'Authentication is required.')
    {
        parent::__construct($message, 401, 'UNAUTHENTICATED');
    }
}
