<?php
declare(strict_types=1);

namespace App\Prompt\Canonicalizer;

use InvalidArgumentException;
use JsonException;
use Throwable;

/**
 * Loads and evaluates canonicalization benchmark cases.
 */
final class CanonicalizeComparisonService
{
    private const array EXPECTED_KEYS = ['kind', 'artists', 'target', 'modifiers', 'source', 'canonicalKey'];

    /**
     * @param \App\Prompt\Canonicalizer\Canonicalizer $canonicalizer Canonicalization entrypoint.
     * @param \App\Prompt\Canonicalizer\CanonicalKeySerializer $serializer Canonical key serializer.
     */
    public function __construct(
        private readonly Canonicalizer $canonicalizer,
        private readonly CanonicalKeySerializer $serializer,
    ) {
    }

    /**
     * @return array<int, array{input:string,expected:array<string,mixed>}>
     */
    public function loadCases(?string $inlineJson, ?string $filePath): array
    {
        $source = $this->resolveSource($inlineJson, $filePath);
        $json = $this->readSourcePayload($source);
        $decoded = $this->decodeCases($json);

        return $this->validateCases($decoded);
    }

    /**
     * @param array<int, array{input:string,expected:array<string,mixed>}> $cases
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
     * @param array<string, mixed> $case
     * @return array<string, mixed>
     */
    private function evaluateCase(array $case, int $caseNumber): array
    {
        $input = (string)$case['input'];
        /** @var array<string, mixed> $expected */
        $expected = $case['expected'];

        $result = [
            'case' => $caseNumber,
            'input' => $input,
            'pass' => false,
            'actual' => null,
            'error' => null,
            'mismatches' => [],
        ];

        try {
            $request = $this->canonicalizer->canonicalize($input);
            $actual = [
                'kind' => $request->kind,
                'artists' => $this->sortedStrings($request->artists),
                'target' => $request->target,
                'modifiers' => $this->sortedStrings($request->modifiers),
                'source' => $request->source,
                'canonicalKey' => $this->serializer->serialize($request),
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
     * @param array<string,mixed> $expected
     * @param array<string,mixed>|null $actual
     * @return array<int,string>
     */
    private function compareExpected(array $expected, ?array $actual, ?string $error): array
    {
        if ($this->hasErrorExpectation($expected)) {
            $expectedFragment = (string)$expected['errorContains'];
            if (is_string($error) && str_contains($error, $expectedFragment)) {
                return [];
            }

            return [sprintf('Expected error containing "%s".', $expectedFragment)];
        }

        if ($actual === null) {
            return ['Expected successful canonicalization, got error.'];
        }

        return $this->compareExpectedFields($expected, $actual);
    }

    /**
     * @param array<string,mixed> $expected
     * @param array<string,mixed> $actual
     * @return array<int,string>
     */
    private function compareExpectedFields(array $expected, array $actual): array
    {
        $mismatches = [];

        foreach (self::EXPECTED_KEYS as $key) {
            if (!array_key_exists($key, $expected)) {
                continue;
            }

            $expectedValue = $expected[$key];
            $actualValue = $actual[$key] ?? null;

            if (in_array($key, ['artists', 'modifiers'], true) && is_array($expectedValue)) {
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
     * @param array{mode:string,payload:string} $source
     */
    private function readSourcePayload(array $source): string
    {
        if ($source['mode'] === 'inline') {
            return $source['payload'];
        }

        $payload = file_get_contents($source['payload']);

        return is_string($payload) ? $payload : '';
    }

    /**
     * @return array{mode:string,payload:string}
     */
    private function resolveSource(?string $inlineJson, ?string $filePath): array
    {
        $inline = is_string($inlineJson) && trim($inlineJson) !== '' ? $inlineJson : null;
        $file = is_string($filePath) && trim($filePath) !== '' ? $filePath : null;

        $sourceMode = ($inline !== null ? '1' : '0') . ($file !== null ? '1' : '0');

        return match ($sourceMode) {
            '00' => throw new InvalidArgumentException('Provide --cases-json or --file.'),
            '11' => throw new InvalidArgumentException('Use either --cases-json or --file, not both.'),
            '10' => ['mode' => 'inline', 'payload' => (string)$inline],
            default => $this->resolveFileSource((string)$file),
        };
    }

    /**
     * @return array{mode:string,payload:string}
     */
    private function resolveFileSource(string $file): array
    {
        if (!is_file($file)) {
            throw new InvalidArgumentException(sprintf('Case file not found: %s', $file));
        }

        return ['mode' => 'file', 'payload' => $file];
    }

    /**
     * @return array<int,mixed>
     */
    private function decodeCases(string $json): array
    {
        if (trim($json) === '') {
            throw new InvalidArgumentException('Case payload is empty.');
        }

        try {
            /** @var mixed $decoded */
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $err) {
            throw new InvalidArgumentException('Invalid JSON for cases: ' . $err->getMessage(), 0, $err);
        }

        if (!is_array($decoded)) {
            throw new InvalidArgumentException('Cases JSON must be an array.');
        }

        return $decoded;
    }

    /**
     * @param array<int,mixed> $decoded
     * @return array<int,array{input:string,expected:array<string,mixed>}>
     */
    private function validateCases(array $decoded): array
    {
        $cases = [];

        foreach ($decoded as $idx => $item) {
            if (!is_array($item)) {
                throw new InvalidArgumentException(sprintf('Case at index %d must be an object.', $idx));
            }

            $input = $item['input'] ?? null;
            $expected = $item['expected'] ?? null;

            if (!is_string($input) || trim($input) === '') {
                throw new InvalidArgumentException(sprintf('Case at index %d requires non-empty string input.', $idx));
            }

            if (!is_array($expected)) {
                throw new InvalidArgumentException(sprintf('Case at index %d requires expected object.', $idx));
            }

            $cases[] = [
                'input' => $input,
                'expected' => $expected,
            ];
        }

        return $cases;
    }
}
