<?php
declare(strict_types=1);

namespace IdentityBridge\Test\TestCase\ValueObject;

use ArrayObject;
use IdentityBridge\ValueObject\AuthenticatedRequestIdentity;
use IdentityBridge\ValueObject\RemoteIdentity;
use PHPUnit\Framework\TestCase;

class AuthenticatedRequestIdentityTest extends TestCase
{
    public function testItStoresRemoteIdentityAndResolvedUser(): void
    {
        $remoteIdentity = new RemoteIdentity(
            provider: 'supabase',
            providerUserId: 'provider-user-123',
            email: 'demo@example.com',
        );
        $user = new ArrayObject([
            'id' => 99,
            'email' => 'demo@example.com',
        ]);

        $authenticatedIdentity = new AuthenticatedRequestIdentity($remoteIdentity, $user);

        $this->assertSame($remoteIdentity, $authenticatedIdentity->remoteIdentity);
        $this->assertSame($user, $authenticatedIdentity->user);
    }
}
