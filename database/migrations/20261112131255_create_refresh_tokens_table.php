<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateRefreshTokensTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('refresh_tokens')
            ->addColumn('user_id', 'biginteger')
            ->addColumn('token_hash', 'string', ['limit' => 255])
            ->addColumn('expires_at', 'timestamp', ['timezone' => true])
            ->addColumn('revoked_at', 'timestamp', ['null' => true, 'timezone' => true])
            ->addColumn('created_at', 'timestamp', ['null' => true, 'timezone' => true])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addIndex('token_hash', ['unique' => true, 'name' => 'uq_refresh_tokens_hash'])
            ->addIndex('user_id')
            ->create();
    }
}
