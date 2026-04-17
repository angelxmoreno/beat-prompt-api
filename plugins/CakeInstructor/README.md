# CakeInstructor Plugin

Reusable CakePHP plugin that wraps `cognesy/instructor-php` behind project-agnostic helper classes.

## What It Provides

- `StructuredOutputFactory`: builds configured `StructuredOutput` instances.
- `InstructorStructuredExtractor`: small service for typed extraction requests.
- `PromptMessageBuilder`: fluent helper for provider message arrays.
- `InstructorExceptionMapper`: maps Instructor/provider exceptions into stable plugin exceptions.
- `FakeStructuredExtractor` + `InstructorTestFakes`: deterministic testing helpers.

## Config

Plugin config path: `Configure::read('CakeInstructor')`

Default keys:

- `default_connection`
- `connections`
- `structured`

Connection intent:

- `default_connection` exists for rapid integration and easy provider/model swaps.
- You can add multiple named `connections` and switch the default without changing app code.
- `apiUrl` is the base URL for the selected connection's `driver` (driver-specific).
- For common presets/default drivers, you can usually leave `apiUrl` empty.
- Set `apiUrl` when using non-default endpoints (for example OpenAI-compatible gateways/proxies/self-hosted URLs).

Example:

```php
[
    'default_connection' => 'default',
    'connections' => [
        'default' => [
            'driver' => 'openai',
            'apiKey' => env('OPENAI_API_KEY'),
            'model' => 'gpt-4.1-mini',
            'options' => ['timeout' => 30],
        ],
    ],
    'structured' => [
        'maxRetries' => 1,
    ],
]
```

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
