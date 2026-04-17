<?php
declare(strict_types=1);

namespace CakeInstructor\Data;

use Cognesy\Messages\Message;
use Cognesy\Messages\Messages;

/**
 * @phpstan-type MessageInput string|array<int, array<string, mixed>>|\Cognesy\Messages\Message|\Cognesy\Messages\Messages
 */
final readonly class ExtractionRequest
{
    /**
     * @param \Cognesy\Messages\Message|\Cognesy\Messages\Messages|array<int, array<string, mixed>>|string $messages
     * @param object|array<string, mixed>|class-string $responseModel
     * @param array<int, mixed> $examples
     * @param array<string, mixed> $options
     */
    public function __construct(
        public string|array|Message|Messages $messages,
        public string|array|object $responseModel,
        public ?string $system = null,
        public ?string $prompt = null,
        public array $examples = [],
        public ?string $model = null,
        public array $options = [],
    ) {
    }

    /**
     * @param object|array<string, mixed>|class-string $responseModel
     * @param array<int, mixed> $examples
     * @param array<string, mixed> $options
     */
    public static function fromPrompt(
        string $prompt,
        string|array|object $responseModel,
        ?string $system = null,
        array $examples = [],
        ?string $model = null,
        array $options = [],
    ): self {
        return new self(
            messages: [['role' => 'user', 'content' => $prompt]],
            responseModel: $responseModel,
            system: $system,
            prompt: null,
            examples: $examples,
            model: $model,
            options: $options,
        );
    }
}
