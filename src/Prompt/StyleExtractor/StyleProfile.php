<?php
declare(strict_types=1);

namespace App\Prompt\StyleExtractor;

/**
 * Immutable style profile produced by phase-1 extraction.
 */
final readonly class StyleProfile
{
    /**
     * @param array<int, string> $mood
     * @param array<int, string> $instruments
     * @param array<int, string> $rhythmTraits
     * @param array<int, string> $productionTraits
     */
    public function __construct(
        public string $genre,
        public array $mood,
        public string $energy,
        public int $tempoBpm,
        public array $instruments,
        public array $rhythmTraits,
        public array $productionTraits,
        public string $source = 'llm',
    ) {
    }
}
