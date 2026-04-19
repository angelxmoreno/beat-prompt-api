<?php
declare(strict_types=1);

namespace CakeInstructor\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\CommandFactoryInterface;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use CakeInstructor\Service\InstructorConnectionProbeService;

/**
 * Probes a CakeInstructor connection and reports actionable diagnostics.
 */
final class InstructorConnectionProbeCommand extends Command
{
    /**
     * @param \CakeInstructor\Service\InstructorConnectionProbeService $probeService Probe runtime service.
     * @param \Cake\Console\CommandFactoryInterface|null $factory Command factory instance.
     */
    public function __construct(
        private readonly InstructorConnectionProbeService $probeService,
        ?CommandFactoryInterface $factory = null,
    ) {
        parent::__construct($factory);
    }

    /**
     * @inheritDoc
     */
    public static function getDescription(): string
    {
        return 'Test a CakeInstructor connection and classify failures as config/provider/schema issues.';
    }

    /**
     * @inheritDoc
     */
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser->addOption('connection', [
            'help' => 'Optional CakeInstructor connection name (defaults to configured default_connection).',
            'default' => null,
        ]);

        $parser->addOption('format', [
            'help' => 'Output format: text or json.',
            'choices' => ['text', 'json'],
            'default' => 'text',
        ]);

        $parser->addOption('debug', [
            'help' => 'Include resolved connection config details with sensitive values masked.',
            'boolean' => true,
            'default' => false,
        ]);

        return $parser;
    }

    /**
     * @inheritDoc
     */
    public function execute(Arguments $args, ConsoleIo $consoleIo): int
    {
        $connection = $args->getOption('connection');
        $connection = is_string($connection) && $connection !== '' ? $connection : null;
        $format = (string)$args->getOption('format');
        $debug = (bool)$args->getOption('debug');
        if ($connection === null) {
            $connection = $this->chooseConnection($consoleIo);
        }
        $summary = $debug
            ? $this->probeService->probeSummaryWithDebug($connection)
            : $this->probeService->probeSummary($connection);
        $this->emit($consoleIo, $summary, $format);

        return ($summary['ok'] ?? false) === true || ($summary['type'] ?? '') === 'schema_error'
            ? static::CODE_SUCCESS
            : static::CODE_ERROR;
    }

    /**
     * @param array<string,mixed> $summary
     */
    private function emit(ConsoleIo $consoleIo, array $summary, string $format): void
    {
        if ($format === 'json') {
            $encoded = json_encode($summary, JSON_UNESCAPED_SLASHES);
            $consoleIo->out(is_string($encoded) ? $encoded : '{}');

            return;
        }

        $consoleIo->out('Connection: ' . (string)($summary['connection'] ?? 'default'));
        $consoleIo->out('Driver: ' . (string)($summary['driver'] ?? 'unknown'));
        $consoleIo->out('Model: ' . (string)($summary['model'] ?? 'unknown'));
        $consoleIo->out('OK: ' . ((bool)($summary['ok'] ?? false) ? 'true' : 'false'));
        $consoleIo->out('Type: ' . (string)($summary['type'] ?? 'unknown'));

        if (is_string($summary['errorClass'] ?? null) && $summary['errorClass'] !== '') {
            $consoleIo->out('ErrorClass: ' . (string)$summary['errorClass']);
        }

        $consoleIo->out('Message: ' . (string)($summary['message'] ?? ''));
        $consoleIo->out('Hint: ' . (string)($summary['hint'] ?? ''));

        if (is_array($summary['debug'] ?? null)) {
            $consoleIo->out('Debug:');
            foreach ($summary['debug'] as $key => $value) {
                $consoleIo->out(sprintf('  %s: %s', (string)$key, $this->stringifyDebugValue($value)));
            }
        }
    }

    /**
     * Interactively choose a configured connection when no option is provided.
     */
    private function chooseConnection(ConsoleIo $consoleIo): string
    {
        $default = $this->probeService->defaultConnection();
        $connections = $this->probeService->availableConnections();

        $consoleIo->out('Available CakeInstructor connections:');
        foreach ($connections as $name) {
            $suffix = $name === $default ? ' (default)' : '';
            $consoleIo->out('- ' . $name . $suffix);
        }

        return $consoleIo->askChoice('Select connection', $connections, $default);
    }

    /**
     * Render debug config values for CLI text output.
     */
    private function stringifyDebugValue(mixed $value): string
    {
        if (is_scalar($value) || $value === null) {
            return $value === null ? 'null' : (string)$value;
        }

        $encoded = json_encode($value, JSON_UNESCAPED_SLASHES);

        return is_string($encoded) ? $encoded : '[unserializable]';
    }
}
