<?php
declare(strict_types=1);

namespace App\Prompt\Canonicalizer;

use RuntimeException;

/**
 * Maps raw structured extraction output into CanonicalRequest.
 */
final class CanonicalResponseMapper
{
    /** @var array<string, string> */
    private const array TARGET_ALIASES = [
        'beats' => 'beat',
        'instrumental' => 'beat',
        'instrumentals' => 'beat',
        'song' => 'song',
        'songs' => 'song',
    ];

    /** @var array<int, string> */
    private const array KINDS = ['artist_style_prompt', 'general_prompt'];

    /**
     * Map raw extracted payload into canonical request DTO.
     */
    public function map(mixed $raw): CanonicalRequest
    {
        $kind = $this->cleanKind($this->readString($raw, 'kind'));
        $artists = $this->cleanArtists($this->readList($raw, 'artists'));
        $target = $this->cleanTarget($this->readString($raw, 'target'));
        $mods = $this->cleanMods($this->readList($raw, 'modifiers'));

        if ($kind === '' && $artists === [] && $mods === []) {
            throw new RuntimeException('LLM returned empty or invalid canonical response.');
        }

        if ($artists !== []) {
            $kind = 'artist_style_prompt';
        }

        if ($kind === '') {
            $kind = 'general_prompt';
        }

        return new CanonicalRequest(
            kind: $kind,
            artists: $artists,
            target: $target,
            modifiers: $mods,
            source: 'llm',
        );
    }

    /**
     * Read a scalar string field from object/array payload.
     */
    private function readString(mixed $raw, string $key): string
    {
        if (is_array($raw) && isset($raw[$key]) && is_scalar($raw[$key])) {
            return (string)$raw[$key];
        }

        if (is_object($raw) && isset($raw->{$key}) && is_scalar($raw->{$key})) {
            return (string)$raw->{$key};
        }

        return '';
    }

    /**
     * @return array<int, string>
     */
    private function readList(mixed $raw, string $key): array
    {
        if (is_array($raw) && isset($raw[$key]) && is_array($raw[$key])) {
            return $this->toStringList($raw[$key]);
        }

        if (is_object($raw) && isset($raw->{$key}) && is_array($raw->{$key})) {
            return $this->toStringList($raw->{$key});
        }

        return [];
    }

    /**
     * @param array<int, mixed> $items
     * @return array<int, string>
     */
    private function toStringList(array $items): array
    {
        $out = [];
        foreach ($items as $item) {
            if (is_scalar($item)) {
                $out[] = (string)$item;
            }
        }

        return $out;
    }

    /**
     * Normalize kind enum-like value.
     */
    private function cleanKind(string $kind): string
    {
        $kind = trim(strtolower($kind));

        return in_array($kind, self::KINDS, true) ? $kind : '';
    }

    /**
     * Normalize target and alias values.
     */
    private function cleanTarget(string $target): string
    {
        $target = trim(strtolower($target));

        if ($target === '') {
            return 'beat';
        }

        if (isset(self::TARGET_ALIASES[$target])) {
            return self::TARGET_ALIASES[$target];
        }

        return $target === 'song' ? 'song' : 'beat';
    }

    /**
     * @param array<int, string> $artists
     * @return array<int, string>
     */
    private function cleanArtists(array $artists): array
    {
        $out = array_map($this->normalizeArtist(...), $artists);
        $out = array_values(array_filter($out, static fn(string $item): bool => $item !== ''));

        return array_values(array_unique($out));
    }

    /**
     * @param array<int, string> $mods
     * @return array<int, string>
     */
    private function cleanMods(array $mods): array
    {
        $out = [];
        foreach ($mods as $mod) {
            $value = $this->normalizeModifier($mod);
            if ($value === '') {
                continue;
            }
            $out[] = $value;
        }

        return array_values(array_unique($out));
    }

    /**
     * Minimal structural normalization for modifiers.
     */
    private function normalizeModifier(string $text): string
    {
        $text = preg_replace('/[^a-z0-9\s]/', ' ', strtolower($text)) ?? $text;
        $text = preg_replace('/\s+/', ' ', trim($text)) ?? $text;

        return $text;
    }

    /**
     * Normalize artist string for stable matching and key generation.
     */
    private function normalizeArtist(string $artist): string
    {
        $artist = preg_replace('/[^a-z0-9\s]/', '', strtolower($artist)) ?? $artist;
        $artist = preg_replace('/\s+/', ' ', trim($artist)) ?? $artist;

        return trim($artist);
    }
}
