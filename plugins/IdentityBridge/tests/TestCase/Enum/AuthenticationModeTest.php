<?php
declare(strict_types=1);

namespace IdentityBridge\Test\TestCase\Enum;

use IdentityBridge\Enum\AuthenticationMode;
use PHPUnit\Framework\TestCase;

class AuthenticationModeTest extends TestCase
{
    public function testCasesExposeExpectedConfigValues(): void
    {
        $this->assertSame('public_by_default', AuthenticationMode::PublicByDefault->value);
        $this->assertSame('protected_by_default', AuthenticationMode::ProtectedByDefault->value);
    }

    public function testValuesReturnsAllSupportedModes(): void
    {
        $this->assertSame(
            ['public_by_default', 'protected_by_default'],
            AuthenticationMode::values(),
        );
    }
}
