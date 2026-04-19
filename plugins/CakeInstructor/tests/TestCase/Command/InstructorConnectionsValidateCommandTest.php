<?php
declare(strict_types=1);

namespace CakeInstructor\Test\TestCase\Command;

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\ContainerStubTrait;
use Cake\TestSuite\TestCase;
use CakeInstructor\Support\ConnectionConfigValidator;

final class InstructorConnectionsValidateCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;
    use ContainerStubTrait;

    public function testReportsInvalidConnectionConfigInJson(): void
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

        $this->exec('instructor_connections_validate --format=json');
        $this->assertExitCode(1);

        $decoded = json_decode($this->_out->output(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertFalse($decoded['valid']);
        $this->assertSame('openai:default', $decoded['connections'][0]['connection']);
        $this->assertContains('OpenAI apiKey is blank.', $decoded['connections'][0]['errors']);
    }
}
