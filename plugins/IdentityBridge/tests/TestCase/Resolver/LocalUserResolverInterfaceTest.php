<?php
declare(strict_types=1);

namespace IdentityBridge\Test\TestCase\Resolver;

use ArrayAccess;
use IdentityBridge\Resolver\LocalUserResolverInterface;
use IdentityBridge\ValueObject\RemoteIdentity;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class LocalUserResolverInterfaceTest extends TestCase
{
    public function testResolveMethodSignature(): void
    {
        $method = new ReflectionMethod(LocalUserResolverInterface::class, 'resolve');

        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);
        $this->assertSame('identity', $parameters[0]->getName());
        $this->assertSame(RemoteIdentity::class, (string)$parameters[0]->getType());
        $this->assertSame(ArrayAccess::class, (string)$method->getReturnType());
    }
}
