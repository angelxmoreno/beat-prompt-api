<?php
declare(strict_types=1);

namespace CakeInstructor\Test\TestCase\Support;

use CakeInstructor\Exception\ProviderRequestException;
use CakeInstructor\Support\InstructorExceptionMapper;
use CakeInstructor\Test\Support\ProviderErrorFixture;
use PHPUnit\Framework\TestCase;

final class InstructorExceptionMapperTest extends TestCase
{
    public function testMapsNestedProviderExceptionWithPayloadMessage(): void
    {
        $mapper = new InstructorExceptionMapper();
        $wrapped = (new ProviderErrorFixture())->anthropicInsufficientCredits();

        $mapped = $mapper->map(
            $wrapped,
            'Structured extraction failed (connection: anthropic:default)',
        );

        self::assertInstanceOf(ProviderRequestException::class, $mapped);
        self::assertStringContainsString('Your credit balance is too low to access the Anthropic API.', $mapped->getMessage());
        self::assertStringContainsString('status=402', $mapped->getMessage());
        self::assertStringContainsString('request_id=req_123', $mapped->getMessage());
        self::assertStringContainsString('needs billing or credits', $mapped->getMessage());
    }
}
