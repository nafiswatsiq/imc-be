<?php

declare(strict_types=1);

namespace App\Exceptions;

class BadRequestException extends ApiException
{
    public function __construct(string $message = 'Malformed request.')
    {
        parent::__construct($message, 400, 'BAD_REQUEST');
    }
}
