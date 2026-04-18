<?php
declare(strict_types=1);

namespace App\Command;

use App\Prompt\Canonicalizer\Canonicalizer;
use App\Prompt\Canonicalizer\CanonicalKeySerializer;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use JsonException;
use Throwable;

final class PromptCanonicalizeCommand extends Command
{
    private ?CanonicalKeySerializer $keySerializer = null;

    /**
     * @inheritDoc
     */
    public static function getDescription(): string
    {
        return 'Canonicalize prompt input and print canonical fields + cache key.';
    }

    /**
     * @inheritDoc
     */
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser->addArgument('text', [
            'help' => 'Raw prompt text to canonicalize.',
            'required' => true,
        ]);

        $parser->addOption('format', [
            'help' => 'Output format: text or json.',
            'choices' => ['text', 'json'],
            'default' => 'text',
        ]);

        $parser->addOption('connection', [
            'help' => 'Optional CakeInstructor connection name.',
            'default' => null,
        ]);

        return $parser;
    }

    /**
     * @inheritDoc
     */
    public function execute(Arguments $args, ConsoleIo $consoleIo): int
    {
        $input = (string)$args->getArgument('text');
        $format = (string)$args->getOption('format');
        $connection = $args->getOption('connection');
        $connection = is_string($connection) && $connection !== '' ? $connection : null;
        try {
            $request = $this->canonicalizer($connection)->canonicalize($input);
            $key = $this->serializer()->serialize($request);
        } catch (Throwable $err) {
            $prev = $err->getPrevious();
            if (!$prev instanceof Throwable) {
                $consoleIo->err('Canonicalization failed: ' . $err->getMessage());

                return static::CODE_ERROR;
            }

            $consoleIo->err(sprintf(
                'Canonicalization failed: %s (%s)',
                $err->getMessage(),
                $prev::class,
            ));

            return static::CODE_ERROR;
        }

        if ($format === 'json') {
            try {
                $payload = json_encode([
                    'input' => $input,
                    'mode' => 'llm',
                    'canonical' => [
                        'kind' => $request->kind,
                        'artists' => $request->artists,
                        'target' => $request->target,
                        'modifiers' => $request->modifiers,
                        'source' => $request->source,
                    ],
                    'canonicalKey' => $key,
                ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            } catch (JsonException $err) {
                $consoleIo->err('Canonicalization failed: Unable to encode JSON output: ' . $err->getMessage());

                return static::CODE_ERROR;
            }

            $consoleIo->out($payload);

            return static::CODE_SUCCESS;
        }

        $consoleIo->out('Input: ' . $input);
        $consoleIo->out('Mode: llm');
        $consoleIo->out('Source: ' . $request->source);
        $consoleIo->out('Kind: ' . $request->kind);
        $consoleIo->out('Artists: ' . implode(', ', $request->artists));
        $consoleIo->out('Target: ' . $request->target);
        $consoleIo->out('Modifiers: ' . implode(', ', $request->modifiers));
        $consoleIo->out('CanonicalKey: ' . $key);

        return static::CODE_SUCCESS;
    }

    /**
     * Build a canonicalizer bound to the optional connection override.
     */
    private function canonicalizer(?string $connection): Canonicalizer
    {
        return new Canonicalizer(connectionName: $connection);
    }

    /**
     * Lazily initialize the deterministic key serializer.
     */
    private function serializer(): CanonicalKeySerializer
    {
        return $this->keySerializer ??= new CanonicalKeySerializer();
    }
}
