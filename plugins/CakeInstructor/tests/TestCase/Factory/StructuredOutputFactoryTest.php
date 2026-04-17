<?php
declare(strict_types=1);

namespace CakeInstructor\Test\TestCase\Factory;

use CakeInstructor\Exception\MissingConfigurationException;
use CakeInstructor\Factory\StructuredOutputFactory;
use Cognesy\Instructor\StructuredOutput;
use PHPUnit\Framework\TestCase;

final class StructuredOutputFactoryTest extends TestCase
{
    public function testMakeReturnsStructuredOutput(): void
    {
        $factory = new StructuredOutputFactory([
            'default_connection' => 'default',
            'connections' => [
                'default' => [
                    'driver' => 'openai',
                    'apiKey' => 'test-key',
                    'model' => 'gpt-4.1-mini',
                ],
            ],
            'structured' => [
                'maxRetries' => 2,
            ],
        ]);

        $result = $factory->make();

        self::assertInstanceOf(StructuredOutput::class, $result);
    }

    public function testMakeSupportsSnakeCaseAliases(): void
    {
        $factory = new StructuredOutputFactory([
            'default_connection' => 'default',
            'connections' => [
                'default' => [
                    'driver' => 'openai',
                    'api_key' => 'test-key',
                    'model' => 'gpt-4.1-mini',
                    'max_tokens' => '800',
                    'context_length' => '64000',
                    'max_output_length' => '1200',
                ],
            ],
            'structured' => [
                'max_retries' => 3,
            ],
        ]);

        $result = $factory->make();

        self::assertInstanceOf(StructuredOutput::class, $result);
    }

    public function testMakeThrowsOnMissingConnection(): void
    {
        $factory = new StructuredOutputFactory([
            'default_connection' => 'missing',
            'connections' => [
                'default' => [
                    'driver' => 'openai',
                    'apiKey' => 'test-key',
                    'model' => 'gpt-4.1-mini',
                ],
            ],
        ]);

        $this->expectException(MissingConfigurationException::class);
        $factory->make();
    }
}
