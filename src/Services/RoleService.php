<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ConflictException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\ValidationException;
use App\Models\Role;
use App\Models\User;

class RoleService
{
    public function __construct(private readonly PermissionService $permissions)
    {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data, User $actor): Role
    {
        $this->assertCanSetSuper($data, $actor);
        $this->assertNameUnique($data['name']);

        return Role::query()->create($data)->refresh();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(Role $role, array $data, User $actor): Role
    {
        $this->assertCanSetSuper($data, $actor);

        if (isset($data['name'])) {
            $this->assertNameUnique($data['name'], $role->id);
        }

        if ($role->is_super) {
            $demoting = (isset($data['is_super']) && $data['is_super'] === false)
                || (isset($data['is_active']) && $data['is_active'] === false);
            if ($demoting && $this->isLastActiveSuperRole($role)) {
                throw new ConflictException('The last active super role cannot be demoted or deactivated.');
            }
        }

        $role->fill($data)->save();
        $this->permissions->flush();

        return $role->refresh();
    }

    public function delete(Role $role): void
    {
        if ($role->is_super) {
            throw new ConflictException('A super role cannot be deleted.');
        }

        if ($role->users()->exists()) {
            throw new ConflictException('This role is still assigned to users and cannot be deleted.');
        }

        $role->delete();
        $this->permissions->flush();
    }

    /**
     * @param array<string, mixed> $data
     */
    private function assertCanSetSuper(array $data, User $actor): void
    {
        if (($data['is_super'] ?? false) === true && !$actor->role->is_super) {
            throw new ForbiddenException('Only a super admin can create or mark a super role.');
        }
    }

    private function assertNameUnique(string $name, ?int $ignoreId = null): void
    {
        $query = Role::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($name)]);
        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->exists()) {
            throw new ValidationException(['name' => ['Role name is already used.']]);
        }
    }

    private function isLastActiveSuperRole(Role $role): bool
    {
        return !Role::query()
            ->where('is_super', true)
            ->where('is_active', true)
            ->where('id', '!=', $role->id)
            ->exists();
    }
}
