<?php
declare(strict_types=1);

namespace CakeInstructor\Test\TestCase\Command;

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\ContainerStubTrait;
use Cake\TestSuite\TestCase;
use CakeInstructor\Exception\MissingConfigurationException;
use CakeInstructor\Service\InstructorConnectionProbeService;

final class InstructorConnectionProbeCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;
    use ContainerStubTrait;

    public function testClassifiesMissingConnectionAsConfigErrorInJson(): void
    {
        $this->exec('instructor_connection_probe --connection=missing --format=json');

        $this->assertExitCode(1);

        $decoded = json_decode($this->_out->output(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertFalse($decoded['ok']);
        $this->assertSame('config_error', $decoded['type']);
        $this->assertSame('missing', $decoded['connection']);
        $this->assertSame('unknown', $decoded['driver']);
        $this->assertSame('unknown', $decoded['model']);
        $this->assertArrayHasKey('message', $decoded);
        $this->assertArrayHasKey('hint', $decoded);
    }

    public function testClassifiesMissingConnectionAsConfigErrorInText(): void
    {
        $this->exec('instructor_connection_probe --connection=missing');

        $this->assertExitCode(1);
        $this->assertOutputContains('Connection: missing');
        $this->assertOutputContains('Driver: unknown');
        $this->assertOutputContains('Model: unknown');
        $this->assertOutputContains('Type: config_error');
        $this->assertOutputContains('Hint: Verify config/app.php connection name and required env vars for this connection.');
    }

    public function testPromptsForConnectionWhenOptionIsOmitted(): void
    {
        $this->mockService(InstructorConnectionProbeService::class, function () {
            return new InstructorConnectionProbeService(
                config: [
                    'default_connection' => 'fake:default',
                    'connections' => [
                        'fake:default' => [
                            'driver' => '',
                            'model' => '',
                        ],
                    ],
                ],
                probeRunner: static function (): never {
                    throw new MissingConfigurationException('LLM connection requires non-empty driver and model values.');
                },
            );
        });

        $this->exec('instructor_connection_probe', ['fake:default']);

        $this->assertExitCode(1);
        $this->assertOutputContains('Available CakeInstructor connections:');
        $this->assertOutputContains('- fake:default (default)');
        $this->assertOutputContains('Connection: fake:default');
        $this->assertOutputContains('Driver: unknown');
        $this->assertOutputContains('Model: unknown');
    }

    public function testDebugIncludesMaskedConnectionConfigInJson(): void
    {
        $this->mockService(InstructorConnectionProbeService::class, function () {
            return new InstructorConnectionProbeService(
                config: [
                    'default_connection' => 'anthropic:default',
                    'connections' => [
                        'anthropic:default' => [
                            'driver' => 'anthropic',
                            'apiUrl' => 'https://api.anthropic.com/v1',
                            'apiKey' => 'sk-ant-test-secret',
                            'model' => 'claude-test',
                            'options' => [
                                'timeout' => 30,
                            ],
                        ],
                    ],
                ],
                probeRunner: static function (): never {
                    throw new MissingConfigurationException('Probe failed.');
                },
            );
        });

        $this->exec('instructor_connection_probe --connection=anthropic:default --format=json --debug');

        $this->assertExitCode(1);

        $decoded = json_decode($this->_out->output(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('anthropic:default', $decoded['connection']);
        $this->assertSame('anthropic', $decoded['driver']);
        $this->assertSame('claude-test', $decoded['model']);
        $this->assertSame('https://api.anthropic.com/v1', $decoded['debug']['apiUrl']);
        $this->assertSame('sk**************et', $decoded['debug']['apiKey']);
        $this->assertSame(['timeout' => 30], $decoded['debug']['options']);
    }
}
