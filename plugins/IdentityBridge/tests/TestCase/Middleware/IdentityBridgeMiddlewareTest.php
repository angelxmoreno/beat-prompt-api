<?php
declare(strict_types=1);

namespace IdentityBridge\Test\TestCase\Middleware;

use ArrayAccess;
use ArrayObject;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use IdentityBridge\Enum\AuthenticationMode;
use IdentityBridge\Exception\AuthenticationException;
use IdentityBridge\Exception\ConfigurationException;
use IdentityBridge\Middleware\IdentityBridgeMiddleware;
use IdentityBridge\Provider\ProviderInterface;
use IdentityBridge\Resolver\LocalUserResolverInterface;
use IdentityBridge\Service\IdentityAuthenticator;
use IdentityBridge\ValueObject\AuthenticatedRequestIdentity;
use IdentityBridge\ValueObject\RemoteIdentity;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class IdentityBridgeMiddlewareTest extends TestCase
{
    public function testPublicOverrideSkipsAuthentication(): void
    {
        $middleware = new IdentityBridgeMiddleware(
            $this->createAuthenticatorThatMustNotRun(),
            [
                'mode' => AuthenticationMode::ProtectedByDefault->value,
                'overrides' => [
                    'Api/Health/index' => false,
                ],
            ],
        );

        $handler = new class implements RequestHandlerInterface {
            public ?ServerRequestInterface $request = null;

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->request = $request;

                return (new Response())->withStatus(204);
            }
        };

        $response = $middleware->process(
            $this->makeRequest('Api', 'Health', 'index'),
            $handler,
        );

        $this->assertSame(204, $response->getStatusCode());
        $this->assertNull($handler->request?->getAttribute('identityBridge.identity'));
    }

    public function testProtectedRouteReturnsUnauthorizedWhenTokenIsMissing(): void
    {
        $middleware = new IdentityBridgeMiddleware(
            $this->makeAuthenticator(),
            [
                'mode' => AuthenticationMode::ProtectedByDefault->value,
                'overrides' => [],
            ],
        );

        $response = $middleware->process(
            $this->makeRequest('Api', 'Issues', 'index'),
            new class implements RequestHandlerInterface {
                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    return new Response();
                }
            },
        );

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertSame('{"message":"Missing bearer token."}', (string)$response->getBody());
    }

    public function testProtectedRouteAttachesResolvedAuthState(): void
    {
        $remoteIdentity = new RemoteIdentity(
            provider: 'clerk',
            providerUserId: 'user_123',
            email: 'demo@example.com',
        );
        $user = new ArrayObject([
            'id' => 10,
            'email' => 'demo@example.com',
        ]);
        $authenticatedIdentity = new AuthenticatedRequestIdentity($remoteIdentity, $user);

        $middleware = new IdentityBridgeMiddleware(
            $this->makeAuthenticator($remoteIdentity, $user),
            [
                'mode' => AuthenticationMode::ProtectedByDefault->value,
                'overrides' => [],
            ],
        );

        $handler = new class implements RequestHandlerInterface {
            public ?ServerRequestInterface $request = null;

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->request = $request;

                return (new Response())->withStatus(200);
            }
        };

        $response = $middleware->process(
            $this->makeRequest('Api', 'Issues', 'index', 'Bearer valid.jwt.token'),
            $handler,
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertEquals($authenticatedIdentity, $handler->request?->getAttribute('identityBridge.identity'));
        $this->assertSame($user, $handler->request?->getAttribute('identity'));
    }

    public function testPublicByDefaultOverrideCanProtectSpecificRoute(): void
    {
        $middleware = new IdentityBridgeMiddleware(
            $this->makeAuthenticator(),
            [
                'mode' => AuthenticationMode::PublicByDefault->value,
                'overrides' => [
                    'Api/Issues/*' => true,
                ],
            ],
        );

        $response = $middleware->process(
            $this->makeRequest('Api', 'Issues', 'view'),
            new class implements RequestHandlerInterface {
                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    return new Response();
                }
            },
        );

        $this->assertSame(401, $response->getStatusCode());
    }

    public function testInvalidModeThrowsConfigurationException(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Invalid IdentityBridge authentication mode.');

        new IdentityBridgeMiddleware(
            $this->createAuthenticatorThatMustNotRun(),
            [
                'mode' => 'invalid',
            ],
        );
    }

    public function testInvalidOverrideValueThrowsConfigurationException(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('IdentityBridge override values must be booleans.');

        new IdentityBridgeMiddleware(
            $this->createAuthenticatorThatMustNotRun(),
            [
                'overrides' => [
                    'Api/Issues/index' => 'public',
                ],
            ],
        );
    }

    private function makeRequest(
        ?string $prefix,
        string $controller,
        string $action,
        ?string $authorization = null,
    ): ServerRequestInterface {
        $request = new ServerRequest([
            'params' => array_filter([
                'prefix' => $prefix,
                'controller' => $controller,
                'action' => $action,
            ], static fn(mixed $value): bool => $value !== null),
        ]);

        if ($authorization !== null) {
            $request = $request->withHeader('Authorization', $authorization);
        }

        return $request;
    }

    private function createAuthenticatorThatMustNotRun(): IdentityAuthenticator
    {
        $provider = new class implements ProviderInterface {
            public function verify(string $jwt): RemoteIdentity
            {
                TestCase::fail('Provider should not be called for skipped routes.');
            }
        };

        $resolver = new class implements LocalUserResolverInterface {
            public function resolve(RemoteIdentity $identity): ArrayAccess
            {
                TestCase::fail('Resolver should not be called for skipped routes.');
            }
        };

        return new IdentityAuthenticator($provider, $resolver);
    }

    private function makeAuthenticator(?RemoteIdentity $remoteIdentity = null, ?ArrayAccess $user = null): IdentityAuthenticator
    {
        $remoteIdentity ??= new RemoteIdentity(
            provider: 'clerk',
            providerUserId: 'provider-user-123',
        );
        $user ??= new ArrayObject(['id' => 1]);

        $provider = new class ($remoteIdentity) implements ProviderInterface {
            public function __construct(private readonly RemoteIdentity $remoteIdentity)
            {
            }

            public function verify(string $jwt): RemoteIdentity
            {
                if ($jwt !== 'valid.jwt.token') {
                    throw new AuthenticationException('Missing bearer token.');
                }

                return $this->remoteIdentity;
            }
        };

        $resolver = new class ($user) implements LocalUserResolverInterface {
            public function __construct(private readonly ArrayAccess $user)
            {
            }

            public function resolve(RemoteIdentity $identity): ArrayAccess
            {
                return $this->user;
            }
        };

        return new IdentityAuthenticator($provider, $resolver);
    }
}
