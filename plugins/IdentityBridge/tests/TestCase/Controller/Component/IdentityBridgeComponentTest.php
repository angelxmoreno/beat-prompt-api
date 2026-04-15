<?php
declare(strict_types=1);

namespace IdentityBridge\Test\TestCase\Controller\Component;

use ArrayObject;
use Cake\Controller\Controller;
use Cake\Http\ServerRequest;
use IdentityBridge\Controller\Component\IdentityBridgeComponent;
use IdentityBridge\Exception\AuthenticationException;
use IdentityBridge\ValueObject\AuthenticatedRequestIdentity;
use IdentityBridge\ValueObject\RemoteIdentity;
use PHPUnit\Framework\TestCase;

class IdentityBridgeComponentTest extends TestCase
{
    public function testGetAuthenticatedRequestIdentityReturnsBundledAuthState(): void
    {
        $authenticatedIdentity = new AuthenticatedRequestIdentity(
            new RemoteIdentity(
                provider: 'clerk',
                providerUserId: 'user_123',
            ),
            new ArrayObject(['id' => 42]),
        );
        $component = $this->makeComponent([
            'identityBridge.identity' => $authenticatedIdentity,
        ]);

        $this->assertSame($authenticatedIdentity, $component->getAuthenticatedRequestIdentity());
    }

    public function testGetRemoteIdentityReturnsNormalizedIdentity(): void
    {
        $remoteIdentity = new RemoteIdentity(
            provider: 'clerk',
            providerUserId: 'user_123',
        );
        $authenticatedIdentity = new AuthenticatedRequestIdentity(
            $remoteIdentity,
            new ArrayObject(['id' => 42]),
        );
        $component = $this->makeComponent([
            'identityBridge.identity' => $authenticatedIdentity,
        ]);

        $this->assertSame($remoteIdentity, $component->getRemoteIdentity());
    }

    public function testGetUserReturnsResolvedLocalUser(): void
    {
        $user = new ArrayObject(['id' => 42]);
        $authenticatedIdentity = new AuthenticatedRequestIdentity(
            new RemoteIdentity(
                provider: 'clerk',
                providerUserId: 'user_123',
            ),
            $user,
        );
        $component = $this->makeComponent([
            'identityBridge.identity' => $authenticatedIdentity,
        ]);

        $this->assertSame($user, $component->getUser());
    }

    public function testHasUserReturnsTrueWhenResolvedUserExists(): void
    {
        $component = $this->makeComponent([
            'identityBridge.identity' => new AuthenticatedRequestIdentity(
                new RemoteIdentity(
                    provider: 'clerk',
                    providerUserId: 'user_123',
                ),
                new ArrayObject(['id' => 42]),
            ),
        ]);

        $this->assertTrue($component->hasUser());
    }

    public function testHasUserReturnsFalseWhenResolvedUserIsMissing(): void
    {
        $component = $this->makeComponent();

        $this->assertFalse($component->hasUser());
    }

    public function testRequireUserReturnsResolvedUser(): void
    {
        $user = new ArrayObject(['id' => 42]);
        $authenticatedIdentity = new AuthenticatedRequestIdentity(
            new RemoteIdentity(
                provider: 'clerk',
                providerUserId: 'user_123',
            ),
            $user,
        );
        $component = $this->makeComponent([
            'identityBridge.identity' => $authenticatedIdentity,
        ]);

        $this->assertSame($user, $component->requireUser());
    }

    public function testRequireUserThrowsWhenResolvedUserIsMissing(): void
    {
        $component = $this->makeComponent();

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Authenticated user is required.');

        $component->requireUser();
    }

    /**
     * @param array<string, mixed> $attributes Request attributes to inject.
     * @return \IdentityBridge\Controller\Component\IdentityBridgeComponent
     */
    private function makeComponent(array $attributes = []): IdentityBridgeComponent
    {
        $request = new ServerRequest();
        foreach ($attributes as $name => $value) {
            $request = $request->withAttribute($name, $value);
        }

        $controller = new Controller($request);

        return new IdentityBridgeComponent($controller->components());
    }
}
