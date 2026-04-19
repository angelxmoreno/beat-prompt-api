# CakeInstructor Plugin

Reusable CakePHP plugin that wraps `cognesy/instructor-php` behind project-agnostic helper classes.

## What It Provides

- `StructuredOutputFactory`: builds configured `StructuredOutput` instances.
- `InstructorStructuredExtractor`: small service for typed extraction requests.
- `Config\Connections`: provider-specific connection builders for app config.
- `Service\InstructorConnectionProbeService`: reusable probe summary logic for diagnostics.
- `PromptMessageBuilder`: fluent helper for provider message arrays.
- `InstructorExceptionMapper`: maps Instructor/provider exceptions into stable plugin exceptions.
- Plugin diagnostic commands for validating and probing configured connections.
- `FakeStructuredExtractor` + `InstructorTestFakes`: deterministic testing helpers.

## Config

Plugin config path: `Configure::read('CakeInstructor')`

Default keys:

- `structured`

Configuration ownership:

- App code owns connection configuration and env resolution.
- `CakeInstructor` runtime reads `Configure::read('CakeInstructor')`.
- The plugin no longer expects `CAKE_INSTRUCTOR_*` env vars for runtime connection setup.
- Use `CakeInstructor\Config\Connections` in `config/app.php` to build valid provider configs.

Example:

```php
use CakeInstructor\Config\Connections;

[
    'default_connection' => 'openai:default',
    'connections' => [
        'openai:default' => Connections::openaiChat(
            apiKey: env('OPENAI_API_KEY', ''),
            model: env('OPENAI_MODEL', 'gpt-4.1'),
        ),
    ],
    'structured' => [
        'maxRetries' => 1,
    ],
]
```

Provider builders:

- `Connections::ollama(model, overrides = [])`
  - OpenAI-compatible Ollama connection with `/chat/completions`.
- `Connections::gemini(apiKey, model, overrides = [])`
  - Gemini GenerateContent connection.
- `Connections::anthropic(apiKey, model, overrides = [])`
  - Anthropic Messages API connection.
- `Connections::openaiChat(apiKey, model, overrides = [])`
  - OpenAI Chat Completions API connection.
- `Connections::openaiResponses(apiKey, model, overrides = [])`
  - OpenAI Responses API connection.

Why `openaiChat` and `openaiResponses` are separate:

- They target different OpenAI API contracts.
- `openaiChat` uses `driver=openai` and `endpoint=/chat/completions`.
- `openaiResponses` uses `driver=openai-responses` and `endpoint=/responses`.
- Exposing them separately keeps the transport choice explicit in app config.

## Diagnostic Commands

`CakeInstructor` ships diagnostic CLI commands and owns them at the plugin layer. The app owns env resolution and connection definitions in `config/app.php`, but these commands operate on the resolved `Configure::read('CakeInstructor')` config.

Available commands:

- `bin/cake instructor_connection_probe`
  - Probe one connection with a minimal structured request.
  - If `--connection` is omitted, lists available connections and prompts for a choice.
  - Supports `--debug` to print masked resolved config.
- `bin/cake instructor_connections_validate`
  - Validate one or all configured connections without making provider requests.
- `bin/cake instructor_connections_doctor`
  - Run validation first, then probe only the connections that pass config validation.

Examples:

```bash
bin/cake instructor_connection_probe
bin/cake instructor_connection_probe --connection=ollama:gemma4 --debug
bin/cake instructor_connections_validate --format=json | jq
bin/cake instructor_connections_doctor --debug
```

What each command is for:

- Use `instructor_connections_validate` when you want to confirm config completeness before touching a provider.
- Use `instructor_connection_probe` when you want to test a single provider/model path and see connection-specific failures.
- Use `instructor_connections_doctor` when you want a quick sweep across all configured connections.

## Usage

```php
use CakeInstructor\CakeInstructor;
use CakeInstructor\Data\ExtractionRequest;
use CakeInstructor\Factory\StructuredOutputFactory;
use CakeInstructor\Service\InstructorStructuredExtractor;
use CakeInstructor\Support\InstructorExceptionMapper;

$extractor = new InstructorStructuredExtractor(
    new StructuredOutputFactory(),
    new InstructorExceptionMapper(),
);

$result = $extractor->extract(new ExtractionRequest(
    messages: [['role' => 'user', 'content' => 'Extract contact info']],
    responseModel: ContactDto::class,
));
```

One-shot helper:

```php
use CakeInstructor\CakeInstructor;
use CakeInstructor\Data\ExtractionRequest;

$result = CakeInstructor::extract(
    request: ExtractionRequest::fromPrompt(
        prompt: 'Extract contact info',
        responseModel: ContactDto::class,
    ),
);
```

## Test Doubles

```php
use CakeInstructor\Testing\InstructorTestFakes;

$fake = InstructorTestFakes::extractor([
    new ContactDto(name: 'Ada', email: 'ada@example.com'),
]);
```
