<?php
declare(strict_types=1);

namespace IdentityBridge\ValueObject;

use ArrayAccess;

/**
 * Bundles the normalized remote identity with the resolved local user.
 */
readonly class AuthenticatedRequestIdentity
{
    /**
     * @param \IdentityBridge\ValueObject\RemoteIdentity $remoteIdentity The normalized remote identity.
     * @param \ArrayAccess $user The resolved local user object.
     */
    public function __construct(
        public RemoteIdentity $remoteIdentity,
        public ArrayAccess $user,
    ) {
    }
}
