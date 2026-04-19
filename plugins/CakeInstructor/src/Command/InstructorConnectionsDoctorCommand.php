<?php
declare(strict_types=1);

namespace CakeInstructor\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\CommandFactoryInterface;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use CakeInstructor\Service\InstructorConnectionProbeService;
use CakeInstructor\Support\ConnectionConfigValidator;

final class InstructorConnectionsDoctorCommand extends Command
{
    /**
     * @param \CakeInstructor\Support\ConnectionConfigValidator $validator Connection config validator.
     * @param \CakeInstructor\Service\InstructorConnectionProbeService $probeService Connection probe service.
     * @param \Cake\Console\CommandFactoryInterface|null $factory Command factory instance.
     */
    public function __construct(
        private readonly ConnectionConfigValidator $validator,
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
        return 'Validate and probe one or all CakeInstructor connections.';
    }

    /**
     * @inheritDoc
     */
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser->addOption('connection', [
            'help' => 'Optional CakeInstructor connection name. Omit to doctor all connections.',
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
        $validation = $this->validator->validate($connection);
        $results = $this->doctorResults((array)$validation['connections'], $debug);

        $summary = [
            'defaultConnection' => $validation['defaultConnection'],
            'globalErrors' => $validation['globalErrors'],
            'results' => $results,
            'ok' => (($validation['valid'] ?? false) === true)
                && $this->allDoctorResultsHealthy($results),
        ];

        if ($format === 'json') {
            $encoded = json_encode($summary, JSON_UNESCAPED_SLASHES);
            $consoleIo->out(is_string($encoded) ? $encoded : '{}');
        }
        if ($format !== 'json') {
            $this->emitText($consoleIo, $summary);
        }

        return $summary['ok'] === true ? static::CODE_SUCCESS : static::CODE_ERROR;
    }

    /**
     * @param array<int,array<string,mixed>> $connections
     * @return array<int,array<string,mixed>>
     */
    private function doctorResults(array $connections, bool $debug): array
    {
        $results = [];
        foreach ($connections as $validated) {
            $results[] = ($validated['valid'] ?? false) === true
                ? $this->probeResult($validated, $debug)
                : $this->configErrorResult($validated);
        }

        return $results;
    }

    /**
     * @param array<string,mixed> $summary
     */
    private function emitText(ConsoleIo $consoleIo, array $summary): void
    {
        $consoleIo->out('DefaultConnection: ' . (string)$summary['defaultConnection']);
        foreach ((array)$summary['globalErrors'] as $error) {
            $consoleIo->out('[ERROR] ' . (string)$error);
        }

        foreach ((array)$summary['results'] as $result) {
            $status = ($result['ok'] ?? false) === true ? 'PASS' : 'FAIL';
            $consoleIo->out(sprintf(
                '[%s] %s (%s / %s)',
                $status,
                (string)$result['connection'],
                (string)$result['driver'],
                (string)$result['model'],
            ));
            $consoleIo->out('  Type: ' . (string)$result['type']);
            $consoleIo->out('  Message: ' . (string)$result['message']);
            $consoleIo->out('  Hint: ' . (string)$result['hint']);
        }
    }

    /**
     * @param array<string,mixed> $validated
     * @return array<string,mixed>
     */
    private function configErrorResult(array $validated): array
    {
        return [
            'connection' => $validated['connection'],
            'driver' => $validated['driver'],
            'model' => $validated['model'],
            'ok' => false,
            'type' => 'config_error',
            'message' => implode(' ', (array)$validated['errors']),
            'hint' => 'Fix config validation errors before probing this connection.',
            'validation' => $validated,
        ];
    }

    /**
     * @param array<string,mixed> $validated
     * @return array<string,mixed>
     */
    private function probeResult(array $validated, bool $debug): array
    {
        $result = $debug
            ? $this->probeService->probeSummaryWithDebug((string)$validated['connection'])
            : $this->probeService->probeSummary((string)$validated['connection']);
        $result['validation'] = $validated;

        return $result;
    }

    /**
     * @param array<int,array<string,mixed>> $results
     */
    private function allDoctorResultsHealthy(array $results): bool
    {
        foreach ($results as $result) {
            if (($result['ok'] ?? false) === true) {
                continue;
            }
            if (($result['type'] ?? '') === 'schema_error') {
                continue;
            }

            return false;
        }

        return true;
    }
}
