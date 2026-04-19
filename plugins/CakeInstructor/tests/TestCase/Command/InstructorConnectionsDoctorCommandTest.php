<?php
declare(strict_types=1);

namespace CakeInstructor\Test\TestCase\Command;

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\ContainerStubTrait;
use Cake\TestSuite\TestCase;
use CakeInstructor\Service\InstructorConnectionProbeService;
use CakeInstructor\Support\ConnectionConfigValidator;

final class InstructorConnectionsDoctorCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;
    use ContainerStubTrait;

    public function testSkipsProbeForInvalidConnectionAndReportsConfigError(): void
    {
        $this->mockService(ConnectionConfigValidator::class, function () {
            return new ConnectionConfigValidator([
                'default_connection' => 'openai:default',
                'connections' => [
                    'openai:default' => [
                        'driver' => 'openai',
                        'apiUrl' => 'https://api.openai.com/v1',
                        'endpoint' => '/chat/completions',
                        'apiKey' => '',
                        'model' => 'gpt-4.1',
                    ],
                ],
            ]);
        });
        $this->mockService(InstructorConnectionProbeService::class, function () {
            return new InstructorConnectionProbeService([]);
        });

        $this->exec('instructor_connections_doctor --format=json');
        $this->assertExitCode(1);

        $decoded = json_decode($this->_out->output(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertFalse($decoded['ok']);
        $this->assertSame('config_error', $decoded['results'][0]['type']);
        $this->assertStringContainsString('OpenAI apiKey is blank.', $decoded['results'][0]['message']);
    }
}
