<?php
declare(strict_types=1);

namespace IdentityBridge\Test\TestCase\Exception;

use Cake\Core\Exception\CakeException;
use IdentityBridge\Exception\AuthenticationException;
use PHPUnit\Framework\TestCase;

class AuthenticationExceptionTest extends TestCase
{
    public function testItExtendsCakeException(): void
    {
        $exception = new AuthenticationException('Token verification failed.', 401);

        $this->assertInstanceOf(CakeException::class, $exception);
        $this->assertSame('Token verification failed.', $exception->getMessage());
        $this->assertSame(401, $exception->getCode());
    }
}
