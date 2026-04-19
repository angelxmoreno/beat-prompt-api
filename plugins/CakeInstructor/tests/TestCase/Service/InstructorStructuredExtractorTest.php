<?php
declare(strict_types=1);

namespace CakeInstructor\Test\TestCase\Service;

use CakeInstructor\Contracts\StructuredOutputFactoryInterface;
use CakeInstructor\Data\ExtractionRequest;
use CakeInstructor\Exception\ProviderRequestException;
use CakeInstructor\Exception\ResponseSchemaException;
use CakeInstructor\Service\InstructorStructuredExtractor;
use CakeInstructor\Support\InstructorExceptionMapper;
use CakeInstructor\Test\Support\ProviderErrorFixture;
use Cognesy\Http\Exceptions\TimeoutException;
use Cognesy\Instructor\StructuredOutput;
use Cognesy\Instructor\Validation\Exceptions\ValidationException;
use PHPUnit\Framework\TestCase;

final class InstructorStructuredExtractorTest extends TestCase
{
    public function testMapsProviderException(): void
    {
        $factory = new class implements StructuredOutputFactoryInterface {
            public function make(?string $connectionName = null, array $connectionOverrides = [], array $structuredOverrides = []): StructuredOutput
            {
                unset($connectionName, $connectionOverrides, $structuredOverrides);
                throw new TimeoutException('Request timed out');
            }
        };

        $extractor = new InstructorStructuredExtractor($factory, new InstructorExceptionMapper());

        $this->expectException(ProviderRequestException::class);
        $extractor->extract(new ExtractionRequest(
            messages: [['role' => 'user', 'content' => 'hello']],
            responseModel: ['type' => 'object'],
        ));
    }

    public function testMapsSchemaException(): void
    {
        $factory = new class implements StructuredOutputFactoryInterface {
            public function make(?string $connectionName = null, array $connectionOverrides = [], array $structuredOverrides = []): StructuredOutput
            {
                unset($connectionName, $connectionOverrides, $structuredOverrides);
                throw new ValidationException('Bad schema', ['field' => 'missing']);
            }
        };

        $extractor = new InstructorStructuredExtractor($factory, new InstructorExceptionMapper());

        $this->expectException(ResponseSchemaException::class);
        $extractor->extract(new ExtractionRequest(
            messages: [['role' => 'user', 'content' => 'hello']],
            responseModel: ['type' => 'object'],
        ));
    }

    public function testProviderErrorIncludesConnectionContextAndHint(): void
    {
        $factory = new class implements StructuredOutputFactoryInterface {
            public function make(?string $connectionName = null, array $connectionOverrides = [], array $structuredOverrides = []): StructuredOutput
            {
                unset($connectionName, $connectionOverrides, $structuredOverrides);
                throw new TimeoutException('Provider error');
            }
        };

        $extractor = new InstructorStructuredExtractor(
            $factory,
            new InstructorExceptionMapper(),
            'anthropic:default',
        );

        try {
            $extractor->extract(new ExtractionRequest(
                messages: [['role' => 'user', 'content' => 'hello']],
                responseModel: ['type' => 'object'],
            ));
            self::fail('Expected provider exception to be thrown.');
        } catch (ProviderRequestException $exception) {
            self::assertStringContainsString('connection: anthropic:default', $exception->getMessage());
            self::assertStringContainsString('ANTHROPIC_API_KEY', $exception->getMessage());
        }
    }

    public function testNestedProviderErrorPreservesUpstreamProviderMessage(): void
    {
        $factory = new class implements StructuredOutputFactoryInterface {
            public function make(?string $connectionName = null, array $connectionOverrides = [], array $structuredOverrides = []): StructuredOutput
            {
                unset($connectionName, $connectionOverrides, $structuredOverrides);
                throw (new ProviderErrorFixture())->anthropicInsufficientCredits();
            }
        };

        $extractor = new InstructorStructuredExtractor(
            $factory,
            new InstructorExceptionMapper(),
            'anthropic:default',
        );

        try {
            $extractor->extract(new ExtractionRequest(
                messages: [['role' => 'user', 'content' => 'hello']],
                responseModel: ['type' => 'object'],
            ));
            self::fail('Expected provider exception to be thrown.');
        } catch (ProviderRequestException $exception) {
            self::assertStringContainsString('Your credit balance is too low to access the Anthropic API.', $exception->getMessage());
            self::assertStringContainsString('status=402', $exception->getMessage());
            self::assertStringContainsString('request_id=req_123', $exception->getMessage());
        }
    }
}
