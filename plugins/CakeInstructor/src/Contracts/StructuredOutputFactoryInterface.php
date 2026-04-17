<?php
declare(strict_types=1);

namespace CakeInstructor\Contracts;

use Cognesy\Instructor\StructuredOutput;

interface StructuredOutputFactoryInterface
{
    /**
     * @param array<string, mixed> $connectionOverrides
     * @param array<string, mixed> $structuredOverrides
     */
    public function make(
        ?string $connectionName = null,
        array $connectionOverrides = [],
        array $structuredOverrides = [],
    ): StructuredOutput;
}
