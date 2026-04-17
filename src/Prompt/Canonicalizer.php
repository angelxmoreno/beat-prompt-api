<?php
declare(strict_types=1);

namespace App\Prompt;

/**
 * Phase-0 rules-first canonicalization and intent resolution service.
 */
final class Canonicalizer
{
    /** @var array<string, string> */
    private const array TARGET_ALIASES = [
        'beats' => 'beat',
        'instrumental' => 'beat',
        'instrumentals' => 'beat',
        'song' => 'song',
        'songs' => 'song',
    ];

    /**
     * @param \App\Prompt\CanonicalKeySerializer $keySerializer
     */
    public function __construct(private readonly CanonicalKeySerializer $keySerializer = new CanonicalKeySerializer())
    {
    }

    /**
     * Canonicalize free-form user text into a normalized request object.
     */
    public function canonicalize(string $input): CanonicalRequest
    {
        $normalized = $this->normalizeInput($input);
        $artists = $this->extractArtists($normalized);

        $target = $this->extractTarget($normalized);
        $modifiers = $this->extractModifiers($normalized, $target, $artists);

        $kind = $artists !== [] ? 'artist_style_prompt' : 'general_prompt';

        return new CanonicalRequest(
            kind: $kind,
            artists: $artists,
            target: $target,
            modifiers: $modifiers,
        );
    }

    /**
     * Build a deterministic canonical cache key from user input.
     */
    public function canonicalKey(string $input): string
    {
        return $this->keySerializer->serialize($this->canonicalize($input));
    }

    /**
     * Normalize case and whitespace for rule-based parsing.
     */
    private function normalizeInput(string $input): string
    {
        $normalized = strtolower(trim($input));

        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

        return trim($normalized);
    }

    /**
     * @return array<int, string>
     */
    private function extractArtists(string $normalized): array
    {
        $artists = [];

        if (preg_match('/^(.+?)\s+type\s+beat(?:\s|$)/', $normalized, $matches) === 1) {
            $artists[] = $this->normalizeArtist($matches[1]);
        }

        if (preg_match('/^beats\s+like\s+(.+)$/', $normalized, $matches) === 1) {
            $artists[] = $this->normalizeArtist($matches[1]);
        }

        if (preg_match('/^(.+?)\s+style\s+beat(?:\s|$)/', $normalized, $matches) === 1) {
            $artists[] = $this->normalizeArtist($matches[1]);
        }

        if (preg_match('/in\s+the\s+style\s+of\s+(.+)$/', $normalized, $matches) === 1) {
            $artists[] = $this->normalizeArtist($matches[1]);
        }

        $artists = array_filter($artists, static fn(string $artist): bool => $artist !== '');
        $artists = array_values(array_unique($artists));

        return $artists;
    }

    /**
     * Resolve target alias tokens to canonical targets.
     */
    private function extractTarget(string $normalized): string
    {
        foreach (self::TARGET_ALIASES as $alias => $target) {
            if (str_contains($normalized, $alias)) {
                return $target;
            }
        }

        return 'beat';
    }

    /**
     * @param array<int, string> $artists
     * @return array<int, string>
     */
    private function extractModifiers(string $normalized, string $target, array $artists): array
    {
        $working = str_replace(['type beat', 'beats like', 'in the style of', 'style beat'], '', $normalized);
        $working = str_replace($target, '', $working);
        $working = str_replace('beat', '', $working);
        $working = str_replace(array_keys(self::TARGET_ALIASES), '', $working);

        foreach ($artists as $artist) {
            $working = str_replace($artist, '', $working);
        }

        $tokens = preg_split('/\s+/', trim($working)) ?: [];
        $tokens = array_values(array_filter($tokens, static function (string $token): bool {
            return $token !== '' && strlen($token) > 2;
        }));

        return array_values(array_unique($tokens));
    }

    /**
     * Normalize extracted artist values for stable matching and cache keys.
     */
    private function normalizeArtist(string $artist): string
    {
        $artist = preg_replace('/[^a-z0-9\s]/', '', $artist) ?? $artist;
        $artist = preg_replace('/\s+/', ' ', trim($artist)) ?? $artist;

        return trim($artist);
    }
}
