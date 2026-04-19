<?php
declare(strict_types=1);

namespace CakeInstructor\Config;

final class Connections
{
    /**
     * Build an Anthropic Messages API connection.
     *
     * @param array<string,mixed> $overrides
     * @return array<string,mixed>
     */
    public static function anthropic(
        string $apiKey,
        string $model,
        array $overrides = [],
    ): array {
        return self::mergeDefaults([
            'driver' => 'anthropic',
            'apiUrl' => 'https://api.anthropic.com/v1',
            'endpoint' => '/messages',
            'apiKey' => $apiKey,
            'model' => $model,
            'options' => [
                'timeout' => 30,
            ],
        ], $overrides);
    }

    /**
     * Build a Gemini GenerateContent API connection.
     *
     * @param array<string,mixed> $overrides
     * @return array<string,mixed>
     */
    public static function gemini(
        string $apiKey,
        string $model,
        array $overrides = [],
    ): array {
        return self::mergeDefaults([
            'driver' => 'gemini',
            'apiUrl' => 'https://generativelanguage.googleapis.com/v1beta',
            'apiKey' => $apiKey,
            'model' => $model,
            'options' => [
                'timeout' => 30,
            ],
        ], $overrides);
    }

    /**
     * Build an Ollama OpenAI-compatible connection.
     *
     * @param array<string,mixed> $overrides
     * @return array<string,mixed>
     */
    public static function ollama(
        string $model,
        array $overrides = [],
    ): array {
        return self::mergeDefaults([
            'driver' => 'ollama',
            'apiUrl' => 'http://127.0.0.1:11434/v1',
            'endpoint' => '/chat/completions',
            'apiKey' => '',
            'model' => $model,
            'options' => [
                'timeout' => 10,
            ],
        ], $overrides);
    }

    /**
     * Build an OpenAI Chat Completions connection.
     *
     * Use this for the legacy/current chat-completions API shape.
     *
     * @param array<string,mixed> $overrides
     * @return array<string,mixed>
     */
    public static function openaiChat(
        string $apiKey,
        string $model,
        array $overrides = [],
    ): array {
        return self::mergeDefaults([
            'driver' => 'openai',
            'apiUrl' => 'https://api.openai.com/v1',
            'endpoint' => '/chat/completions',
            'apiKey' => $apiKey,
            'model' => $model,
            'options' => [
                'timeout' => 30,
            ],
        ], $overrides);
    }

    /**
     * Build an OpenAI Responses API connection.
     *
     * Use this when you want the newer Responses API transport.
     *
     * @param array<string,mixed> $overrides
     * @return array<string,mixed>
     */
    public static function openaiResponses(
        string $apiKey,
        string $model,
        array $overrides = [],
    ): array {
        return self::mergeDefaults([
            'driver' => 'openai-responses',
            'apiUrl' => 'https://api.openai.com/v1',
            'endpoint' => '/responses',
            'apiKey' => $apiKey,
            'model' => $model,
            'options' => [
                'timeout' => 30,
            ],
        ], $overrides);
    }

    /**
     * @param array<string,mixed> $defaults
     * @param array<string,mixed> $overrides
     * @return array<string,mixed>
     */
    private static function mergeDefaults(array $defaults, array $overrides): array
    {
        return array_replace_recursive($defaults, $overrides);
    }
}
