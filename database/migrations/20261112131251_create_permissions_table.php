<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreatePermissionsTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('permissions')
            ->addColumn('page_id', 'biginteger')
            ->addColumn('action', 'string', ['limit' => 20])
            ->addColumn('name', 'string', ['limit' => 100])
            ->addForeignKey('page_id', 'pages', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addIndex('name', ['unique' => true, 'name' => 'uq_permissions_name'])
            ->addIndex(['page_id', 'action'], ['unique' => true, 'name' => 'uq_permissions_page_action'])
            ->create();
    }
}
