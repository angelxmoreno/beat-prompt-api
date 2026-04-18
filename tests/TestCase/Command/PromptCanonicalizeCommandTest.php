<?php
declare(strict_types=1);

namespace App\Test\TestCase\Command;

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;

final class PromptCanonicalizeCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    public function testFailsFastWhenLlmConfigIsMissing(): void
    {
        $this->exec('prompt_canonicalize "Joyner Lucas type beat" --connection=missing --format=json');

        $this->assertExitCode(1);
        $this->assertErrorContains('Canonicalization failed:');
    }
}
