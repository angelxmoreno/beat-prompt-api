<?php
declare(strict_types=1);

namespace IdentityBridge\Test\TestCase\ValueObject;

use IdentityBridge\ValueObject\RemoteIdentity;
use PHPUnit\Framework\TestCase;

class RemoteIdentityTest extends TestCase
{
    public function testItStoresNormalizedIdentityData(): void
    {
        $claims = [
            'sub' => 'provider-user-123',
            'role' => 'member',
        ];

        $identity = new RemoteIdentity(
            provider: 'clerk',
            providerUserId: 'provider-user-123',
            email: 'demo@example.com',
            emailVerified: true,
            displayName: 'Demo User',
            avatarUrl: 'https://example.com/avatar.png',
            claims: $claims,
        );

        $this->assertSame('clerk', $identity->provider);
        $this->assertSame('provider-user-123', $identity->providerUserId);
        $this->assertSame('demo@example.com', $identity->email);
        $this->assertTrue($identity->emailVerified);
        $this->assertSame('Demo User', $identity->displayName);
        $this->assertSame('https://example.com/avatar.png', $identity->avatarUrl);
        $this->assertSame($claims, $identity->claims);
    }

    public function testItAllowsMissingOptionalProfileFields(): void
    {
        $identity = new RemoteIdentity(
            provider: 'firebase',
            providerUserId: 'firebase-user-456',
        );

        $this->assertSame('firebase', $identity->provider);
        $this->assertSame('firebase-user-456', $identity->providerUserId);
        $this->assertNull($identity->email);
        $this->assertFalse($identity->emailVerified);
        $this->assertNull($identity->displayName);
        $this->assertNull($identity->avatarUrl);
        $this->assertSame([], $identity->claims);
    }

    public function testItSerializesToApiSafeJsonShape(): void
    {
        $identity = new RemoteIdentity(
            provider: 'appwrite',
            providerUserId: 'user_789',
            email: 'demo@example.com',
            emailVerified: true,
            displayName: 'Demo User',
            avatarUrl: 'https://example.com/avatar.png',
            claims: ['sub' => 'user_789'],
        );

        $this->assertSame([
            'provider' => 'appwrite',
            'providerUserId' => 'user_789',
            'email' => 'demo@example.com',
            'emailVerified' => true,
            'displayName' => 'Demo User',
            'avatarUrl' => 'https://example.com/avatar.png',
        ], $identity->jsonSerialize());
    }
}
