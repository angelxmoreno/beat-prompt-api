<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api;

use App\Test\Support\Auth\TestIdentityProvider;
use App\Test\Support\Auth\TestIdentityResolver;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use IdentityBridge\Provider\ProviderInterface;
use IdentityBridge\Resolver\LocalUserResolverInterface;

class IdentityControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * @var array<string, mixed>
     */
    protected array $appPluginsToLoad = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockService(
            ProviderInterface::class,
            fn(): ProviderInterface => new TestIdentityProvider([
                'provider' => 'test-provider',
            ]),
        );
        $this->mockService(
            LocalUserResolverInterface::class,
            fn(): LocalUserResolverInterface => new TestIdentityResolver(),
        );
    }

    public function testMeRequiresAuthentication(): void
    {
        $this->get('/api/identity/me');

        $this->assertResponseCode(401);
        $this->assertContentType('application/json');
        $this->assertResponseContains('"message":"Missing bearer token."');
    }

    public function testMeReturnsAuthenticatedIdentitySummary(): void
    {
        $this->configRequest([
            'headers' => [
                'Authorization' => 'Bearer valid-test-token',
            ],
        ]);

        $this->get('/api/identity/me');

        $this->assertResponseOk();
        $this->assertContentType('application/json');

        /** @var array<string, mixed> $payload */
        $payload = json_decode($this->_getBodyAsString(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('test-provider', $payload['data']['remoteIdentity']['provider']);
        $this->assertSame('provider-user-123', $payload['data']['remoteIdentity']['providerUserId']);
        $this->assertSame('demo@example.com', $payload['data']['remoteIdentity']['email']);
        $this->assertSame(42, $payload['data']['user']['id']);
    }

    public function testMeRejectsInvalidTokens(): void
    {
        $this->configRequest([
            'headers' => [
                'Authorization' => 'Bearer invalid-test-token',
            ],
        ]);

        $this->get('/api/identity/me');

        $this->assertResponseCode(401);
        $this->assertContentType('application/json');
        $this->assertResponseContains('"message":"Token verification failed."');
    }
}
