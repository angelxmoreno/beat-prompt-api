<?php
declare(strict_types=1);

namespace App\Prompt\StyleExtractor;

use RuntimeException;

/**
 * Maps raw structured extraction output into StyleProfile.
 */
final class StyleProfileMapper
{
    /**
     * Map extracted payload into style profile DTO.
     */
    public function map(mixed $raw): StyleProfile
    {
        $genre = $this->cleanText($this->readString($raw, 'genre'));
        $energy = $this->cleanText($this->readString($raw, 'energy'));
        $tempoBpm = $this->cleanTempo($this->readInt($raw, 'tempoBpm', 'tempo_bpm'));
        $mood = $this->cleanList($this->readList($raw, 'mood'));
        $instruments = $this->cleanList($this->readList($raw, 'instruments'));
        $rhythmTraits = $this->cleanList($this->readList($raw, 'rhythmTraits', 'rhythm_traits'));
        $productionTraits = $this->cleanList($this->readList($raw, 'productionTraits', 'production_traits'));

        if ($genre === '' && $mood === [] && $instruments === [] && $rhythmTraits === [] && $productionTraits === []) {
            throw new RuntimeException('LLM returned empty or invalid style extraction response.');
        }

        if ($genre === '') {
            $genre = 'unknown';
        }
        if ($energy === '') {
            $energy = 'medium';
        }

        return new StyleProfile(
            genre: $genre,
            mood: $mood,
            energy: $energy,
            tempoBpm: $tempoBpm,
            instruments: $instruments,
            rhythmTraits: $rhythmTraits,
            productionTraits: $productionTraits,
            source: 'llm',
        );
    }

    /**
     * Read scalar string value from object/array payload.
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
    private function readList(mixed $raw, string ...$keys): array
    {
        foreach ($keys as $key) {
            if (is_array($raw) && isset($raw[$key]) && is_array($raw[$key])) {
                return $this->toStringList($raw[$key]);
            }

            if (is_object($raw) && isset($raw->{$key}) && is_array($raw->{$key})) {
                return $this->toStringList($raw->{$key});
            }
        }

        return [];
    }

    /**
     * Read first available numeric field from object/array payload.
     */
    private function readInt(mixed $raw, string ...$keys): int
    {
        foreach ($keys as $key) {
            if (is_array($raw) && isset($raw[$key]) && is_numeric($raw[$key])) {
                return (int)$raw[$key];
            }

            if (is_object($raw) && isset($raw->{$key}) && is_numeric($raw->{$key})) {
                return (int)$raw->{$key};
            }
        }

        return 90;
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
     * @param array<int, string> $values
     * @return array<int, string>
     */
    private function cleanList(array $values): array
    {
        $clean = [];
        foreach ($values as $value) {
            $normalized = $this->cleanText($value);
            if ($normalized !== '') {
                $clean[] = $normalized;
            }
        }

        return array_values(array_unique($clean));
    }

    /**
     * Normalize scalar text with structural cleanup.
     */
    private function cleanText(string $value): string
    {
        $value = preg_replace('/\s+/', ' ', trim(strtolower($value))) ?? $value;

        return trim($value);
    }

    /**
     * Normalize BPM to expected operating range.
     */
    private function cleanTempo(int $tempoBpm): int
    {
        if ($tempoBpm < 40 || $tempoBpm > 260) {
            return 90;
        }

        return $tempoBpm;
    }
}
