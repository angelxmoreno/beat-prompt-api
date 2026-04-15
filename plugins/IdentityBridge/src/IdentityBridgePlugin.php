<?php
declare(strict_types=1);

namespace IdentityBridge;

use Cake\Console\CommandCollection;
use Cake\Core\BasePlugin;
use Cake\Core\Configure;
use Cake\Core\ContainerInterface;
use Cake\Core\PluginApplicationInterface;
use Cake\Http\MiddlewareQueue;
use Cake\Routing\RouteBuilder;
use IdentityBridge\Exception\ConfigurationException;
use IdentityBridge\Middleware\IdentityBridgeMiddleware;
use IdentityBridge\Provider\ProviderInterface;
use IdentityBridge\Resolver\LocalUserResolverInterface;
use IdentityBridge\Service\IdentityAuthenticator;

/**
 * Plugin for IdentityBridge
 */
class IdentityBridgePlugin extends BasePlugin
{
    /**
     * Load all the plugin configuration and bootstrap logic.
     *
     * The host application is provided as an argument. This allows you to load
     * additional plugin dependencies, or attach events.
     *
     * @param \Cake\Core\PluginApplicationInterface $app The host application
     * @return void
     */
    public function bootstrap(PluginApplicationInterface $app): void
    {
        parent::bootstrap($app);
    }

    /**
     * Add routes for the plugin.
     *
     * If your plugin has many routes and you would like to isolate them into a separate file,
     * you can create `$plugin/config/routes.php` and delete this method.
     *
     * @param \Cake\Routing\RouteBuilder $routes The route builder to update.
     * @return void
     */
    public function routes(RouteBuilder $routes): void
    {
        // remove this method hook if you don't need it
        $routes->plugin(
            'IdentityBridge',
            ['path' => '/identity-bridge'],
            function (RouteBuilder $builder): void {
                // Add custom routes here

                $builder->fallbacks();
            },
        );
        parent::routes($routes);
    }

    /**
     * Add middleware for the plugin.
     *
     * @param \Cake\Http\MiddlewareQueue $middlewareQueue The middleware queue to update.
     * @return \Cake\Http\MiddlewareQueue
     */
    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        return $middlewareQueue->add(IdentityBridgeMiddleware::class);
    }

    /**
     * Add commands for the plugin.
     *
     * @param \Cake\Console\CommandCollection $commands The command collection to update.
     * @return \Cake\Console\CommandCollection
     */
    public function console(CommandCollection $commands): CommandCollection
    {
        // Add your commands here
        // remove this method hook if you don't need it

        $commands = parent::console($commands);

        return $commands;
    }

    /**
     * Register application container services.
     *
     * @param \Cake\Core\ContainerInterface $container The Container to update.
     * @return void
     * @link https://book.cakephp.org/5/en/development/dependency-injection.html#dependency-injection
     */
    public function services(ContainerInterface $container): void
    {
        $container->addShared(ProviderInterface::class, function () use ($container): ProviderInterface {
            $className = $this->getConfiguredClassName('provider', ProviderInterface::class);

            if (!$container->has($className)) {
                $container->addShared($className, function () use ($className): object {
                    return new $className((array)Configure::read('IdentityBridge.providerConfig', []));
                });
            }

            /** @var \IdentityBridge\Provider\ProviderInterface */
            return $container->get($className);
        });

        $container->addShared(
            LocalUserResolverInterface::class,
            function () use ($container): LocalUserResolverInterface {
                $className = $this->getConfiguredClassName('resolver', LocalUserResolverInterface::class);

                if (!$container->has($className)) {
                    $container->addShared($className);
                }

                /** @var \IdentityBridge\Resolver\LocalUserResolverInterface */
                return $container->get($className);
            },
        );

        $container->addShared(IdentityAuthenticator::class, function () use ($container): IdentityAuthenticator {
            return new IdentityAuthenticator(
                $container->get(ProviderInterface::class),
                $container->get(LocalUserResolverInterface::class),
            );
        });

        $container->addShared(IdentityBridgeMiddleware::class, function () use ($container): IdentityBridgeMiddleware {
            return new IdentityBridgeMiddleware(
                $container->get(IdentityAuthenticator::class),
                (array)Configure::read('IdentityBridge', []),
            );
        });
    }

    /**
     * Resolves and validates a configured provider or resolver class name.
     *
     * @param string $configKey The IdentityBridge config key to inspect.
     * @param string $interfaceName The required interface name.
     * @return class-string
     */
    private function getConfiguredClassName(string $configKey, string $interfaceName): string
    {
        $className = Configure::read("IdentityBridge.$configKey");
        if (!is_string($className) || $className === '') {
            throw new ConfigurationException(sprintf(
                'IdentityBridge config key "%s" must contain a class name ' .
                'for %s.',
                $configKey,
                $interfaceName,
            ));
        }

        if (!class_exists($className)) {
            throw new ConfigurationException(sprintf(
                'Configured IdentityBridge class "%s" does not exist.',
                $className,
            ));
        }

        if (!is_a($className, $interfaceName, true)) {
            throw new ConfigurationException(sprintf(
                'Configured IdentityBridge class "%s" must implement %s.',
                $className,
                $interfaceName,
            ));
        }

        return $className;
    }
}
