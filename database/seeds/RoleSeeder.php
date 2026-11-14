<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class RoleSeeder extends AbstractSeed
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:sP');
        $rows = [
            ['name' => 'Super Admin', 'description' => 'Full access, bypasses all permission checks', 'is_super' => true],
            ['name' => 'Manager',     'description' => 'Manages users, roles and pages',            'is_super' => false],
            ['name' => 'Staff',       'description' => 'Day-to-day operational access',              'is_super' => false],
            ['name' => 'Viewer',      'description' => 'Read-only access',                           'is_super' => false],
        ];

        foreach ($rows as &$row) {
            $row['is_active'] = true;
            $row['created_at'] = $now;
            $row['updated_at'] = $now;
        }

        $this->table('roles')->insert($rows)->saveData();
    }
}
