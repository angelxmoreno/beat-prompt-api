<?php
declare(strict_types=1);

namespace CakeInstructor;

use CakeInstructor\Contracts\StructuredExtractorInterface;
use CakeInstructor\Contracts\StructuredOutputFactoryInterface;
use CakeInstructor\Data\ExtractionRequest;
use CakeInstructor\Factory\StructuredOutputFactory;
use CakeInstructor\Service\InstructorStructuredExtractor;
use CakeInstructor\Support\InstructorExceptionMapper;

final class CakeInstructor
{
    /**
     * Build a structured extractor with plugin defaults.
     */
    public static function extractor(
        ?string $connectionName = null,
        ?StructuredOutputFactoryInterface $factory = null,
        ?InstructorExceptionMapper $exceptionMapper = null,
    ): StructuredExtractorInterface {
        return new InstructorStructuredExtractor(
            factory: $factory ?? new StructuredOutputFactory(),
            exceptionMapper: $exceptionMapper ?? new InstructorExceptionMapper(),
            connectionName: $connectionName,
        );
    }

    /**
     * Extract structured output in one call using plugin defaults.
     */
    public static function extract(
        ExtractionRequest $request,
        ?string $connectionName = null,
        ?StructuredOutputFactoryInterface $factory = null,
        ?InstructorExceptionMapper $exceptionMapper = null,
    ): mixed {
        return self::extractor(
            connectionName: $connectionName,
            factory: $factory,
            exceptionMapper: $exceptionMapper,
        )->extract($request);
    }
}
