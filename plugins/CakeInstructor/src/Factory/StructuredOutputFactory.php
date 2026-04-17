<?php
declare(strict_types=1);

namespace CakeInstructor\Factory;

use Cake\Core\Configure;
use CakeInstructor\Contracts\StructuredOutputFactoryInterface;
use CakeInstructor\Exception\MissingConfigurationException;
use Cognesy\Instructor\Config\StructuredOutputConfig;
use Cognesy\Instructor\StructuredOutput;
use Cognesy\Instructor\StructuredOutputRuntime;
use Cognesy\Polyglot\Inference\Config\LLMConfig;

final class StructuredOutputFactory implements StructuredOutputFactoryInterface
{
    /** @var array<int, string> */
    private const array LLM_ALLOWED_KEYS = [
        'apiUrl',
        'apiKey',
        'endpoint',
        'queryParams',
        'metadata',
        'model',
        'maxTokens',
        'contextLength',
        'maxOutputLength',
        'driver',
        'options',
        'pricing',
    ];

    /** @var array<string, string> */
    private const LLM_KEY_ALIASES = [
        'api_url' => 'apiUrl',
        'api_key' => 'apiKey',
        'query_params' => 'queryParams',
        'max_tokens' => 'maxTokens',
        'context_length' => 'contextLength',
        'max_output_length' => 'maxOutputLength',
    ];

    /** @var array<string, string> */
    private const STRUCTURED_KEY_ALIASES = [
        'output_mode' => 'outputMode',
        'use_object_references' => 'useObjectReferences',
        'max_retries' => 'maxRetries',
        'retry_prompt' => 'retryPrompt',
        'mode_prompts' => 'modePrompts',
        'mode_prompt_classes' => 'modePromptClasses',
        'retry_prompt_class' => 'retryPromptClass',
        'schema_name' => 'schemaName',
        'schema_description' => 'schemaDescription',
        'tool_name' => 'toolName',
        'tool_description' => 'toolDescription',
        'output_class' => 'outputClass',
        'default_to_std_class' => 'defaultToStdClass',
        'deserialization_error_prompt_class' => 'deserializationErrorPromptClass',
        'throw_on_transformation_failure' => 'throwOnTransformationFailure',
        'chat_structure' => 'chatStructure',
        'response_cache_policy' => 'responseCachePolicy',
        'stream_materialization_interval' => 'streamMaterializationInterval',
    ];

    /**
     * @param array<string, mixed>|null $pluginConfig
     */
    public function __construct(private readonly ?array $pluginConfig = null)
    {
    }

    /**
     * @param array<string, mixed> $connectionOverrides
     * @param array<string, mixed> $structuredOverrides
     */
    public function make(
        ?string $connectionName = null,
        array $connectionOverrides = [],
        array $structuredOverrides = [],
    ): StructuredOutput {
        $config = $this->resolveRootConfig();
        $resolvedConnectionName = $this->resolveConnectionName($connectionName, $config);
        $connectionConfig = $this->resolveConnectionConfig($resolvedConnectionName, $config, $connectionOverrides);

        $llmConfig = $this->buildLlmConfig($connectionConfig);
        $structuredConfig = $this->buildStructuredConfig($config, $structuredOverrides);

        $runtime = StructuredOutputRuntime::fromConfig($llmConfig)->withConfig($structuredConfig);

        return (new StructuredOutput())->withRuntime($runtime);
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveRootConfig(): array
    {
        $config = $this->pluginConfig ?? (array)Configure::read('CakeInstructor', []);
        if ($config === []) {
            throw new MissingConfigurationException('CakeInstructor config is missing.');
        }

        return $config;
    }

    /**
     * @param array<string, mixed> $rootConfig
     */
    private function resolveConnectionName(?string $connectionName, array $rootConfig): string
    {
        $name = $connectionName ?? (string)($rootConfig['default_connection'] ?? 'default');
        if ($name === '') {
            throw new MissingConfigurationException('No CakeInstructor default connection is configured.');
        }

        return $name;
    }

    /**
     * @param array<string, mixed> $rootConfig
     * @param array<string, mixed> $connectionOverrides
     * @return array<string, mixed>
     */
    private function resolveConnectionConfig(string $name, array $rootConfig, array $connectionOverrides): array
    {
        $connections = (array)($rootConfig['connections'] ?? []);
        if (!array_key_exists($name, $connections) || !is_array($connections[$name])) {
            throw new MissingConfigurationException(sprintf(
                'Connection "%s" is not defined in CakeInstructor.connections.',
                $name,
            ));
        }

        return array_replace_recursive(
            $this->normalizeLlmConfig((array)$connections[$name]),
            $this->normalizeLlmConfig($connectionOverrides),
        );
    }

    /**
     * @param array<string, mixed> $connectionConfig
     */
    private function buildLlmConfig(array $connectionConfig): LLMConfig
    {
        $preset = (string)($connectionConfig['preset'] ?? '');
        $basePath = isset($connectionConfig['basePath']) ? (string)$connectionConfig['basePath'] : null;

        if ($preset !== '') {
            $overrides = $connectionConfig;
            unset($overrides['preset'], $overrides['basePath']);

            return LLMConfig::fromPreset($preset, $basePath)->withOverrides($this->filterAllowedLlmConfig($overrides));
        }

        $normalized = $this->filterAllowedLlmConfig($connectionConfig);
        if (($normalized['driver'] ?? '') === '' || ($normalized['model'] ?? '') === '') {
            throw new MissingConfigurationException('LLM connection requires non-empty driver and model values.');
        }

        return LLMConfig::fromArray($normalized);
    }

    /**
     * @param array<string, mixed> $rootConfig
     * @param array<string, mixed> $structuredOverrides
     */
    private function buildStructuredConfig(array $rootConfig, array $structuredOverrides): StructuredOutputConfig
    {
        $base = $this->normalizeStructuredConfig((array)($rootConfig['structured'] ?? []));
        $overrides = $this->normalizeStructuredConfig($structuredOverrides);

        return StructuredOutputConfig::fromArray(array_replace_recursive($base, $overrides));
    }

    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    private function normalizeLlmConfig(array $config): array
    {
        $normalized = [];
        foreach ($config as $key => $value) {
            $targetKey = self::LLM_KEY_ALIASES[$key] ?? $key;
            $normalized[$targetKey] = $value;
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    private function normalizeStructuredConfig(array $config): array
    {
        $normalized = [];
        foreach ($config as $key => $value) {
            $targetKey = self::STRUCTURED_KEY_ALIASES[$key] ?? $key;
            $normalized[$targetKey] = $value;
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    private function filterAllowedLlmConfig(array $config): array
    {
        $filtered = [];
        foreach (self::LLM_ALLOWED_KEYS as $key) {
            if (array_key_exists($key, $config)) {
                $filtered[$key] = $config[$key];
            }
        }

        return $filtered;
    }
}
