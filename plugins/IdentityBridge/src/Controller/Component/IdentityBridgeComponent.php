<?php
declare(strict_types=1);

namespace IdentityBridge\Controller\Component;

use ArrayAccess;
use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;
use IdentityBridge\Exception\AuthenticationException;
use IdentityBridge\ValueObject\AuthenticatedRequestIdentity;
use IdentityBridge\ValueObject\RemoteIdentity;

/**
 * Controller-facing accessor for IdentityBridge request auth state.
 */
final class IdentityBridgeComponent extends Component
{
    /**
     * @param \Cake\Controller\ComponentRegistry<\Cake\Controller\Controller> $registry The component registry.
     * @param array<string, mixed> $config Component configuration.
     */
    public function __construct(ComponentRegistry $registry, array $config = [])
    {
        parent::__construct($registry, $config);
    }

    /**
     * Returns the authenticated request identity attached by the middleware.
     *
     * @return \IdentityBridge\ValueObject\AuthenticatedRequestIdentity|null
     */
    public function getAuthenticatedRequestIdentity(): ?AuthenticatedRequestIdentity
    {
        $identity = $this->getController()->getRequest()->getAttribute('identityBridge.identity');

        return $identity instanceof AuthenticatedRequestIdentity ? $identity : null;
    }

    /**
     * Returns the normalized remote identity attached by the middleware.
     *
     * @return \IdentityBridge\ValueObject\RemoteIdentity|null
     */
    public function getRemoteIdentity(): ?RemoteIdentity
    {
        return $this->getAuthenticatedRequestIdentity()?->remoteIdentity;
    }

    /**
     * Returns the resolved local user attached by the middleware.
     *
     * @return \ArrayAccess|null
     */
    public function getUser(): ?ArrayAccess
    {
        return $this->getAuthenticatedRequestIdentity()?->user;
    }

    /**
     * Indicates whether a resolved local user is available on the request.
     *
     * @return bool
     */
    public function hasUser(): bool
    {
        return $this->getUser() !== null;
    }

    /**
     * Returns the resolved local user or throws when the request is unauthenticated.
     *
     * @return \ArrayAccess
     */
    public function requireUser(): ArrayAccess
    {
        $user = $this->getUser();
        if ($user === null) {
            throw new AuthenticationException('Authenticated user is required.');
        }

        return $user;
    }
}
