<?php
declare(strict_types=1);

namespace IdentityBridge\Test\TestCase\Exception;

use Cake\Core\Exception\CakeException;
use IdentityBridge\Exception\ConfigurationException;
use PHPUnit\Framework\TestCase;

class ConfigurationExceptionTest extends TestCase
{
    public function testItExtendsCakeException(): void
    {
        $exception = new ConfigurationException('IdentityBridge provider is not configured.', 500);

        $this->assertInstanceOf(CakeException::class, $exception);
        $this->assertSame('IdentityBridge provider is not configured.', $exception->getMessage());
        $this->assertSame(500, $exception->getCode());
    }
}
