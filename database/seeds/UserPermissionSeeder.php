<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class UserPermissionSeeder extends AbstractSeed
{
    public function getDependencies(): array
    {
        return ['UserSeeder', 'PermissionSeeder'];
    }

    public function run(): void
    {
        $users = array_column($this->fetchAll('SELECT id, username FROM users'), 'id', 'username');
        $perms = array_column($this->fetchAll('SELECT id, name FROM permissions'), 'id', 'name');

        $rows = [
            // Staff gets an extra page
            ['user_id' => $users['staff_allow'], 'permission_id' => $perms['users.view'], 'effect' => 'allow'],
            // Manager loses a page granted by the role
            ['user_id' => $users['manager_deny'], 'permission_id' => $perms['pages.view'], 'effect' => 'deny'],
            ['user_id' => $users['manager_deny'], 'permission_id' => $perms['roles.view'], 'effect' => 'deny'],
        ];

        $this->table('user_permissions')->insert($rows)->saveData();
    }
}
