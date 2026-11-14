<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateUserPermissionsTable extends AbstractMigration
{
    public function up(): void
    {
        $this->table('user_permissions', ['id' => false, 'primary_key' => ['user_id', 'permission_id']])
            ->addColumn('user_id', 'biginteger')
            ->addColumn('permission_id', 'biginteger')
            ->addColumn('effect', 'string', ['limit' => 5])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('permission_id', 'permissions', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->create();

        $this->execute("ALTER TABLE user_permissions ADD CONSTRAINT chk_user_permissions_effect CHECK (effect IN ('allow','deny'))");
    }

    public function down(): void
    {
        $this->table('user_permissions')->drop()->save();
    }
}
