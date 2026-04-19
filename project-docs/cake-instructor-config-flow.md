# CakeInstructor Config Flow

This project keeps `CakeInstructor` configuration ownership in the application layer.

## Source Of Truth

- Runtime code reads `Configure::read('CakeInstructor')`.
- Application config in [`config/app.php`](../config/app.php) builds that structure.
- Environment variables are resolved in app config, not inside `CakeInstructor` runtime services.

## Runtime Path

1. Cake loads `.env` and config files.
2. [`config/app.php`](../config/app.php) resolves env values.
3. App config builds named connections with [`Connections.php`](../plugins/CakeInstructor/src/Config/Connections.php).
4. `CakeInstructor` runtime reads `Configure::read('CakeInstructor')`.
5. [`StructuredOutputFactory.php`](../plugins/CakeInstructor/src/Factory/StructuredOutputFactory.php) converts the selected connection into `LLMConfig`.

## What The Plugin Owns

- Runtime factory and extractor logic
- Provider-specific connection builders
- Structured-output defaults
- Error mapping and diagnostics

## What The App Owns

- Which connections exist
- Which connection is default
- Which env vars feed each connection
- Any project-specific URL/model overrides

## Builder Methods

- `Connections::ollama(model, overrides = [])`
- `Connections::gemini(apiKey, model, overrides = [])`
- `Connections::anthropic(apiKey, model, overrides = [])`
- `Connections::openaiChat(apiKey, model, overrides = [])`
- `Connections::openaiResponses(apiKey, model, overrides = [])`

These builders:

- fill provider defaults like `driver` and `endpoint`
- accept app-level overrides
- do not fail app boot on blank env values; use the plugin diagnostic commands to validate resolved connections

## Env Ownership Rule

Use env vars only in app config.

Example:

```php
'openai:default' => Connections::openaiChat(
    apiKey: env('OPENAI_API_KEY', ''),
    model: env('OPENAI_MODEL', 'gpt-4.1'),
    overrides: [
        'apiUrl' => env('OPENAI_API_URL', 'https://api.openai.com/v1'),
    ],
),
```

Do not add provider env lookups inside `CakeInstructor` runtime classes.

## Diagnostics

Use the plugin commands to inspect the resolved configuration and runtime health of connections:

- `bin/cake instructor_connection_probe`
- `bin/cake instructor_connections_validate`
- `bin/cake instructor_connections_doctor`

These commands operate on the resolved `Configure::read('CakeInstructor')` config, not raw env state.

## Why This Exists

Without this split, connection setup becomes hard to reason about:

- app config and plugin defaults both read env
- provider endpoint defaults become implicit
- debugging connection issues gets slower

The current structure makes the config flow explicit:

- app resolves env
- plugin consumes config
