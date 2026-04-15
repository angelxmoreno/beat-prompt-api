<?php
declare(strict_types=1);

namespace IdentityBridge\Test\Support;

use ArrayAccess;
use ArrayObject;
use IdentityBridge\Resolver\LocalUserResolverInterface;
use IdentityBridge\ValueObject\RemoteIdentity;

final class TestResolver implements LocalUserResolverInterface
{
    /**
     * @param \IdentityBridge\ValueObject\RemoteIdentity $identity The normalized identity.
     * @return \ArrayAccess
     */
    public function resolve(RemoteIdentity $identity): ArrayAccess
    {
        return new ArrayObject(['id' => 1]);
    }
}
