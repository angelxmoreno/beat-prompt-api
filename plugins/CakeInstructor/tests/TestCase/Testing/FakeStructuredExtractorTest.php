<?php
declare(strict_types=1);

namespace CakeInstructor\Test\TestCase\Testing;

use CakeInstructor\Data\ExtractionRequest;
use CakeInstructor\Exception\InstructorIntegrationException;
use CakeInstructor\Testing\FakeStructuredExtractor;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class FakeStructuredExtractorTest extends TestCase
{
    public function testReturnsQueuedResponseAndTracksCalls(): void
    {
        $fake = new FakeStructuredExtractor(['first', 'second']);

        $request = new ExtractionRequest(
            messages: [['role' => 'user', 'content' => 'hello']],
            responseModel: ['type' => 'object'],
        );

        self::assertSame('first', $fake->extract($request));
        self::assertSame('second', $fake->extract($request));
        self::assertCount(2, $fake->calls());
        self::assertSame(0, $fake->pendingCount());
    }

    public function testThrowsWhenQueueIsEmpty(): void
    {
        $fake = new FakeStructuredExtractor();

        $this->expectException(InstructorIntegrationException::class);
        $fake->extract(new ExtractionRequest(
            messages: [['role' => 'user', 'content' => 'hello']],
            responseModel: ['type' => 'object'],
        ));
    }

    public function testCanQueueThrowable(): void
    {
        $fake = new FakeStructuredExtractor([new RuntimeException('boom')]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('boom');

        $fake->extract(new ExtractionRequest(
            messages: [['role' => 'user', 'content' => 'hello']],
            responseModel: ['type' => 'object'],
        ));
    }
}
