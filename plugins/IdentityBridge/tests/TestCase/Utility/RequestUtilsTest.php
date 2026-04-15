<?php
declare(strict_types=1);

namespace IdentityBridge\Test\TestCase\Utility;

use Cake\Http\ServerRequest;
use IdentityBridge\Utility\RequestUtils;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class RequestUtilsTest extends TestCase
{
    public function testGetRouteTargetBuildsPrefixControllerActionString(): void
    {
        $request = $this->makeRequest(
            params: [
                'prefix' => 'Api',
                'controller' => 'Issues',
                'action' => 'index',
            ],
        );

        $this->assertSame('Api/Issues/index', RequestUtils::getRouteTarget($request));
    }

    public function testGetRouteTargetReturnsNullWithoutControllerOrAction(): void
    {
        $request = $this->makeRequest(
            params: [
                'prefix' => 'Api',
                'controller' => 'Issues',
            ],
        );

        $this->assertNull(RequestUtils::getRouteTarget($request));
    }

    public function testExtractBearerTokenReturnsTokenWhenHeaderIsPresent(): void
    {
        $request = $this->makeRequest(
            authorization: 'Bearer valid.jwt.token',
        );

        $this->assertSame('valid.jwt.token', RequestUtils::extractBearerToken($request));
    }

    public function testExtractBearerTokenReturnsEmptyStringWithoutBearerHeader(): void
    {
        $request = $this->makeRequest(
            authorization: 'Basic abc123',
        );

        $this->assertSame('', RequestUtils::extractBearerToken($request));
    }

    private function makeRequest(array $params = [], ?string $authorization = null): ServerRequestInterface
    {
        $request = new ServerRequest([
            'params' => $params,
        ]);

        if ($authorization !== null) {
            $request = $request->withHeader('Authorization', $authorization);
        }

        return $request;
    }
}
