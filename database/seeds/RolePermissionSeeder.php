<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class RolePermissionSeeder extends AbstractSeed
{
    public function getDependencies(): array
    {
        return ['RoleSeeder', 'PermissionSeeder'];
    }

    public function run(): void
    {
        $roles = array_column($this->fetchAll('SELECT id, name FROM roles'), 'id', 'name');
        $perms = array_column($this->fetchAll('SELECT id, name FROM permissions'), 'id', 'name');

        $grants = [
            'Manager' => [
                'dashboard.view', 'users.view', 'users.create', 'users.update', 'users.delete',
                'roles.view', 'roles.create', 'roles.update', 'roles.delete',
                'pages.view', 'pages.create', 'pages.update', 'pages.delete',
            ],
            'Staff' => ['dashboard.view'],
            'Viewer' => ['dashboard.view'],
        ];

        $rows = [];
        foreach ($grants as $roleName => $names) {
            foreach ($names as $name) {
                $rows[] = ['role_id' => $roles[$roleName], 'permission_id' => $perms[$name]];
            }
        }

        $this->table('role_permissions')->insert($rows)->saveData();
    }
}
