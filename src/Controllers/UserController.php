<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use App\Services\UserService;
use App\Support\ApiResponse;
use App\Support\Paginator;
use App\Validation\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validator as v;

final class UserController extends Controller
{
    public function __construct(private readonly UserService $users)
    {
    }

    public function index(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $query = User::query()->with('role:id,name,is_super')->orderBy('id');

        if (!empty($params['search']) && is_string($params['search'])) {
            $like = Paginator::likeTerm(mb_strtolower($params['search']));
            $query->where(function ($q) use ($like): void {
                $q->whereRaw('LOWER(full_name) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(username) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(email) LIKE ?', [$like]);
            });
        }
        if (!empty($params['role_id']) && ctype_digit((string) $params['role_id'])) {
            $query->where('role_id', (int) $params['role_id']);
        }
        if (isset($params['is_active']) && in_array($params['is_active'], ['true', 'false', '1', '0'], true)) {
            $query->where('is_active', in_array($params['is_active'], ['true', '1'], true));
        }

        [$items, $meta] = Paginator::paginate($query, $request);

        return ApiResponse::json($response, $items, 200, $meta);
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $user = User::query()->with('role:id,name,is_super')->findOrFail($this->id($args));

        return ApiResponse::json($response, $user);
    }

    public function store(Request $request, Response $response): Response
    {
        $data = Validator::validate($this->input($request), [
            'full_name' => v::stringType()->notEmpty()->length(1, 150),
            'username' => v::stringType()->regex('/^[A-Za-z0-9._-]+$/')->length(3, 50),
            'email' => v::stringType()->email()->length(1, 150),
            'password' => v::stringType()->length(8, 255),
            'role_id' => v::intVal()->positive(),
            'is_active' => v::optional(v::boolType()),
        ]);
        $data['is_active'] = $data['is_active'] ?? true;
        $data['role_id'] = (int) $data['role_id'];

        $user = $this->users->create($data, $this->actor($request));

        return ApiResponse::json($response, $user, 201);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $user = User::query()->findOrFail($this->id($args));

        $data = Validator::validate($this->input($request), [
            'full_name' => v::stringType()->notEmpty()->length(1, 150),
            'username' => v::stringType()->regex('/^[A-Za-z0-9._-]+$/')->length(3, 50),
            'email' => v::stringType()->email()->length(1, 150),
            'password' => v::optional(v::stringType()->length(8, 255)),
            'role_id' => v::intVal()->positive(),
            'is_active' => v::boolType(),
        ], partial: true);
        if (isset($data['role_id'])) {
            $data['role_id'] = (int) $data['role_id'];
        }

        return ApiResponse::json($response, $this->users->update($user, $data, $this->actor($request)));
    }

    public function destroy(Request $request, Response $response, array $args): Response
    {
        $user = User::query()->with('role')->findOrFail($this->id($args));
        $this->users->delete($user, $this->actor($request));

        return $response->withStatus(204);
    }
}
