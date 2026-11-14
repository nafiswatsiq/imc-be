<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Role;
use App\Services\RoleService;
use App\Support\ApiResponse;
use App\Support\Paginator;
use App\Validation\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validator as v;

final class RoleController extends Controller
{
    public function __construct(private readonly RoleService $roles)
    {
    }

    public function index(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $query = Role::query()->withCount('users')->orderBy('id');

        if (!empty($params['search']) && is_string($params['search'])) {
            $query->whereRaw('LOWER(name) LIKE ?', [Paginator::likeTerm(mb_strtolower($params['search']))]);
        }
        if (isset($params['is_active']) && in_array($params['is_active'], ['true', 'false', '1', '0'], true)) {
            $query->where('is_active', in_array($params['is_active'], ['true', '1'], true));
        }

        [$items, $meta] = Paginator::paginate($query, $request);

        return ApiResponse::json($response, $items, 200, $meta);
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        return ApiResponse::json($response, Role::query()->withCount('users')->findOrFail($this->id($args)));
    }

    public function store(Request $request, Response $response): Response
    {
        $data = Validator::validate($this->input($request), $this->rules(), nullable: ['description']);
        $data['is_super'] = $data['is_super'] ?? false;
        $data['is_active'] = $data['is_active'] ?? true;

        $role = $this->roles->create($data, $this->actor($request));

        return ApiResponse::json($response, $role, 201);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $role = Role::query()->findOrFail($this->id($args));
        $data = Validator::validate($this->input($request), $this->rules(), partial: true, nullable: ['description']);

        return ApiResponse::json($response, $this->roles->update($role, $data, $this->actor($request)));
    }

    public function destroy(Request $request, Response $response, array $args): Response
    {
        $this->roles->delete(Role::query()->findOrFail($this->id($args)));

        return $response->withStatus(204);
    }

    /**
     * @return array<string, \Respect\Validation\Validatable>
     */
    private function rules(): array
    {
        return [
            'name' => v::stringType()->notEmpty()->length(1, 100),
            'description' => v::optional(v::stringType()->length(0, 1000)),
            'is_super' => v::optional(v::boolType()),
            'is_active' => v::optional(v::boolType()),
        ];
    }
}
