<?php
declare(strict_types=1);

namespace App\Prompt\StyleExtractor;

use App\Prompt\Canonicalizer\CanonicalRequest;
use CakeInstructor\CakeInstructor;
use CakeInstructor\Contracts\StructuredExtractorInterface;
use CakeInstructor\Data\ExtractionRequest;
use RuntimeException;
use Throwable;

/**
 * Phase-1 style extraction using LLM structured output.
 */
final class StyleExtractor
{
    private const string LLM_SYSTEM = <<<'TXT'
You extract structured music style attributes from a canonical request.

Return only these fields:
- genre: concise genre/style label
- mood: array of mood descriptors
- energy: one-word or short descriptor (low, medium, high, intense, etc.)
- tempoBpm: single integer BPM value
- instruments: array of prominent instruments/sounds
- rhythm_traits: array of groove/rhythm descriptors
- production_traits: array of production/mix descriptors

Rules:
- Use a single integer for tempoBpm (never a range, never text).
- Keep arrays concise and semantic.
- Use lowercase text values.
- Do not include artist names in output fields.
TXT;

    private StyleProfileMapper $mapper;

    private ?StructuredExtractorInterface $extractor;

    /**
     * @param \App\Prompt\StyleExtractor\StyleProfileMapper|null $mapper Mapper from structured output to profile DTO.
     * @param \CakeInstructor\Contracts\StructuredExtractorInterface|null $extractor Optional injected extractor for tests.
     * @param string|null $connectionName Optional CakeInstructor connection override.
     */
    public function __construct(
        ?StyleProfileMapper $mapper = null,
        ?StructuredExtractorInterface $extractor = null,
        private readonly ?string $connectionName = null,
    ) {
        $this->mapper = $mapper ?? new StyleProfileMapper();
        $this->extractor = $extractor;
    }

    /**
     * Extract style profile from canonical request.
     */
    public function extract(CanonicalRequest $canonical, string $canonicalKey): StyleProfile
    {
        try {
            $raw = $this->extractor()->extract($this->buildReq($canonical, $canonicalKey));
        } catch (Throwable $err) {
            throw new RuntimeException(
                message: 'Style extraction failed: ' . $err->getMessage(),
                previous: $err,
            );
        }

        try {
            $profile = $this->mapper->map($raw);
        } catch (Throwable $err) {
            throw new RuntimeException(
                message: 'Style extraction failed: ' . $err->getMessage(),
                previous: $err,
            );
        }

        return $profile;
    }

    /**
     * Build style extraction request payload.
     */
    private function buildReq(CanonicalRequest $canonical, string $canonicalKey): ExtractionRequest
    {
        $payload = json_encode([
            'canonicalKey' => $canonicalKey,
            'kind' => $canonical->kind,
            'artists' => $canonical->artists,
            'target' => $canonical->target,
            'modifiers' => $canonical->modifiers,
        ], JSON_UNESCAPED_SLASHES);

        return new ExtractionRequest(
            messages: [['role' => 'user', 'content' => is_string($payload) ? $payload : '{}']],
            responseModel: StyleExtractionResult::class,
            system: self::LLM_SYSTEM,
            options: ['temperature' => 0],
        );
    }

    /**
     * Resolve extractor, creating one from CakeInstructor when not injected.
     */
    private function extractor(): StructuredExtractorInterface
    {
        if ($this->extractor instanceof StructuredExtractorInterface) {
            return $this->extractor;
        }

        $sdk = new CakeInstructor();
        $this->extractor = $sdk->createExtractor(connectionName: $this->connectionName);

        return $this->extractor;
    }
}
