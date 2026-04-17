<?php
declare(strict_types=1);

namespace App\Prompt\Canonicalizer;

/**
 * Structured model returned by the LLM canonicalization step.
 */
final readonly class CanonicalExtractionResult
{
    /**
     * @param array<int, string> $artists
     * @param array<int, string> $modifiers
     */
    public function __construct(
        public string $kind = 'general_prompt',
        public array $artists = [],
        public string $target = 'beat',
        public array $modifiers = [],
    ) {
    }
}
