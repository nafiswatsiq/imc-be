<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class PageSeeder extends AbstractSeed
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:sP');
        $pages = [
            ['Dashboard',   '/dashboard',   'dashboard',   'Landing page'],
            ['Users',       '/users',       'users',       'User management'],
            ['Roles',      '/roles',      'roles',      'Role management'],
            ['Pages',       '/pages',       'pages',       'Page management'],
            ['Permissions', '/permissions', 'permissions', 'Permission matrix'],
        ];

        $rows = [];
        foreach ($pages as $i => [$name, $path, $slug, $desc]) {
            $rows[] = [
                'name' => $name,
                'path' => $path,
                'slug' => $slug,
                'description' => $desc,
                'sort_order' => ($i + 1) * 10,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $this->table('pages')->insert($rows)->saveData();
    }
}
