<?php
declare(strict_types=1);

namespace IdentityBridge\Provider;

use IdentityBridge\ValueObject\RemoteIdentity;

/**
 * Contract for verifying remote bearer tokens.
 */
interface ProviderInterface
{
    /**
     * Verifies a bearer token and returns normalized remote identity data.
     *
     * @param string $jwt The provider-issued bearer token.
     * @return \IdentityBridge\ValueObject\RemoteIdentity
     */
    public function verify(string $jwt): RemoteIdentity;
}
