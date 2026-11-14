<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Support\ApiResponse;
use App\Validation\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validator as v;

final class AuthController extends Controller
{
    public function __construct(private readonly AuthService $auth)
    {
    }

    public function login(Request $request, Response $response): Response
    {
        $data = Validator::validate($this->input($request), [
            'identifier' => v::stringType()->notEmpty()->length(1, 150),
            'password' => v::stringType()->notEmpty()->length(1, 255),
        ]);

        $ip = $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown';

        return ApiResponse::json($response, $this->auth->login($data['identifier'], $data['password'], $ip));
    }

    public function refresh(Request $request, Response $response): Response
    {
        $data = Validator::validate($this->input($request), [
            'refresh_token' => v::stringType()->notEmpty()->length(1, 255),
        ]);

        return ApiResponse::json($response, $this->auth->refresh($data['refresh_token']));
    }

    public function logout(Request $request, Response $response): Response
    {
        $body = $this->input($request);
        $token = isset($body['refresh_token']) && is_string($body['refresh_token']) ? $body['refresh_token'] : null;

        $this->auth->logout($this->actor($request), (int) $request->getAttribute('session_id'), $token);

        return ApiResponse::json($response, ['message' => 'Logged out.']);
    }

    public function me(Request $request, Response $response): Response
    {
        return ApiResponse::json($response, $this->auth->me($this->actor($request)));
    }
}
