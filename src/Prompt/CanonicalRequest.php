<?php
declare(strict_types=1);

namespace App\Prompt;

/**
 * Immutable canonical request produced by phase-0 normalization.
 */
final readonly class CanonicalRequest
{
    /**
     * @param array<int, string> $artists
     * @param array<int, string> $modifiers
     */
    public function __construct(
        public string $kind,
        public array $artists,
        public string $target,
        public array $modifiers,
    ) {
    }
}
