<?php
declare(strict_types=1);

namespace App\Prompt\StyleExtractor;

/**
 * Structured model returned by the LLM style extraction step.
 */
final readonly class StyleExtractionResult
{
    /**
     * @param array<int, string> $mood
     * @param array<int, string> $instruments
     * @param array<int, string> $rhythmTraits
     * @param array<int, string> $productionTraits
     */
    public function __construct(
        public string $genre = '',
        public array $mood = [],
        public string $energy = '',
        public int $tempoBpm = 90,
        public array $instruments = [],
        public array $rhythmTraits = [],
        public array $productionTraits = [],
    ) {
    }
}
