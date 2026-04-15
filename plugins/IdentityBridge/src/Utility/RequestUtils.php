<?php
declare(strict_types=1);

namespace IdentityBridge\Utility;

use Cake\Http\ServerRequest;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Small request helpers shared across IdentityBridge HTTP integrations.
 */
final class RequestUtils
{
    /**
     * Static utility class.
     */
    private function __construct()
    {
    }

    /**
     * Builds the normalized prefix/controller/action target from a request.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request to inspect.
     * @return string|null
     */
    public static function getRouteTarget(ServerRequestInterface $request): ?string
    {
        $params = [];
        if ($request instanceof ServerRequest) {
            $params = [
                'prefix' => $request->getParam('prefix'),
                'controller' => $request->getParam('controller'),
                'action' => $request->getParam('action'),
            ];
        } else {
            $params = [
                'prefix' => $request->getAttribute('prefix'),
                'controller' => $request->getAttribute('controller'),
                'action' => $request->getAttribute('action'),
            ];
        }

        $controller = $params['controller'];
        $action = $params['action'];
        if (!is_string($controller) || $controller === '' || !is_string($action) || $action === '') {
            return null;
        }

        $segments = [];
        $prefix = $params['prefix'];
        if (is_string($prefix) && $prefix !== '') {
            $segments[] = $prefix;
        }

        $segments[] = $controller;
        $segments[] = $action;

        return implode('/', $segments);
    }

    /**
     * Extracts a bearer token from the Authorization header when present.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request to inspect.
     * @return string
     */
    public static function extractBearerToken(ServerRequestInterface $request): string
    {
        $header = $request->getHeaderLine('Authorization');
        if (preg_match('/^\s*Bearer\s+(.+?)\s*$/i', $header, $matches) === 1) {
            return $matches[1];
        }

        return '';
    }
}
