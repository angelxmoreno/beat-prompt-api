<?php
declare(strict_types=1);

namespace App\Prompt\Canonicalizer;

use CakeInstructor\CakeInstructor;
use CakeInstructor\Contracts\StructuredExtractorInterface;
use CakeInstructor\Data\ExtractionRequest;
use RuntimeException;
use Throwable;

/**
 * Canonicalization entrypoint.
 * LLM extraction is required and fail-fast.
 */
final class Canonicalizer
{
    private const string LLM_SYSTEM = <<<'TXT'
You extract canonical intent for music prompts.

Return only these structured fields:
- kind: "artist_style_prompt" or "general_prompt"
- artists: array of artist/person names
- target: concise requested artifact label
- modifiers: array of semantic style descriptors

Definitions:
- artists: referenced artist/person names in canonical form (lowercase, no punctuation).
- target: requested artifact from user intent (examples: beat, song, instrumental, loop, stem pack).
- modifiers: only semantic style descriptors (examples: dark, cinematic, aggressive, melodic, sparse, upbeat).

Never include these scaffolding/function words in modifiers:
type, beat, beats, instrumental, instrumentals, style, like, sounds, with, that, in, the, of.

If the phrase is only artist-style scaffolding, modifiers must be [].
Only use kind="artist_style_prompt" when at least one explicit artist/person name is present in the input text.
Do not infer or hallucinate artist names.
For artist-style prompts, keep modifiers empty unless true non-artist descriptors
are explicitly present (for example dark, cinematic, aggressive).
Words like "vibe" or "vibes" are scaffolding and should not be included in modifiers.
If the user explicitly asks for an instrumental, target must be "instrumental".

Examples:
Input: "Joyner Lucas type beat"
Output: {"kind":"artist_style_prompt","artists":["joyner lucas"],"target":"beat","modifiers":[]}

Input: "a beat with dark cinematic vibes like Joyner Lucas"
Output: {"kind":"artist_style_prompt","artists":["joyner lucas"],"target":"beat",
"modifiers":["dark","cinematic"]}

Input: "a beat with vibes like Joyner Lucas"
Output: {"kind":"artist_style_prompt","artists":["joyner lucas"],"target":"beat","modifiers":[]}

Input: "give me an instrumental in Joyner Lucas style"
Output: {"kind":"artist_style_prompt","artists":["joyner lucas"],"target":"instrumental","modifiers":[]}

Input: "Joyner Lucas x J. Cole type beat"
Output: {"kind":"artist_style_prompt","artists":["joyner lucas","j cole"],"target":"beat","modifiers":[]}

Input: "dark cinematic 94 bpm boom bap beat"
Output: {"kind":"general_prompt","artists":[],"target":"beat","modifiers":["dark","cinematic","boom bap"]}

If uncertain about artist reference, prefer general_prompt.
TXT;

    private CanonicalKeySerializer $keySerializer;

    private CanonicalResponseMapper $responseMapper;

    private ?StructuredExtractorInterface $extractor;

    /**
     * @param \App\Prompt\Canonicalizer\CanonicalKeySerializer|null $keySerializer Serializer for canonical cache keys.
     * @param \App\Prompt\Canonicalizer\CanonicalResponseMapper|null $responseMapper Mapper from raw extraction output to request DTO.
     * @param \CakeInstructor\Contracts\StructuredExtractorInterface|null $extractor Optional injected extractor (mainly for tests).
     * @param string|null $connectionName Optional CakeInstructor connection override.
     */
    public function __construct(
        ?CanonicalKeySerializer $keySerializer = null,
        ?CanonicalResponseMapper $responseMapper = null,
        ?StructuredExtractorInterface $extractor = null,
        private readonly ?string $connectionName = null,
    ) {
        $this->keySerializer = $keySerializer ?? new CanonicalKeySerializer();
        $this->responseMapper = $responseMapper ?? new CanonicalResponseMapper();
        $this->extractor = $extractor;
    }

    /**
     * Canonicalize user input into a structured request using LLM extraction.
     */
    public function canonicalize(string $input): CanonicalRequest
    {
        $normalized = $this->normalizeInput($input);
        if ($normalized === '') {
            throw new RuntimeException('Canonicalization failed: Input is empty after normalization.');
        }

        try {
            $raw = $this->extractor()->extract($this->buildLlmReq($normalized));
        } catch (Throwable $err) {
            throw new RuntimeException(
                message: 'LLM canonicalization failed: ' . $err->getMessage(),
                previous: $err,
            );
        }

        try {
            return $this->responseMapper->map($raw);
        } catch (Throwable $err) {
            throw new RuntimeException(
                message: 'LLM canonicalization failed: ' . $err->getMessage(),
                previous: $err,
            );
        }
    }

    /**
     * Produce a deterministic cache key for canonicalized input.
     */
    public function canonicalKey(string $input): string
    {
        return $this->keySerializer->serialize($this->canonicalize($input));
    }

    /**
     * Normalize case and whitespace before extraction.
     */
    private function normalizeInput(string $input): string
    {
        $normalized = strtolower(trim($input));
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

        return trim($normalized);
    }

    /**
     * Build the typed extraction request sent through CakeInstructor.
     */
    private function buildLlmReq(string $normalized): ExtractionRequest
    {
        return new ExtractionRequest(
            messages: [['role' => 'user', 'content' => $normalized]],
            responseModel: CanonicalExtractionResult::class,
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
