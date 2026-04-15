<?php
declare(strict_types=1);

namespace IdentityBridge\Resolver;

use ArrayAccess;
use IdentityBridge\ValueObject\RemoteIdentity;

/**
 * Host-app contract for resolving the local user from remote identity data.
 */
interface LocalUserResolverInterface
{
    /**
     * Finds, creates, or updates the local user for a verified remote identity.
     *
     * @param \IdentityBridge\ValueObject\RemoteIdentity $identity The normalized identity.
     * @return \ArrayAccess
     */
    public function resolve(RemoteIdentity $identity): ArrayAccess;
}
