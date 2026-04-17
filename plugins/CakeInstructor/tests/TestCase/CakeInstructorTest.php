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
        $service = new CakeInstructor();
        $extractor = $service->createExtractor(
            factory: new class implements StructuredOutputFactoryInterface {
                public function make(?string $connectionName = null, array $connectionOverrides = [], array $structuredOverrides = []): StructuredOutput
                {
                    unset($connectionName, $connectionOverrides, $structuredOverrides);
                    throw new TimeoutException('timeout');
                }
            },
        );

        self::assertInstanceOf(StructuredExtractorInterface::class, $extractor);
    }

    public function testExtractBuildsDefaultsAndMapsFailure(): void
    {
        $this->expectException(ProviderRequestException::class);

        $service = new CakeInstructor();
        $service->runExtract(
            request: new ExtractionRequest(
                messages: [['role' => 'user', 'content' => 'hello']],
                responseModel: ['type' => 'object'],
            ),
            factory: new class implements StructuredOutputFactoryInterface {
                public function make(?string $connectionName = null, array $connectionOverrides = [], array $structuredOverrides = []): StructuredOutput
                {
                    unset($connectionName, $connectionOverrides, $structuredOverrides);
                    throw new TimeoutException('network timeout');
                }
            },
        );
    }
}
