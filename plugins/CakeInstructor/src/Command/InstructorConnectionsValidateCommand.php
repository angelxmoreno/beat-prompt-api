<?php
declare(strict_types=1);

namespace CakeInstructor\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\CommandFactoryInterface;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use CakeInstructor\Support\ConnectionConfigValidator;

final class InstructorConnectionsValidateCommand extends Command
{
    /**
     * @param \CakeInstructor\Support\ConnectionConfigValidator $validator Connection config validator.
     * @param \Cake\Console\CommandFactoryInterface|null $factory Command factory instance.
     */
    public function __construct(
        private readonly ConnectionConfigValidator $validator,
        ?CommandFactoryInterface $factory = null,
    ) {
        parent::__construct($factory);
    }

    /**
     * @inheritDoc
     */
    public static function getDescription(): string
    {
        return 'Validate CakeInstructor connection config without making provider requests.';
    }

    /**
     * @inheritDoc
     */
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser->addOption('connection', [
            'help' => 'Optional CakeInstructor connection name. Omit to validate all connections.',
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
        $connection = $args->getOption('connection');
        $connection = is_string($connection) && $connection !== '' ? $connection : null;
        $format = (string)$args->getOption('format');
        $summary = $this->validator->validate($connection);

        if ($format === 'json') {
            $encoded = json_encode($summary, JSON_UNESCAPED_SLASHES);
            $consoleIo->out(is_string($encoded) ? $encoded : '{}');
        }
        if ($format !== 'json') {
            $this->emitText($consoleIo, $summary);
        }

        return ($summary['valid'] ?? false) === true ? static::CODE_SUCCESS : static::CODE_ERROR;
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

        foreach ((array)$summary['connections'] as $result) {
            $status = ($result['valid'] ?? false) === true ? 'PASS' : 'FAIL';
            $consoleIo->out(sprintf(
                '[%s] %s (%s / %s)',
                $status,
                (string)$result['connection'],
                (string)$result['driver'],
                (string)$result['model'],
            ));

            foreach ((array)($result['errors'] ?? []) as $error) {
                $consoleIo->out('  - error: ' . (string)$error);
            }
            foreach ((array)($result['warnings'] ?? []) as $warning) {
                $consoleIo->out('  - warning: ' . (string)$warning);
            }
        }
    }
}
