<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class PermissionSeeder extends AbstractSeed
{
    public const ACTIONS = ['view', 'create', 'update', 'delete'];

    public function getDependencies(): array
    {
        return ['PageSeeder'];
    }

    public function run(): void
    {
        $rows = [];
        foreach ($this->fetchAll('SELECT id, slug FROM pages ORDER BY id') as $page) {
            foreach (self::ACTIONS as $action) {
                $rows[] = [
                    'page_id' => $page['id'],
                    'action' => $action,
                    'name' => $page['slug'] . '.' . $action,
                ];
            }
        }

        $this->table('permissions')->insert($rows)->saveData();
    }
}
