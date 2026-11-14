<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateUsersTable extends AbstractMigration
{
    public function up(): void
    {
        $this->table('users')
            ->addColumn('full_name', 'string', ['limit' => 150])
            ->addColumn('username', 'string', ['limit' => 50])
            ->addColumn('email', 'string', ['limit' => 150])
            ->addColumn('password_hash', 'string', ['limit' => 255])
            ->addColumn('role_id', 'biginteger')
            ->addColumn('is_active', 'boolean', ['default' => true])
            ->addColumn('last_login_at', 'timestamp', ['null' => true, 'timezone' => true])
            ->addColumn('created_at', 'timestamp', ['null' => true, 'timezone' => true])
            ->addColumn('updated_at', 'timestamp', ['null' => true, 'timezone' => true])
            ->addForeignKey('role_id', 'roles', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
            ->addIndex('role_id')
            ->create();

        $this->execute('CREATE UNIQUE INDEX uq_users_username ON users (LOWER(username))');
        $this->execute('CREATE UNIQUE INDEX uq_users_email ON users (LOWER(email))');
    }

    public function down(): void
    {
        $this->table('users')->drop()->save();
    }
}
