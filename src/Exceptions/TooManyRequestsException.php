<?php

declare(strict_types=1);

namespace App\Exceptions;

class TooManyRequestsException extends ApiException
{
    public function __construct(string $message = 'Too many attempts. Please try again later.')
    {
        parent::__construct($message, 429, 'TOO_MANY_REQUESTS');
    }
}
