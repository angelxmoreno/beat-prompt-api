<?php
declare(strict_types=1);

namespace CakeInstructor\Support;

final class ConnectionConfigValidator
{
    /**
     * @param array<string,mixed> $config
     */
    public function __construct(
        private readonly array $config,
    ) {
    }

    /**
     * @return array<string,mixed>
     */
    public function validate(?string $connectionName = null): array
    {
        $default = $this->defaultConnection();
        $connections = $this->connections();
        $selected = $connectionName !== null
            ? [$connectionName => $connections[$connectionName] ?? null]
            : $connections;

        $results = [];
        foreach ($selected as $name => $config) {
            $results[] = $this->validateConnection((string)$name, $config);
        }

        return [
            'defaultConnection' => $default,
            'globalErrors' => $this->globalErrors($default, $connections),
            'connections' => $results,
            'valid' => $this->allValid($results, $default, $connections),
        ];
    }

    /**
     * @return array<int,string>
     */
    public function availableConnections(): array
    {
        return array_keys($this->connections());
    }

    /**
     * Resolve the configured default connection name.
     */
    public function defaultConnection(): string
    {
        $default = $this->config['default_connection'] ?? 'default';

        return is_string($default) && $default !== '' ? $default : 'default';
    }

    /**
     * @param mixed $config
     * @return array<string,mixed>
     */
    private function validateConnection(string $connectionName, mixed $config): array
    {
        if (!is_array($config)) {
            return [
                'connection' => $connectionName,
                'driver' => 'unknown',
                'model' => 'unknown',
                'valid' => false,
                'errors' => ['Connection is not defined in CakeInstructor.connections.'],
                'warnings' => [],
            ];
        }

        $driver = $this->stringOrUnknown($config['driver'] ?? null);
        $model = $this->stringOrUnknown($config['model'] ?? null);
        $errors = [];
        $warnings = [];

        if ($driver === 'unknown') {
            $errors[] = 'Driver is blank.';
        }

        if ($model === 'unknown') {
            $errors[] = 'Model is blank.';
        }

        foreach ($this->requiredFieldsFor($driver) as $field => $message) {
            $value = $config[$field] ?? null;
            if (!is_string($value) || trim($value) === '') {
                $errors[] = $message;
            }
        }

        if ($driver !== 'unknown' && !$this->isKnownDriver($driver)) {
            $warnings[] = sprintf(
                'Driver "%s" is not recognized by the validator; provider-specific checks were skipped.',
                $driver,
            );
        }

        return [
            'connection' => $connectionName,
            'driver' => $driver,
            'model' => $model,
            'valid' => $errors === [],
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * @param string $driver
     * @return array<string,string>
     */
    private function requiredFieldsFor(string $driver): array
    {
        return match ($driver) {
            'anthropic' => [
                'apiUrl' => 'Anthropic apiUrl is blank.',
                'endpoint' => 'Anthropic endpoint is blank.',
                'apiKey' => 'Anthropic apiKey is blank.',
            ],
            'gemini' => [
                'apiUrl' => 'Gemini apiUrl is blank.',
                'apiKey' => 'Gemini apiKey is blank.',
            ],
            'ollama' => [
                'apiUrl' => 'Ollama apiUrl is blank.',
                'endpoint' => 'Ollama endpoint is blank.',
            ],
            'openai' => [
                'apiUrl' => 'OpenAI apiUrl is blank.',
                'endpoint' => 'OpenAI endpoint is blank.',
                'apiKey' => 'OpenAI apiKey is blank.',
            ],
            'openai-compatible' => [
                'apiUrl' => 'OpenAI-compatible apiUrl is blank.',
                'endpoint' => 'OpenAI-compatible endpoint is blank.',
            ],
            'openai-responses' => [
                'apiUrl' => 'OpenAI Responses apiUrl is blank.',
                'endpoint' => 'OpenAI Responses endpoint is blank.',
                'apiKey' => 'OpenAI Responses apiKey is blank.',
            ],
            default => [],
        };
    }

    /**
     * Determine whether a driver has built-in validation rules.
     */
    private function isKnownDriver(string $driver): bool
    {
        return in_array($driver, [
            'anthropic',
            'gemini',
            'ollama',
            'openai',
            'openai-compatible',
            'openai-responses',
        ], true);
    }

    /**
     * @param array<string,array<string,mixed>> $connections
     * @return array<int,string>
     */
    private function globalErrors(string $default, array $connections): array
    {
        if (array_key_exists($default, $connections)) {
            return [];
        }

        return [sprintf('default_connection "%s" is not defined in CakeInstructor.connections.', $default)];
    }

    /**
     * @param array<int,array<string,mixed>> $results
     * @param array<string,array<string,mixed>> $connections
     */
    private function allValid(array $results, string $default, array $connections): bool
    {
        if (!array_key_exists($default, $connections)) {
            return false;
        }

        foreach ($results as $result) {
            if (($result['valid'] ?? false) !== true) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    private function connections(): array
    {
        $connections = $this->config['connections'] ?? [];
        if (!is_array($connections)) {
            return [];
        }

        $filtered = [];
        foreach ($connections as $name => $config) {
            if (!is_string($name)) {
                continue;
            }
            $filtered[$name] = is_array($config) ? $config : [];
        }

        return $filtered;
    }

    /**
     * Normalize optional string config values for summaries.
     */
    private function stringOrUnknown(mixed $value): string
    {
        if (!is_string($value)) {
            return 'unknown';
        }

        $normalized = trim($value);

        return $normalized !== '' ? $normalized : 'unknown';
    }
}
