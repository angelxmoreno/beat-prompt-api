<?php
declare(strict_types=1);

namespace App\Command;

use App\Prompt\Canonicalizer\CanonicalizeComparisonService;
use App\Prompt\Canonicalizer\Canonicalizer;
use App\Prompt\Canonicalizer\CanonicalKeySerializer;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Throwable;

/**
 * Compare canonicalizer output against expected results across multiple cases.
 */
final class PromptCanonicalizeCompareCommand extends Command
{
    private const string DEFAULT_CASES_FILE = ROOT . DS . 'config' . DS . 'prompt-canonicalize-cases.json';

    /**
     * @inheritDoc
     */
    public static function getDescription(): string
    {
        return 'Run canonicalization comparison cases to benchmark connection/model quality.';
    }

    /**
     * @inheritDoc
     */
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser->addOption('cases-json', [
            'help' => 'Inline JSON array of cases.',
            'default' => null,
        ]);

        $parser->addOption('file', [
            'help' => 'Path to JSON file containing cases array. Defaults to config/prompt-canonicalize-cases.json.',
            'default' => null,
        ]);

        $parser->addOption('connection', [
            'help' => 'Optional CakeInstructor connection name.',
            'default' => null,
        ]);

        $parser->addOption('format', [
            'help' => 'Output format: text or json.',
            'choices' => ['text', 'json'],
            'default' => 'text',
        ]);

        return $parser;
    }

    /**
     * @inheritDoc
     */
    public function execute(Arguments $args, ConsoleIo $consoleIo): int
    {
        $format = (string)$args->getOption('format');
        $service = $this->service($args->getOption('connection'));
        $inlineCases = $this->optString($args->getOption('cases-json'));
        $caseFile = $this->optString($args->getOption('file'));
        if ($inlineCases === null && $caseFile === null) {
            $caseFile = self::DEFAULT_CASES_FILE;
        }

        try {
            $cases = $service->loadCases(
                $inlineCases,
                $caseFile,
            );
        } catch (Throwable $err) {
            $consoleIo->err('Case loading failed: ' . $err->getMessage());

            return static::CODE_ERROR;
        }

        $summary = $service->run($cases);

        if ($format === 'json') {
            $consoleIo->out((string)json_encode($summary, JSON_UNESCAPED_SLASHES));

            return $summary['failed'] === 0 ? static::CODE_SUCCESS : static::CODE_ERROR;
        }

        $this->renderText(
            $consoleIo,
            $summary['results'],
            $summary['passed'],
            $summary['failed'],
            $summary['total'],
            $this->optString($args->getOption('connection')),
        );

        return $summary['failed'] === 0 ? static::CODE_SUCCESS : static::CODE_ERROR;
    }

    /**
     * @param array<int, array<string, mixed>> $results
     */
    private function renderText(
        ConsoleIo $consoleIo,
        array $results,
        int $passCount,
        int $failCount,
        int $total,
        ?string $connection,
    ): void {
        $consoleIo->out('Connection: ' . ($connection ?? 'default'));

        foreach ($results as $item) {
            $prefix = $item['pass'] === true ? 'PASS' : 'FAIL';
            $consoleIo->out(sprintf('[%s] #%d %s', $prefix, (int)$item['case'], (string)$item['input']));

            /** @var array<int, string> $mismatches */
            $mismatches = is_array($item['mismatches']) ? $item['mismatches'] : [];
            foreach ($mismatches as $line) {
                $consoleIo->out('  - ' . $line);
            }

            if (is_array($item['error'])) {
                $message = (string)($item['error']['message'] ?? '');
                if ($message !== '') {
                    $consoleIo->out('  - error: ' . $message);
                }
            }
        }

        $consoleIo->out(sprintf('Summary: %d/%d passed, %d failed', $passCount, $total, $failCount));
    }

    /**
     * Build comparison service with optional connection override.
     */
    private function service(mixed $connection): CanonicalizeComparisonService
    {
        $connectionName = $this->optString($connection);

        return new CanonicalizeComparisonService(
            new Canonicalizer(connectionName: $connectionName),
            new CanonicalKeySerializer(),
        );
    }

    /**
     * Normalize optional CLI option values to nullable string.
     */
    private function optString(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
