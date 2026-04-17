# Instructor PHP Integration Plan

## Overview
To guarantee structured, typed data extraction from LLMs for BeatPrompt's Phase 1 (Style Extraction) and Phase 2 (Prompt Synthesis), we will integrate `cognesy/instructor-php`. This plan outlines the steps required to set up the library, configure it within the CakePHP environment, and establish a testing strategy.

## Tasks

### 1. Installation
- [ ] Require the package via Composer: `composer require cognesy/instructor-php`.
- [ ] Ensure any necessary HTTP client dependencies (like Guzzle) are present if not already installed by the package.

### 2. Configuration & Environment
- [ ] Add placeholder keys (never real secrets) to `sample.env` and document required variables (`OPENAI_API_KEY` or `ANTHROPIC_API_KEY`, provider, model, timeout).
- [ ] Add real API keys only in local `.env` (gitignored) and confirm no secrets are committed.
- [ ] Create a dedicated CakePHP configuration block in `config/app.php` for AI settings (provider, model, timeout, retry count) sourced from environment variables.

### 3. Core Response Models
- [ ] Create a dedicated namespace for AI Response Models (e.g., `App\Prompt\Model\Response` or `App\AI\Response`).
- [ ] Define the `StyleProfile` class with strict types and docblocks/attributes for the properties required in Phase 1 (genre, mood, energy, tempo, instruments, etc.).

### 4. Service Integration
- [ ] In the `Prompt\StyleExtractor` service (to be built in TICKET-003), implement the `StructuredOutput::using(...)` fluent API.
- [ ] Ensure the service correctly passes the Canonical Request data as the prompt/messages to the LLM.
- [ ] Add explicit error handling for provider/network timeout, invalid structured output, and missing configuration.
- [ ] Define retry policy (max attempts + backoff) and ensure failures are surfaced as domain-level exceptions with useful error messages.

### 5. Testing Strategy
- [ ] Implement tests for the extraction services using `StructuredOutput::fake()`.
- [ ] Create mock response model instances (e.g., a fake `StyleProfile`) to inject during unit tests. This ensures the CI pipeline does not make actual network requests to the LLM provider and remains fast and deterministic.
- [ ] Add negative-path tests for malformed LLM output, missing required fields, timeout/error propagation, and missing env/config values.
- [ ] Add a non-CI opt-in smoke test for real provider invocation (skipped by default) to validate end-to-end local wiring without making CI flaky/costly.

## Acceptance Criteria
- `cognesy/instructor-php` is installed and autoloaded.
- `sample.env` contains placeholders and documentation only; real keys are read from local `.env` and are not committed.
- AI configuration is loaded through CakePHP config (`config/app.php`) with env overrides for provider/model/timeout/retry.
- `Prompt\StyleExtractor` returns a strongly-typed `StyleProfile` on success and throws domain-level exceptions for provider timeout/error, invalid structured output, and missing config.
- Automated tests cover both happy path (with `fake()`) and required failure paths, and run without external network calls in CI.
- Optional real-provider smoke test exists and is disabled by default in CI.
