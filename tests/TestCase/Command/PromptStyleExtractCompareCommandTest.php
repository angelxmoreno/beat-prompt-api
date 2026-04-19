<?php
declare(strict_types=1);

namespace App\Test\TestCase\Command;

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;

final class PromptStyleExtractCompareCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    public function testSupportsExpectedErrorContains(): void
    {
        $json = (string)json_encode([
            [
                'canonical' => [
                    'kind' => 'artist_style_prompt',
                    'artists' => ['joyner lucas'],
                    'target' => 'beat',
                    'modifiers' => [],
                ],
                'expected' => [
                    'errorContains' => 'Connection "missing" is not defined',
                ],
            ],
        ], JSON_THROW_ON_ERROR);
        $file = tempnam(sys_get_temp_dir(), 'style-cases-');
        self::assertIsString($file);
        file_put_contents($file, $json);

        $this->exec('prompt_style_extract_compare --connection=missing --file=' . $file . ' --format=json');

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
