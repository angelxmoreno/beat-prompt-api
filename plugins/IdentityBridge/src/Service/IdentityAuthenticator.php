<?php
declare(strict_types=1);

namespace IdentityBridge\Service;

use IdentityBridge\Exception\AuthenticationException;
use IdentityBridge\Provider\ProviderInterface;
use IdentityBridge\Resolver\LocalUserResolverInterface;
use IdentityBridge\ValueObject\AuthenticatedRequestIdentity;

/**
 * Shared service that turns a bearer token into request auth state.
 */
final class IdentityAuthenticator
{
    /**
     * @param \IdentityBridge\Provider\ProviderInterface $provider The configured identity provider.
     * @param \IdentityBridge\Resolver\LocalUserResolverInterface $localUserResolver The host-app user resolver.
     */
    public function __construct(
        private readonly ProviderInterface $provider,
        private readonly LocalUserResolverInterface $localUserResolver,
    ) {
    }

    /**
     * Verifies the bearer token and resolves the local app user.
     *
     * @param string $jwt The provider-issued bearer token.
     * @return \IdentityBridge\ValueObject\AuthenticatedRequestIdentity
     */
    public function authenticate(string $jwt): AuthenticatedRequestIdentity
    {
        $jwt = trim($jwt);
        if ($jwt === '') {
            throw new AuthenticationException('Missing bearer token.');
        }

        $remoteIdentity = $this->provider->verify($jwt);
        $user = $this->localUserResolver->resolve($remoteIdentity);

        return new AuthenticatedRequestIdentity($remoteIdentity, $user);
    }
}
