<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\ValidationException;
use App\Models\Role;
use App\Models\User;
use App\Services\PermissionService;
use App\Support\ApiResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class PermissionController extends Controller
{
    public function __construct(private readonly PermissionService $permissions)
    {
    }

    public function index(Request $request, Response $response): Response
    {
        return ApiResponse::json($response, $this->permissions->matrix());
    }

    public function showRole(Request $request, Response $response, array $args): Response
    {
        $role = Role::query()->findOrFail($this->id($args));

        return ApiResponse::json($response, [
            'role_id' => $role->id,
            'is_super' => $role->is_super,
            'permission_ids' => $this->permissions->rolePermissionIds($role),
        ]);
    }

    public function updateRole(Request $request, Response $response, array $args): Response
    {
        $role = Role::query()->findOrFail($this->id($args));
        $body = $this->input($request);

        if (!isset($body['permission_ids']) || !is_array($body['permission_ids']) || !$this->allInts($body['permission_ids'])) {
            throw new ValidationException(['permission_ids' => ['Permission ids must be an array of integers.']]);
        }

        $this->permissions->syncRolePermissions($role, $body['permission_ids']);

        return ApiResponse::json($response, [
            'role_id' => $role->id,
            'permission_ids' => $this->permissions->rolePermissionIds($role),
        ]);
    }

    public function showUser(Request $request, Response $response, array $args): Response
    {
        $user = User::query()->with('role')->findOrFail($this->id($args));

        return ApiResponse::json($response, $this->userPayload($user));
    }

    public function updateUser(Request $request, Response $response, array $args): Response
    {
        $user = User::query()->with('role')->findOrFail($this->id($args));
        $body = $this->input($request);

        if (!isset($body['overrides']) || !is_array($body['overrides']) || !array_is_list($body['overrides'])) {
            throw new ValidationException(['overrides' => ['Overrides must be a list of {permission_id, effect}.']]);
        }

        $map = [];
        foreach ($body['overrides'] as $item) {
            if (!is_array($item)
                || !isset($item['permission_id']) || !is_int($item['permission_id'])
                || !isset($item['effect']) || !in_array($item['effect'], ['allow', 'deny'], true)) {
                throw new ValidationException(['overrides' => ['Each override needs an integer permission_id and effect of allow or deny.']]);
            }
            $map[$item['permission_id']] = $item['effect'];
        }

        $this->permissions->syncUserOverrides($user, $map);

        return ApiResponse::json($response, $this->userPayload($user->fresh('role')));
    }

    /**
     * @return array<string, mixed>
     */
    private function userPayload(User $user): array
    {
        $overrides = [];
        foreach ($this->permissions->userOverrides($user) as $permissionId => $effect) {
            $overrides[] = ['permission_id' => $permissionId, 'effect' => $effect];
        }

        $effective = $this->permissions->effectivePermissions($user);

        return [
            'user_id' => $user->id,
            'role' => ['id' => $user->role->id, 'name' => $user->role->name, 'is_super' => $user->role->is_super],
            'role_permission_ids' => $this->permissions->rolePermissionIds($user->role),
            'overrides' => $overrides,
            'effective' => $effective,
        ];
    }

    /**
     * @param array<mixed> $values
     */
    private function allInts(array $values): bool
    {
        foreach ($values as $v) {
            if (!is_int($v)) {
                return false;
            }
        }

        return true;
    }
}
