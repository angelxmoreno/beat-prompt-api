<?php
declare(strict_types=1);

namespace App\Test\TestCase\Command;

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;

final class PromptCanonicalizeCompareCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    public function testRejectsInlineAndFileTogether(): void
    {
        $json = (string)json_encode([
            [
                'input' => 'Joyner Lucas type beat',
                'expected' => [
                    'errorContains' => 'Connection "missing" is not defined',
                ],
            ],
        ], JSON_THROW_ON_ERROR);
        $file = tempnam(sys_get_temp_dir(), 'canon-cases-');
        self::assertIsString($file);
        file_put_contents($file, $json);

        $this->exec('prompt_canonicalize_compare --cases-json=' . escapeshellarg($json) . ' --file=' . $file);

        $this->assertExitCode(1);
        $this->assertErrorContains('Case loading failed: Use either --cases-json or --file, not both.');

        if (is_file($file)) {
            unlink($file);
        }
    }

    public function testSupportsExpectedErrorContains(): void
    {
        $json = (string)json_encode([
            [
                'input' => 'Joyner Lucas type beat',
                'expected' => [
                    'errorContains' => 'Connection "missing" is not defined',
                ],
            ],
        ], JSON_THROW_ON_ERROR);
        $file = tempnam(sys_get_temp_dir(), 'canon-cases-');
        self::assertIsString($file);
        file_put_contents($file, $json);

        $this->exec('prompt_canonicalize_compare --connection=missing --file=' . $file . ' --format=json');

        $this->assertExitSuccess();

        $decoded = json_decode($this->_out->output(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('pass', $decoded['status']);
        $this->assertSame(1, $decoded['passed']);
        $this->assertSame(0, $decoded['failed']);
        $this->assertSame(1, $decoded['total']);

        if (is_file($file)) {
            unlink($file);
        }
    }
}
