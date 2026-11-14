<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateRolePermissionsTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('role_permissions', ['id' => false, 'primary_key' => ['role_id', 'permission_id']])
            ->addColumn('role_id', 'biginteger')
            ->addColumn('permission_id', 'biginteger')
            ->addForeignKey('role_id', 'roles', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('permission_id', 'permissions', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->create();
    }
}
