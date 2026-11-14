<?php

declare(strict_types=1);

namespace App\Exceptions;

class ValidationException extends ApiException
{
    /**
     * @param array<string, list<string>> $details
     */
    public function __construct(array $details, string $message = 'The given data was invalid.')
    {
        parent::__construct($message, 422, 'VALIDATION_ERROR', $details);
    }
}
