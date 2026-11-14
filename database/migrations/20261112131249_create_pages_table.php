<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreatePagesTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('pages')
            ->addColumn('name', 'string', ['limit' => 100])
            ->addColumn('path', 'string', ['limit' => 150])
            ->addColumn('slug', 'string', ['limit' => 50])
            ->addColumn('description', 'text', ['null' => true])
            ->addColumn('sort_order', 'integer', ['default' => 0])
            ->addColumn('is_active', 'boolean', ['default' => true])
            ->addColumn('created_at', 'timestamp', ['null' => true, 'timezone' => true])
            ->addColumn('updated_at', 'timestamp', ['null' => true, 'timezone' => true])
            ->addIndex('path', ['unique' => true, 'name' => 'uq_pages_path'])
            ->addIndex('slug', ['unique' => true, 'name' => 'uq_pages_slug'])
            ->addIndex('sort_order')
            ->create();
    }
}
