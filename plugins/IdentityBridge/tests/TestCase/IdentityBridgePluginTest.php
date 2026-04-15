<?php
declare(strict_types=1);

namespace IdentityBridge\Test\TestCase;

require_once dirname(__DIR__) . '/Support/InvalidResolver.php';
require_once dirname(__DIR__) . '/Support/TestProvider.php';
require_once dirname(__DIR__) . '/Support/TestResolver.php';

use Cake\Core\Configure;
use Cake\Core\Container;
use IdentityBridge\Exception\ConfigurationException;
use IdentityBridge\IdentityBridgePlugin;
use IdentityBridge\Middleware\IdentityBridgeMiddleware;
use IdentityBridge\Provider\ProviderInterface;
use IdentityBridge\Resolver\LocalUserResolverInterface;
use IdentityBridge\Service\IdentityAuthenticator;
use IdentityBridge\Test\Support\InvalidResolver;
use IdentityBridge\Test\Support\TestProvider;
use IdentityBridge\Test\Support\TestResolver;
use PHPUnit\Framework\TestCase;

class IdentityBridgePluginTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        Configure::delete('IdentityBridge');
    }

    public function testServicesRegistersConfiguredProviderResolverAndSharedServices(): void
    {
        Configure::write('IdentityBridge', [
            'provider' => TestProvider::class,
            'providerConfig' => [
                'baseUrl' => 'https://example.test',
                'secret' => 'abc123',
            ],
            'resolver' => TestResolver::class,
            'mode' => 'protected_by_default',
            'overrides' => [],
        ]);

        $container = new Container();
        (new IdentityBridgePlugin())->services($container);

        $provider = $container->get(ProviderInterface::class);
        $resolver = $container->get(LocalUserResolverInterface::class);
        $authenticator = $container->get(IdentityAuthenticator::class);
        $middleware = $container->get(IdentityBridgeMiddleware::class);

        $this->assertInstanceOf(TestProvider::class, $provider);
        $this->assertSame([
            'baseUrl' => 'https://example.test',
            'secret' => 'abc123',
        ], $provider->getConfig());
        $this->assertInstanceOf(TestResolver::class, $resolver);
        $this->assertInstanceOf(IdentityAuthenticator::class, $authenticator);
        $this->assertInstanceOf(IdentityBridgeMiddleware::class, $middleware);
    }

    public function testResolvingProviderFailsWhenProviderConfigIsMissing(): void
    {
        Configure::write('IdentityBridge', [
            'resolver' => TestResolver::class,
        ]);

        $container = new Container();
        (new IdentityBridgePlugin())->services($container);

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(
            sprintf(
                'IdentityBridge config key "provider" must contain a class name for %s.',
                ProviderInterface::class,
            ),
        );

        $container->get(ProviderInterface::class);
    }

    public function testResolvingResolverFailsWhenConfiguredClassDoesNotImplementInterface(): void
    {
        Configure::write('IdentityBridge', [
            'provider' => TestProvider::class,
            'resolver' => InvalidResolver::class,
        ]);

        $container = new Container();
        (new IdentityBridgePlugin())->services($container);

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Configured IdentityBridge class "%s" must implement %s.',
                InvalidResolver::class,
                LocalUserResolverInterface::class,
            ),
        );

        $container->get(LocalUserResolverInterface::class);
    }
}
