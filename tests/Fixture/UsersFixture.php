<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * UsersFixture
 */
class UsersFixture extends TestFixture
{
    /**
     * Init method
     *
     * @return void
     */
    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'appwrite_id' => 'appwrite-user-1',
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
                'avatar_url' => 'https://example.com/avatars/jane.png',
                'created' => '2026-03-31 05:06:16',
                'modified' => '2026-03-31 05:06:16',
            ],
            [
                'id' => 2,
                'appwrite_id' => 'appwrite-user-2',
                'name' => 'John Roe',
                'email' => 'john@example.com',
                'avatar_url' => 'https://example.com/avatars/john.png',
                'created' => '2026-03-31 05:06:16',
                'modified' => '2026-03-31 05:06:16',
            ],
        ];
        parent::init();
    }
}
