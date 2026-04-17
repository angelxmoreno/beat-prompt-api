<?php
declare(strict_types=1);

return [
    'default_connection' => 'default',
    'connections' => [
        'default' => [
            'driver' => (string)env('CAKE_INSTRUCTOR_DRIVER', 'openai'),
            'apiKey' => (string)env('CAKE_INSTRUCTOR_API_KEY', (string)env('OPENAI_API_KEY', '')),
            'model' => (string)env('CAKE_INSTRUCTOR_MODEL', 'gpt-4.1-mini'),
            'apiUrl' => (string)env('CAKE_INSTRUCTOR_API_URL', ''),
            'endpoint' => (string)env('CAKE_INSTRUCTOR_ENDPOINT', ''),
            'maxTokens' => (int)env('CAKE_INSTRUCTOR_MAX_TOKENS', 2048),
            'contextLength' => (int)env('CAKE_INSTRUCTOR_CONTEXT_LENGTH', 128000),
            'maxOutputLength' => (int)env('CAKE_INSTRUCTOR_MAX_OUTPUT_LENGTH', 4096),
            'options' => [
                'timeout' => (int)env('CAKE_INSTRUCTOR_TIMEOUT_SECONDS', 30),
            ],
        ],
    ],
    'structured' => [
        'maxRetries' => (int)env('CAKE_INSTRUCTOR_MAX_RETRIES', 1),
    ],
];
