<?php
declare(strict_types=1);

namespace App\Prompt\StyleExtractor;

use App\Prompt\Canonicalizer\CanonicalKeySerializer;
use App\Prompt\Canonicalizer\CanonicalRequest;
use Throwable;

/**
 * Loads and evaluates style-extraction benchmark cases.
 */
final class StyleExtractComparisonService
{
    private const array EXPECTED_KEYS = [
        'genre',
        'mood',
        'energy',
        'tempoBpm',
        'instruments',
        'rhythmTraits',
        'productionTraits',
        'source',
    ];

    /**
     * @param \App\Prompt\StyleExtractor\StyleExtractor $extractor Style extraction entrypoint.
     * @param \App\Prompt\Canonicalizer\CanonicalKeySerializer $keySerializer Canonical key serializer.
     */
    public function __construct(
        private readonly StyleExtractor $extractor,
        private readonly CanonicalKeySerializer $keySerializer,
        private readonly ?StyleExtractCaseLoader $caseLoader = null,
    ) {
    }

    /**
     * @return array<int, array{canonical:array<string,mixed>,expected:array<string,mixed>}>
     */
    public function loadCases(?string $inlineJson, ?string $filePath): array
    {
        return $this->caseLoader()->loadCases($inlineJson, $filePath);
    }

    /**
     * @param array<int, array{canonical:array<string,mixed>,expected:array<string,mixed>}> $cases
     * @return array{status:string,passed:int,failed:int,total:int,results:array<int,array<string,mixed>>}
     */
    public function run(array $cases): array
    {
        $results = [];
        $passCount = 0;

        foreach ($cases as $index => $case) {
            $result = $this->evaluateCase($case, $index + 1);
            $results[] = $result;
            if ($result['pass'] === true) {
                $passCount++;
            }
        }

        $total = count($results);
        $failCount = $total - $passCount;

        return [
            'status' => $failCount === 0 ? 'pass' : 'fail',
            'passed' => $passCount,
            'failed' => $failCount,
            'total' => $total,
            'results' => $results,
        ];
    }

    /**
     * @param array<string,mixed> $case
     * @return array<string,mixed>
     */
    private function evaluateCase(array $case, int $caseNumber): array
    {
        /** @var array<string,mixed> $canonicalRaw */
        $canonicalRaw = $case['canonical'];
        /** @var array<string,mixed> $expected */
        $expected = $case['expected'];
        $inputLabel = $this->caseLabel($canonicalRaw);

        $result = [
            'case' => $caseNumber,
            'input' => $inputLabel,
            'pass' => false,
            'actual' => null,
            'error' => null,
            'mismatches' => [],
        ];

        try {
            $canonical = $this->toCanonicalRequest($canonicalRaw);
            $canonicalKey = $this->keySerializer->serialize($canonical);
            $profile = $this->extractor->extract($canonical, $canonicalKey);
            $actual = [
                'genre' => $profile->genre,
                'mood' => $this->sortedStrings($profile->mood),
                'energy' => $profile->energy,
                'tempoBpm' => $profile->tempoBpm,
                'instruments' => $this->sortedStrings($profile->instruments),
                'rhythmTraits' => $this->sortedStrings($profile->rhythmTraits),
                'productionTraits' => $this->sortedStrings($profile->productionTraits),
                'source' => $profile->source,
            ];
            $result['actual'] = $actual;
            $mismatches = $this->compareExpected($expected, $actual, null);
            $result['mismatches'] = $mismatches;
            $result['pass'] = $mismatches === [];

            return $result;
        } catch (Throwable $err) {
            $message = $err->getMessage();
            $result['error'] = [
                'class' => $err::class,
                'message' => $message,
            ];
            $mismatches = $this->compareExpected($expected, null, $message);
            $result['mismatches'] = $mismatches;
            $result['pass'] = $mismatches === [];

            return $result;
        }
    }

    /**
     * @param array<string,mixed> $canonical
     */
    private function caseLabel(array $canonical): string
    {
        $artists = is_array($canonical['artists'] ?? null)
            ? implode(',', $this->sortedStrings((array)$canonical['artists']))
            : '';

        return sprintf(
            'kind=%s artists=%s target=%s modifiers=%s',
            (string)($canonical['kind'] ?? ''),
            $artists,
            (string)($canonical['target'] ?? ''),
            implode(',', $this->sortedStrings((array)($canonical['modifiers'] ?? []))),
        );
    }

    /**
     * @param array<string,mixed> $raw
     */
    private function toCanonicalRequest(array $raw): CanonicalRequest
    {
        $kind = is_string($raw['kind'] ?? null) ? (string)$raw['kind'] : 'general_prompt';
        $target = is_string($raw['target'] ?? null) ? (string)$raw['target'] : 'beat';
        $artists = is_array($raw['artists'] ?? null) ? $this->sortedStrings((array)$raw['artists']) : [];
        $modifiers = is_array($raw['modifiers'] ?? null) ? $this->sortedStrings((array)$raw['modifiers']) : [];

        return new CanonicalRequest(
            kind: $kind,
            artists: $artists,
            target: $target,
            modifiers: $modifiers,
            source: 'llm',
        );
    }

    /**
     * @param array<string,mixed> $expected
     * @param array<string,mixed>|null $actual
     * @return array<int,string>
     */
    private function compareExpected(array $expected, ?array $actual, ?string $error): array
    {
        if ($this->hasErrorExpectation($expected)) {
            return $this->compareErrorExpectation((string)$expected['errorContains'], $error);
        }

        if ($actual === null) {
            return ['Expected successful style extraction, got error.'];
        }

        $mismatches = [];
        foreach (self::EXPECTED_KEYS as $key) {
            if (!array_key_exists($key, $expected)) {
                continue;
            }

            $expectedValue = $expected[$key];
            $actualValue = $actual[$key] ?? null;
            if (
                in_array($key, ['mood', 'instruments', 'rhythmTraits', 'productionTraits'], true)
                && is_array($expectedValue)
            ) {
                $expectedValue = $this->sortedStrings($expectedValue);
            }

            if ($expectedValue === $actualValue) {
                continue;
            }

            $mismatches[] = sprintf(
                '%s mismatch: expected %s, got %s',
                $key,
                $this->encodeValue($expectedValue),
                $this->encodeValue($actualValue),
            );
        }

        return $mismatches;
    }

    /**
     * @param array<string,mixed> $expected
     */
    private function hasErrorExpectation(array $expected): bool
    {
        return is_string($expected['errorContains'] ?? null) && $expected['errorContains'] !== '';
    }

    /**
     * @return array<int,string>
     */
    private function compareErrorExpectation(string $expectedFragment, ?string $error): array
    {
        if (is_string($error) && str_contains($error, $expectedFragment)) {
            return [];
        }

        return [sprintf('Expected error containing "%s".', $expectedFragment)];
    }

    /**
     * @param array<int,mixed> $values
     * @return array<int,string>
     */
    private function sortedStrings(array $values): array
    {
        $strings = [];
        foreach ($values as $value) {
            if (!is_scalar($value)) {
                continue;
            }

            $normalized = trim(strtolower((string)$value));
            if ($normalized !== '') {
                $strings[] = $normalized;
            }
        }

        $strings = array_values(array_unique($strings));
        sort($strings, SORT_STRING);

        return $strings;
    }

    /**
     * Encode values for mismatch output rendering.
     */
    private function encodeValue(mixed $value): string
    {
        $encoded = json_encode($value, JSON_UNESCAPED_SLASHES);

        return is_string($encoded) ? $encoded : 'null';
    }

    /**
     * Resolve case loader, creating default loader when not injected.
     */
    private function caseLoader(): StyleExtractCaseLoader
    {
        return $this->caseLoader ?? new StyleExtractCaseLoader();
    }
}
