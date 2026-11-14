<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class UserSeeder extends AbstractSeed
{
    public function getDependencies(): array
    {
        return ['RoleSeeder'];
    }

    public function run(): void
    {
        $roles = array_column($this->fetchAll('SELECT id, name FROM roles'), 'id', 'name');
        $now = date('Y-m-d H:i:sP');


        $users = [
            ['Super Admin',        'superadmin', 'superadmin@example.com', 'password',   'Super Admin', true],
            ['Manager One',        'manager1',   'manager1@example.com',   'password', 'Manager',     true],
            ['Staff One',          'staff1',     'staff1@example.com',     'password',   'Staff',       true],
            ['Viewer One',         'viewer1',    'viewer1@example.com',    'password',  'Viewer',      true],
            ['Inactive User',      'inactive1',  'inactive1@example.com',  'password', 'Staff',      false],
            ['Staff Allow Override', 'staff_allow', 'staff_allow@example.com', 'password', 'Staff',     true],
            ['Manager Deny Override', 'manager_deny', 'manager_deny@example.com', 'password', 'Manager', true],
        ];

        $rows = [];
        foreach ($users as [$fullName, $username, $email, $password, $role, $active]) {
            $rows[] = [
                'full_name' => $fullName,
                'username' => $username,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_BCRYPT),
                'role_id' => $roles[$role],
                'is_active' => $active,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $this->table('users')->insert($rows)->saveData();
    }
}
