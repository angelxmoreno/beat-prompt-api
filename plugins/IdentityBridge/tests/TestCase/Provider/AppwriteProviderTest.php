<?php
declare(strict_types=1);

namespace IdentityBridge\Test\TestCase\Provider;

use Appwrite\AppwriteException;
use Appwrite\Models\Preferences;
use Appwrite\Models\User;
use IdentityBridge\Provider\AppwriteProvider;
use IdentityBridge\ValueObject\RemoteIdentity;
use PHPUnit\Framework\TestCase;

class AppwriteProviderTest extends TestCase
{
    public function testVerifyNormalizesAppwriteUserIntoRemoteIdentity(): void
    {
        $user = $this->makeUser(
            id: 'user_123',
            name: 'Demo User',
            email: 'demo@example.com',
            emailVerification: true,
            labels: ['admin', 'beta'],
        );
        $provider = new class (['endpoint' => 'https://example.test']) extends AppwriteProvider {
            public ?string $capturedJwt = null;

            public function __construct(private readonly array $testConfig = [])
            {
                parent::__construct($testConfig);
            }

            protected function fetchUser(string $jwt): User
            {
                $this->capturedJwt = $jwt;

                return new User(
                    id: 'user_123',
                    createdAt: '2026-01-01T00:00:00+00:00',
                    updatedAt: '2026-01-01T00:00:00+00:00',
                    name: 'Demo User',
                    registration: '2026-01-01T00:00:00+00:00',
                    status: true,
                    labels: ['admin', 'beta'],
                    passwordUpdate: '2026-01-01T00:00:00+00:00',
                    email: 'demo@example.com',
                    phone: '+15555550123',
                    emailVerification: true,
                    phoneVerification: false,
                    mfa: false,
                    prefs: new Preferences(),
                    targets: [],
                    accessedAt: '2026-01-01T00:00:00+00:00',
                );
            }
        };

        $identity = $provider->verify('valid.jwt.token');

        $this->assertInstanceOf(RemoteIdentity::class, $identity);
        $this->assertSame('valid.jwt.token', $provider->capturedJwt);
        $this->assertSame(AppwriteProvider::PROVIDER_NAME, $identity->provider);
        $this->assertSame($user->id, $identity->providerUserId);
        $this->assertSame($user->email, $identity->email);
        $this->assertSame($user->emailVerification, $identity->emailVerified);
        $this->assertSame($user->name, $identity->displayName);
        $this->assertSame(['labels' => $user->labels], $identity->claims);
    }

    public function testVerifyPropagatesAppwriteFailures(): void
    {
        $provider = new class extends AppwriteProvider {
            protected function fetchUser(string $jwt): User
            {
                throw new AppwriteException('Token verification failed.');
            }
        };

        $this->expectException(AppwriteException::class);
        $this->expectExceptionMessage('Token verification failed.');

        $provider->verify('invalid.jwt.token');
    }

    public function testConstructorStoresProviderConfigForClientBuilding(): void
    {
        $provider = new class ([
            'endpoint' => 'https://appwrite.example.test/v1',
            'projectId' => 'project_123',
            'isDev' => true,
        ]) extends AppwriteProvider {
            public function getSnapshot(): array
            {
                return [
                    'endpoint' => $this->endpoint,
                    'projectId' => $this->projectId,
                    'isDev' => $this->isDev,
                ];
            }
        };

        $this->assertSame([
            'endpoint' => 'https://appwrite.example.test/v1',
            'projectId' => 'project_123',
            'isDev' => true,
        ], $provider->getSnapshot());
    }

    /**
     * @param list<string> $labels
     * @return \Appwrite\Models\User
     */
    private function makeUser(
        string $id,
        string $name,
        string $email,
        bool $emailVerification,
        array $labels,
    ): User {
        return new User(
            id: $id,
            createdAt: '2026-01-01T00:00:00+00:00',
            updatedAt: '2026-01-01T00:00:00+00:00',
            name: $name,
            registration: '2026-01-01T00:00:00+00:00',
            status: true,
            labels: $labels,
            passwordUpdate: '2026-01-01T00:00:00+00:00',
            email: $email,
            phone: '+15555550123',
            emailVerification: $emailVerification,
            phoneVerification: false,
            mfa: false,
            prefs: new Preferences(),
            targets: [],
            accessedAt: '2026-01-01T00:00:00+00:00',
        );
    }
}
