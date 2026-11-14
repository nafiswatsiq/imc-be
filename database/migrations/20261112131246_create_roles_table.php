<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateRolesTable extends AbstractMigration
{
    public function up(): void
    {
        $this->table('roles')
            ->addColumn('name', 'string', ['limit' => 100])
            ->addColumn('description', 'text', ['null' => true])
            ->addColumn('is_super', 'boolean', ['default' => false])
            ->addColumn('is_active', 'boolean', ['default' => true])
            ->addColumn('deleted_at', 'timestamp', ['null' => true, 'timezone' => true])
            ->addColumn('created_at', 'timestamp', ['null' => true, 'timezone' => true])
            ->addColumn('updated_at', 'timestamp', ['null' => true, 'timezone' => true])
            ->create();

        $this->execute('CREATE UNIQUE INDEX uq_roles_name ON roles (LOWER(name)) WHERE deleted_at IS NULL');
    }

    public function down(): void
    {
        $this->execute('DROP INDEX IF EXISTS uq_roles_name');
        $this->table('roles')->drop()->save();
    }
}
