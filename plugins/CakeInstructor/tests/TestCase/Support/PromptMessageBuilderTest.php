<?php
declare(strict_types=1);

namespace CakeInstructor\Test\TestCase\Support;

use CakeInstructor\Support\PromptMessageBuilder;
use PHPUnit\Framework\TestCase;

final class PromptMessageBuilderTest extends TestCase
{
    public function testFluentMessageBuilder(): void
    {
        $messages = PromptMessageBuilder::make()
            ->system('You are concise')
            ->user('Write a summary')
            ->assistant('Sure')
            ->custom('tool', '{"status":"ok"}')
            ->toArray();

        self::assertSame(
            [
                ['role' => 'system', 'content' => 'You are concise'],
                ['role' => 'user', 'content' => 'Write a summary'],
                ['role' => 'assistant', 'content' => 'Sure'],
                ['role' => 'tool', 'content' => '{"status":"ok"}'],
            ],
            $messages,
        );
    }

    public function testFromUserPrompt(): void
    {
        $messages = PromptMessageBuilder::fromUserPrompt('Create JSON')->toArray();

        self::assertSame([
            ['role' => 'user', 'content' => 'Create JSON'],
        ], $messages);
    }
}
