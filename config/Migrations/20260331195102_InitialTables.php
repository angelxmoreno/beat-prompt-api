<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class InitialTables extends AbstractMigration
{
    /**
     * Creates the minimal template schema.
     *
     * @return void
     */
    public function change(): void
    {
        $this->table('users')
            ->addColumn('appwrite_id', 'string', ['limit' => 128])
            ->addColumn('name', 'string', ['limit' => 255])
            ->addColumn('email', 'string', ['limit' => 255])
            ->addColumn('avatar_url', 'string', ['limit' => 512, 'null' => true])
            ->addTimestamps()
            ->addIndex(['appwrite_id'], ['unique' => true])
            ->addIndex(['email'], ['unique' => true])
            ->create();
    }
}
