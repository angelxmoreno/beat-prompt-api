<?php
declare(strict_types=1);

namespace App\Test\Support\Auth;

use ArrayAccess;
use ArrayObject;
use IdentityBridge\Resolver\LocalUserResolverInterface;
use IdentityBridge\ValueObject\RemoteIdentity;

class TestIdentityResolver implements LocalUserResolverInterface
{
    /**
     * @param array<string, mixed> $config Test resolver config.
     */
    public function __construct(private readonly array $config = [])
    {
    }

    /**
     * @param \IdentityBridge\ValueObject\RemoteIdentity $identity The normalized identity.
     * @return \ArrayAccess
     */
    public function resolve(RemoteIdentity $identity): ArrayAccess
    {
        return new ArrayObject([
            'id' => $this->config['id'] ?? 42,
            'name' => $identity->displayName,
            'email' => $identity->email,
            'avatar_url' => $identity->avatarUrl,
        ]);
    }
}
