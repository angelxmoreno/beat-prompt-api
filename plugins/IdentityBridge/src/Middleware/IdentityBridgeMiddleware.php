<?php
declare(strict_types=1);

namespace IdentityBridge\Middleware;

use Cake\Core\Configure;
use Cake\Http\Response;
use IdentityBridge\Enum\AuthenticationMode;
use IdentityBridge\Exception\AuthenticationException;
use IdentityBridge\Exception\ConfigurationException;
use IdentityBridge\Service\IdentityAuthenticator;
use IdentityBridge\Utility\RequestUtils;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Route-aware middleware for applying IdentityBridge authentication.
 */
final class IdentityBridgeMiddleware implements MiddlewareInterface
{
    private readonly AuthenticationMode $mode;

    /**
     * @var array<string, bool>
     */
    private array $overrides;

    /**
     * @param \IdentityBridge\Service\IdentityAuthenticator $authenticator Shared authenticator service.
     * @param array<string, mixed> $config Middleware configuration overrides.
     */
    public function __construct(
        private readonly IdentityAuthenticator $authenticator,
        array $config = [],
    ) {
        $config = array_replace((array)Configure::read('IdentityBridge', []), $config);

        $mode = AuthenticationMode::tryFrom(
            (string)($config['mode'] ?? AuthenticationMode::ProtectedByDefault->value),
        );
        if ($mode === null) {
            throw new ConfigurationException('Invalid IdentityBridge authentication mode.');
        }

        $overrides = $config['overrides'] ?? [];
        if (!is_array($overrides)) {
            throw new ConfigurationException('IdentityBridge overrides must be an array.');
        }

        foreach ($overrides as $pattern => $isProtected) {
            if (!is_string($pattern) || $pattern === '') {
                throw new ConfigurationException('IdentityBridge override keys must be non-empty strings.');
            }
            if (!is_bool($isProtected)) {
                throw new ConfigurationException(
                    'IdentityBridge override values must be booleans.',
                );
            }
        }

        $this->mode = $mode;
        $this->overrides = $overrides;
    }

    /**
     * Applies route protection rules and attaches authenticated request state.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The incoming request.
     * @param \Psr\Http\Server\RequestHandlerInterface $handler The next handler.
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $routeTarget = RequestUtils::getRouteTarget($request);
        if ($routeTarget === null || !$this->isProtectedRoute($routeTarget)) {
            return $handler->handle($request);
        }

        try {
            $authenticatedIdentity = $this->authenticator->authenticate(
                RequestUtils::extractBearerToken($request),
            );
        } catch (AuthenticationException $exception) {
            return $this->buildUnauthorizedResponse($exception);
        }

        $request = $request->withAttribute('identityBridge.identity', $authenticatedIdentity);
        $request = $request->withAttribute('identity', $authenticatedIdentity->user);

        return $handler->handle($request);
    }

    /**
     * Determines whether the matched route requires authentication.
     *
     * @param string $routeTarget The normalized prefix/controller/action target.
     * @return bool
     */
    private function isProtectedRoute(string $routeTarget): bool
    {
        foreach ($this->overrides as $pattern => $isProtected) {
            if (fnmatch($pattern, $routeTarget)) {
                return $isProtected;
            }
        }

        return $this->mode === AuthenticationMode::ProtectedByDefault;
    }

    /**
     * Builds a consistent unauthorized JSON response for auth failures.
     *
     * @param \IdentityBridge\Exception\AuthenticationException $exception The auth error.
     * @return \Psr\Http\Message\ResponseInterface
     */
    private function buildUnauthorizedResponse(AuthenticationException $exception): ResponseInterface
    {
        $body = json_encode(['message' => $exception->getMessage()], JSON_THROW_ON_ERROR);

        return (new Response())
            ->withStatus(401)
            ->withType('application/json')
            ->withStringBody($body);
    }
}
