<?php
declare(strict_types=1);

namespace IdentityBridge\Test\Support;

use IdentityBridge\Provider\ProviderInterface;
use IdentityBridge\ValueObject\RemoteIdentity;

final class TestProvider implements ProviderInterface
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
        return new RemoteIdentity(
            provider: 'test',
            providerUserId: 'provider-user-123',
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        return $this->config;
    }
}
