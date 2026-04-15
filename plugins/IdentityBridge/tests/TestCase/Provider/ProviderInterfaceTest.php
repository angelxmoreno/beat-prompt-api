<?php
declare(strict_types=1);

namespace IdentityBridge\Test\TestCase\Provider;

use IdentityBridge\Provider\ProviderInterface;
use IdentityBridge\ValueObject\RemoteIdentity;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class ProviderInterfaceTest extends TestCase
{
    public function testVerifyMethodSignature(): void
    {
        $method = new ReflectionMethod(ProviderInterface::class, 'verify');

        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);
        $this->assertSame('jwt', $parameters[0]->getName());
        $this->assertSame('string', (string)$parameters[0]->getType());
        $this->assertSame(RemoteIdentity::class, (string)$method->getReturnType());
    }
}
