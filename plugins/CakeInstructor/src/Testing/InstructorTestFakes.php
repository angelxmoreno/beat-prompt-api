<?php
declare(strict_types=1);

namespace CakeInstructor\Testing;

final class InstructorTestFakes
{
    /**
     * @param array<int, mixed> $responses
     */
    public static function extractor(array $responses = []): FakeStructuredExtractor
    {
        return new FakeStructuredExtractor($responses);
    }
}
