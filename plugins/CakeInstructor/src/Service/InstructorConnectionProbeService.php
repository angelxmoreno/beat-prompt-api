<?php
declare(strict_types=1);

namespace CakeInstructor\Service;

use CakeInstructor\CakeInstructor;
use CakeInstructor\Data\ExtractionRequest;
use CakeInstructor\Exception\InstructorIntegrationException;
use CakeInstructor\Exception\MissingConfigurationException;
use CakeInstructor\Exception\ProviderRequestException;
use CakeInstructor\Exception\ResponseSchemaException;
use CakeInstructor\Factory\StructuredOutputFactory;
use CakeInstructor\Support\InstructorExceptionMapper;
use Throwable;

final class InstructorConnectionProbeService
{
    /**
     * @var callable(\CakeInstructor\Data\ExtractionRequest,string):mixed|null
     */
    private mixed $probeRunner;

    /**
     * @param array<string,mixed> $config
     * @param callable(\CakeInstructor\Data\ExtractionRequest,string):mixed|null $probeRunner
     */
    public function __construct(
        private readonly array $config,
        private readonly ?CakeInstructor $client = null,
        ?callable $probeRunner = null,
    ) {
        $this->probeRunner = $probeRunner;
    }

    /**
     * @return array<int,string>
     */
    public function availableConnections(): array
    {
        $connections = is_array($this->config['connections'] ?? null) ? $this->config['connections'] : [];

        return array_values(array_filter(
            array_keys($connections),
            static fn(mixed $name): bool => is_string($name) && $name !== '',
        ));
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
     * @return array{connection:string,driver:string,model:string}
     */
    public function resolveConnectionMeta(?string $requestedConnection): array
    {
        $connection = $requestedConnection ?? $this->defaultConnection();
        $resolved = $this->resolvedConnectionConfig($connection);

        return [
            'connection' => $connection,
            'driver' => $this->stringOrUnknown($resolved['driver'] ?? null),
            'model' => $this->stringOrUnknown($resolved['model'] ?? null),
        ];
    }

    /**
     * Return the resolved connection config with sensitive values masked for debugging.
     *
     * @return array<string,mixed>
     */
    public function debugConnectionConfig(string $connectionName): array
    {
        return $this->maskSensitiveValues($this->resolvedConnectionConfig($connectionName));
    }

    /**
     * Run a structured connection probe against the selected connection.
     */
    public function probe(ExtractionRequest $request, string $connectionName): mixed
    {
        if (is_callable($this->probeRunner)) {
            return ($this->probeRunner)($request, $connectionName);
        }

        $client = $this->client ?? new CakeInstructor();

        return $client->runExtract(
            request: $request,
            connectionName: $connectionName,
            factory: new StructuredOutputFactory(pluginConfig: $this->config),
            exceptionMapper: new InstructorExceptionMapper(),
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function probeSummary(string $connectionName): array
    {
        return $this->buildProbeSummary($connectionName, null);
    }

    /**
     * @return array<string,mixed>
     */
    public function probeSummaryWithDebug(string $connectionName): array
    {
        return $this->buildProbeSummary($connectionName, $this->debugConnectionConfig($connectionName));
    }

    /**
     * @param array<string,mixed>|null $debugConfig
     * @return array<string,mixed>
     */
    private function buildProbeSummary(string $connectionName, ?array $debugConfig): array
    {
        $connectionMeta = $this->resolveConnectionMeta($connectionName);

        try {
            $result = $this->probe($this->probeRequest(), $connectionMeta['connection']);
            $summary = [
                'ok' => true,
                'type' => 'success',
                'connection' => $connectionMeta['connection'],
                'driver' => $connectionMeta['driver'],
                'model' => $connectionMeta['model'],
                'message' => 'Connection reached provider and returned structured output.',
                'hint' => 'Connection and model look healthy for structured extraction.',
                'response' => $result,
            ];
        } catch (Throwable $err) {
            $summary = $this->failureSummary($connectionMeta, $err);
        }

        if ($debugConfig !== null) {
            $summary['debug'] = $debugConfig;
        }

        return $summary;
    }

    /**
     * Normalize optional string config values for display output.
     */
    private function stringOrUnknown(mixed $value): string
    {
        if (!is_string($value)) {
            return 'unknown';
        }

        $normalized = trim($value);

        return $normalized !== '' ? $normalized : 'unknown';
    }

    /**
     * @param array{connection:string,driver:string,model:string} $connectionMeta
     * @return array<string,mixed>
     */
    private function failureSummary(array $connectionMeta, Throwable $err): array
    {
        $type = 'integration_error';
        $hint = 'Check connection config, API key, model name, and provider response details.';

        if ($err instanceof MissingConfigurationException) {
            $type = 'config_error';
            $hint = 'Verify config/app.php connection name and required env vars for this connection.';
        }

        if ($err instanceof ProviderRequestException) {
            $type = 'provider_error';
            $hint = 'Verify API key, API URL, model name, and network access for the selected provider.';
        }

        if ($err instanceof ResponseSchemaException) {
            $type = 'schema_error';
            $hint = 'Connection is likely reachable; adjust prompt/schema compatibility for this model.';
        }

        if ($err instanceof InstructorIntegrationException && $type === 'integration_error') {
            $hint = 'Check wrapped exception details and provider compatibility with structured extraction.';
        }

        $message = $err->getMessage();
        if (str_contains(strtolower($message), 'does not support tools')) {
            $type = 'provider_error';
            $hint = 'Model is reachable but incompatible with tool/structured mode; '
                . 'choose a model that supports structured extraction tools.';
        }

        return [
            'ok' => false,
            'type' => $type,
            'connection' => $connectionMeta['connection'],
            'driver' => $connectionMeta['driver'],
            'model' => $connectionMeta['model'],
            'errorClass' => $err::class,
            'message' => $message,
            'hint' => $hint,
        ];
    }

    /**
     * Build a minimal structured probe request.
     */
    private function probeRequest(): ExtractionRequest
    {
        return new ExtractionRequest(
            messages: [['role' => 'user', 'content' => 'Reply with ok=true and a short provider string.']],
            responseModel: [
                'type' => 'object',
                'properties' => [
                    'ok' => ['type' => 'boolean'],
                    'provider' => ['type' => 'string'],
                ],
                'required' => ['ok'],
                'additionalProperties' => false,
            ],
            system: 'Connection probe. Return valid structured output.',
            options: ['temperature' => 0],
        );
    }

    /**
     * @return array<string,mixed>
     */
    private function resolvedConnectionConfig(string $connectionName): array
    {
        $connections = is_array($this->config['connections'] ?? null) ? $this->config['connections'] : [];
        $resolved = $connections[$connectionName] ?? null;

        return is_array($resolved) ? $resolved : [];
    }

    /**
     * @param array<string,mixed> $config
     * @return array<string,mixed>
     */
    private function maskSensitiveValues(array $config): array
    {
        $masked = [];
        foreach ($config as $key => $value) {
            if (!is_string($key)) {
                continue;
            }

            if (is_array($value)) {
                $masked[$key] = $this->maskSensitiveValues($value);

                continue;
            }

            $masked[$key] = $this->isSensitiveKey($key)
                ? $this->maskValue($value)
                : $value;
        }

        return $masked;
    }

    /**
     * Identify config keys whose values should be masked in debug output.
     */
    private function isSensitiveKey(string $key): bool
    {
        $normalized = strtolower($key);

        return str_contains($normalized, 'key')
            || str_contains($normalized, 'token')
            || str_contains($normalized, 'secret')
            || str_contains($normalized, 'password');
    }

    /**
     * Mask sensitive scalar config values while leaving enough context to inspect them.
     */
    private function maskValue(mixed $value): string
    {
        if (!is_scalar($value) || $value === '') {
            return '';
        }

        $stringValue = (string)$value;
        $length = strlen($stringValue);
        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        return substr($stringValue, 0, 2)
            . str_repeat('*', max(4, $length - 4))
            . substr($stringValue, -2);
    }
}
