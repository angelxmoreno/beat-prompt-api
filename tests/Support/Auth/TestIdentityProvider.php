<?php
declare(strict_types=1);

namespace App\Test\Support\Auth;

use IdentityBridge\Exception\AuthenticationException;
use IdentityBridge\Provider\ProviderInterface;
use IdentityBridge\ValueObject\RemoteIdentity;

class TestIdentityProvider implements ProviderInterface
{
    /**
     * @param array<string, mixed> $config Test provider config.
     */
    public function __construct(private readonly array $config = [])
    {
    }

    /**
     * @param string $jwt The provider-issued bearer token.
     * @return \IdentityBridge\ValueObject\RemoteIdentity
     */
    public function verify(string $jwt): RemoteIdentity
    {
        if ($jwt !== 'valid-test-token') {
            throw new AuthenticationException('Token verification failed.');
        }

        return new RemoteIdentity(
            provider: (string)($this->config['provider'] ?? 'test'),
            providerUserId: 'provider-user-123',
            email: 'demo@example.com',
            emailVerified: true,
            displayName: 'Demo User',
            avatarUrl: 'https://example.com/avatar.png',
            claims: ['scope' => 'api'],
        );
    }
}
