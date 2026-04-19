<?php
declare(strict_types=1);

namespace App\Prompt\StyleExtractor;

use InvalidArgumentException;
use JsonException;

/**
 * Loads style extraction benchmark cases from inline JSON or a file.
 */
final class StyleExtractCaseLoader
{
    /**
     * @return array<int, array{canonical:array<string,mixed>,expected:array<string,mixed>}>
     */
    public function loadCases(?string $inlineJson, ?string $filePath): array
    {
        $source = $this->resolveSource($inlineJson, $filePath);
        $json = $this->readSourcePayload($source);
        $decoded = $this->decodeCases($json);

        return $this->validateCases($decoded);
    }

    /**
     * @param array{mode:string,payload:string} $source
     */
    private function readSourcePayload(array $source): string
    {
        if ($source['mode'] === 'inline') {
            return $source['payload'];
        }

        if (!is_readable($source['payload'])) {
            throw new InvalidArgumentException(sprintf('Unable to read case file: %s', $source['payload']));
        }

        $payload = file_get_contents($source['payload']);
        if ($payload === false) {
            throw new InvalidArgumentException(sprintf('Unable to read case file: %s', $source['payload']));
        }

        return $payload;
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
     * @return array<int,array{canonical:array<string,mixed>,expected:array<string,mixed>}>
     */
    private function validateCases(array $decoded): array
    {
        $cases = [];

        foreach ($decoded as $idx => $item) {
            if (!is_array($item)) {
                throw new InvalidArgumentException(sprintf('Case at index %d must be an object.', $idx));
            }

            $canonical = $item['canonical'] ?? null;
            $expected = $item['expected'] ?? null;

            if (!is_array($canonical)) {
                throw new InvalidArgumentException(sprintf('Case at index %d requires canonical object.', $idx));
            }

            if (!is_array($expected)) {
                throw new InvalidArgumentException(sprintf('Case at index %d requires expected object.', $idx));
            }

            $cases[] = [
                'canonical' => $canonical,
                'expected' => $expected,
            ];
        }

        return $cases;
    }
}
