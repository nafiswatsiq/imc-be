<?php

declare(strict_types=1);

namespace App\Handlers;

use App\Exceptions\ApiException;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Throwable;

final class HttpErrorHandler
{
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,

        private readonly bool $debug,
        private readonly ?string $logFile = null,
    ) {
    }

    public function __invoke(Request $request, Throwable $e, bool $displayErrorDetails, bool $logErrors, bool $logErrorDetails): Response
    {
        $response = $this->responseFactory->createResponse();

        if ($e instanceof ApiException) {
            return ApiResponse::error($response, $e->getStatus(), $e->getErrorCode(), $e->getMessage(), $e->getDetails());
        }

        if ($e instanceof ModelNotFoundException || $e instanceof HttpNotFoundException) {
            return ApiResponse::error($response, 404, 'NOT_FOUND', 'Resource not found.');
        }

        if ($e instanceof HttpMethodNotAllowedException) {
            return ApiResponse::error($response, 405, 'METHOD_NOT_ALLOWED', 'Method not allowed.');
        }

        $this->log($e);

        $message = $this->debug ? $e->getMessage() : 'An unexpected error occurred.';

        return ApiResponse::error($response, 500, 'SERVER_ERROR', $message);
    }

    private function log(Throwable $e): void
    {
        $line = sprintf("[%s] %s: %s in %s:%d\n%s\n", date('c'), $e::class, $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString());

        if ($this->logFile !== null) {
            @file_put_contents($this->logFile, $line, FILE_APPEND | LOCK_EX);
        } else {
            error_log($line);
        }
    }
}
