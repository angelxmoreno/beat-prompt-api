<?php
declare(strict_types=1);

namespace CakeInstructor\Test\TestCase;

use CakeInstructor\CakeInstructor;
use CakeInstructor\Contracts\StructuredExtractorInterface;
use CakeInstructor\Contracts\StructuredOutputFactoryInterface;
use CakeInstructor\Data\ExtractionRequest;
use CakeInstructor\Exception\ProviderRequestException;
use Cognesy\Http\Exceptions\TimeoutException;
use Cognesy\Instructor\StructuredOutput;
use PHPUnit\Framework\TestCase;

final class CakeInstructorTest extends TestCase
{
    public function testExtractorReturnsExtractorContract(): void
    {
        $extractor = CakeInstructor::extractor(
            factory: new class implements StructuredOutputFactoryInterface {
                public function make(?string $connectionName = null, array $connectionOverrides = [], array $structuredOverrides = []): StructuredOutput
                {
                    throw new TimeoutException('timeout');
                }
            },
        );

        self::assertInstanceOf(StructuredExtractorInterface::class, $extractor);
    }

    public function testExtractBuildsDefaultsAndMapsFailure(): void
    {
        $this->expectException(ProviderRequestException::class);

        CakeInstructor::extract(
            request: ExtractionRequest::fromPrompt('hello', responseModel: ['type' => 'object']),
            factory: new class implements StructuredOutputFactoryInterface {
                public function make(?string $connectionName = null, array $connectionOverrides = [], array $structuredOverrides = []): StructuredOutput
                {
                    throw new TimeoutException('network timeout');
                }
            },
        );
    }
}
