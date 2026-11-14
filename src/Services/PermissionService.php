<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ConflictException;
use App\Exceptions\ValidationException;
use App\Models\Role;
use App\Models\Page;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\Capsule\Manager as DB;

class PermissionService
{
    /** @var array<int, array{is_super: bool, names: list<string>}> */
    private array $cache = [];

    public function userCan(User $user, string $permission): bool
    {
        $perms = $this->effectivePermissions($user);

        return $perms['is_super'] || in_array($permission, $perms['names'], true);
    }

    /**
     * @return array{is_super: bool, names: list<string>}
     */
    public function effectivePermissions(User $user): array
    {
        if (isset($this->cache[$user->id])) {
            return $this->cache[$user->id];
        }

        $role = $user->role;

        if ($role === null || !$role->is_active && !$role->is_super) {
            $fromRole = collect();
        } elseif ($role->is_super) {
            return $this->cache[$user->id] = ['is_super' => true, 'names' => []];
        } else {
            $fromRole = $role->permissions()->pluck('permissions.name');
        }

        $overrides = $user->permissions()->get();
        $allow = $overrides->filter(fn ($p) => $p->pivot->effect === 'allow')->pluck('name');
        $deny = $overrides->filter(fn ($p) => $p->pivot->effect === 'deny')->pluck('name');

        $names = $fromRole->merge($allow)->unique()->diff($deny)->values()->all();

        return $this->cache[$user->id] = ['is_super' => false, 'names' => $names];
    }

    /**
     * Active pages the user may view, ordered by sort_order.
     *
     * @return list<array{id: int, name: string, path: string, slug: string, sort_order: int}>
     */
    public function menuFor(User $user): array
    {
        $perms = $this->effectivePermissions($user);

        return Page::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->filter(fn (Page $p) => $perms['is_super'] || in_array($p->slug . '.view', $perms['names'], true))
            ->map(fn (Page $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'path' => $p->path,
                'slug' => $p->slug,
                'sort_order' => $p->sort_order,
            ])
            ->values()
            ->all();
    }

    /**
     * Pages with their permissions, used to render the matrix.
     *
     * @return list<array<string, mixed>>
     */
    public function matrix(): array
    {
        return Page::query()
            ->with(['permissions' => fn ($q) => $q->orderBy('id')])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (Page $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'path' => $p->path,
                'slug' => $p->slug,
                'is_active' => $p->is_active,
                'permissions' => $p->permissions->map(fn (Permission $perm) => [
                    'id' => $perm->id,
                    'action' => $perm->action,
                    'name' => $perm->name,
                ])->values()->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<int>
     */
    public function rolePermissionIds(Role $role): array
    {
        return $role->permissions()->pluck('permissions.id')->map(fn ($id) => (int) $id)->values()->all();
    }

    /**
     * @param list<int> $permissionIds
     */
    public function syncRolePermissions(Role $role, array $permissionIds): void
    {
        if ($role->is_super) {
            throw new ConflictException('The super role has every permission implicitly and cannot be edited.');
        }

        $ids = $this->existingPermissionIds($permissionIds, 'permission_ids');
        $role->permissions()->sync($ids);
        $this->flush();
    }

    /**
     * @return array<int, string> permission_id => allow|deny
     */
    public function userOverrides(User $user): array
    {
        $out = [];
        foreach ($user->permissions()->get() as $perm) {
            $out[$perm->id] = $perm->pivot->effect;
        }

        return $out;
    }

    /**
     * Replaces the user's overrides. Permissions absent from $overrides inherit from the role.
     *
     * @param array<int, string> $overrides permission_id => allow|deny
     */
    public function syncUserOverrides(User $user, array $overrides): void
    {
        $this->existingPermissionIds(array_keys($overrides), 'overrides');

        $payload = [];
        foreach ($overrides as $permissionId => $effect) {
            $payload[(int) $permissionId] = ['effect' => $effect];
        }

        DB::connection()->transaction(function () use ($user, $payload): void {
            $user->permissions()->sync($payload);
        });
        $this->flush();
    }

    public function flush(): void
    {
        $this->cache = [];
    }

    /**
     * @param array<int|string> $ids
     * @return list<int>
     */
    private function existingPermissionIds(array $ids, string $field): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if ($ids === []) {
            return [];
        }

        $found = Permission::query()->whereIn('id', $ids)->pluck('id')->map(fn ($i) => (int) $i)->all();
        $missing = array_diff($ids, $found);
        if ($missing !== []) {
            throw new ValidationException([$field => ['Unknown permission id(s): ' . implode(', ', $missing) . '.']]);
        }

        return $ids;
    }
}
