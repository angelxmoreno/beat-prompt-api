<?php
declare(strict_types=1);

namespace App\Command;

use App\Prompt\Canonicalizer\CanonicalKeySerializer;
use App\Prompt\StyleExtractor\StyleExtractComparisonService;
use App\Prompt\StyleExtractor\StyleExtractor;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Throwable;

/**
 * Compare style extraction output against expected results across benchmark cases.
 */
final class PromptStyleExtractCompareCommand extends Command
{
    private const string DEFAULT_CASES_FILE = ROOT . DS . 'config' . DS . 'prompt-style-extract-cases.json';

    /**
     * @inheritDoc
     */
    public static function getDescription(): string
    {
        return 'Run style-extraction comparison cases to benchmark connection/model quality.';
    }

    /**
     * @inheritDoc
     */
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser->addOption('cases-json', [
            'help' => 'Inline JSON array of style extraction cases.',
            'default' => null,
        ]);

        $parser->addOption('file', [
            'help' => 'Path to JSON file containing style extraction cases. Defaults to '
                . 'config/prompt-style-extract-cases.json.',
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
            $cases = $service->loadCases($inlineCases, $caseFile);
        } catch (Throwable $err) {
            $consoleIo->err('Case loading failed: ' . $err->getMessage());

            return static::CODE_ERROR;
        }

        $summary = $service->run($cases);

        if ($format === 'json') {
            $payload = json_encode($summary, JSON_UNESCAPED_SLASHES);
            $consoleIo->out(is_string($payload) ? $payload : '{}');

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
                $class = (string)($item['error']['class'] ?? '');
                if ($class !== '') {
                    $consoleIo->out('  - errorClass: ' . $class);
                }
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
    private function service(mixed $connection): StyleExtractComparisonService
    {
        $connectionName = $this->optString($connection);

        return new StyleExtractComparisonService(
            new StyleExtractor(connectionName: $connectionName),
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
