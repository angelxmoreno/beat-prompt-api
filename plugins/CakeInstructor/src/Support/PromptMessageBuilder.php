<?php
declare(strict_types=1);

namespace CakeInstructor\Support;

final class PromptMessageBuilder
{
    /**
     * @var array<int, array<string, string>>
     */
    private array $messages = [];

    /**
     * Create a new message builder.
     */
    public static function make(): self
    {
        return new self();
    }

    /**
     * Create a builder seeded with one user prompt.
     */
    public static function fromUserPrompt(string $prompt): self
    {
        return self::make()->user($prompt);
    }

    /**
     * Append a system message.
     */
    public function system(string $content): self
    {
        return $this->push('system', $content);
    }

    /**
     * Append a user message.
     */
    public function user(string $content): self
    {
        return $this->push('user', $content);
    }

    /**
     * Append an assistant message.
     */
    public function assistant(string $content): self
    {
        return $this->push('assistant', $content);
    }

    /**
     * Append a custom role message.
     */
    public function custom(string $role, string $content): self
    {
        return $this->push($role, $content);
    }

    /**
     * @param array<int, array{role:string, content:string}> $messages
     */
    public function merge(array $messages): self
    {
        foreach ($messages as $message) {
            $this->custom($message['role'], $message['content']);
        }

        return $this;
    }

    /**
     * @return array<int, array{role:string, content:string}>
     */
    public function toArray(): array
    {
        return $this->messages;
    }

    /**
     * Append a normalized role/content pair.
     */
    private function push(string $role, string $content): self
    {
        $this->messages[] = [
            'role' => $role,
            'content' => $content,
        ];

        return $this;
    }
}
