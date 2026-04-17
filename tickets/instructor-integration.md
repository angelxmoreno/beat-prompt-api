# CakeInstructor Shared Integration Plan

> Status note: Shared-layer foundation is complete; remaining unchecked items are intentionally deferred to TICKET-003 app/domain integration.

## Overview
To guarantee structured, typed data extraction from LLMs for BeatPrompt's Phase 0 (Canonicalization), Phase 1 (Style Extraction), and Phase 2 (Prompt Synthesis), we will use `CakeInstructor` as the shared integration layer over `cognesy/instructor-php`. This plan outlines the steps required to set up the shared layer, configure it within the CakePHP environment, and establish a testing strategy.

Prompt class convention for integration points:
- Namespace: `App\Prompt\<Feature>\<Class>`
- Path: `src/Prompt/<Feature>/<Class>.php`
- Output-handling convention: minimal structural normalization only (shape/type checks, trimming/lowercasing where needed, deduplication). No lexical/rules stripping post-processing.

## Tasks

### 1. Installation
- [x] Require the package via Composer: `composer require cognesy/instructor-php`.
- [x] Ensure any necessary HTTP client dependencies (like Guzzle) are present if not already installed by the package.

### 2. Configuration & Environment
- [x] Add placeholder keys (never real secrets) to `sample.env` and document required `CAKE_INSTRUCTOR_*` variables (driver, API key, model, timeout, retry count).
- [ ] Add real API keys only in local `.env` (gitignored) and confirm no secrets are committed.
- [x] Ensure the CakePHP `CakeInstructor` config block is sourced from environment variables and supports default-connection swapping.

### 3. Core Response Models
- [ ] Create feature-local AI response model namespaces under prompt modules (e.g., `App\Prompt\StyleExtractor\...` in `src/Prompt/StyleExtractor/`).
- [ ] Define the `StyleProfile` class with strict types and docblocks/attributes for the properties required in Phase 1 (genre, mood, energy, tempo, instruments, etc.).

### 4. Service Integration
- [ ] In `App\Prompt\StyleExtractor\StyleExtractor` (`src/Prompt/StyleExtractor/StyleExtractor.php`), integrate via `CakeInstructor::extract(...)` or injected `InstructorStructuredExtractor`.
- [ ] Ensure the service correctly passes the Canonical Request data as the prompt/messages to the LLM.
- [x] Add explicit error handling for provider/network timeout, invalid structured output, and missing configuration.
- [x] Define retry policy (max attempts + backoff) and ensure failures are surfaced as domain-level exceptions with useful error messages.

### 5. Testing Strategy
- [x] Implement tests for extraction services using `CakeInstructor` test doubles (`InstructorTestFakes::extractor(...)` / `FakeStructuredExtractor`).
- [ ] Create mock response model instances (e.g., a fake `StyleProfile`) to inject during unit tests. This ensures the CI pipeline does not make actual network requests to the LLM provider and remains fast and deterministic.
- [x] Add negative-path tests for malformed LLM output, missing required fields, timeout/error propagation, and missing env/config values.
- [ ] Add a non-CI opt-in smoke test for real provider invocation (skipped by default) to validate end-to-end local wiring without making CI flaky/costly.

## Acceptance Criteria
- `CakeInstructor` is the required shared integration layer for LLM-backed phases.
- `sample.env` contains placeholders and documentation only; real keys are read from local `.env` and are not committed.
- `CakeInstructor` configuration is loaded from env (`CAKE_INSTRUCTOR_*`) and supports default connection overrides.
- `App\Prompt\StyleExtractor\StyleExtractor` returns a strongly-typed `StyleProfile` on success and throws domain-level exceptions for provider timeout/error, invalid structured output, and missing config.
- Prompt modules follow the output-handling convention: no lexical/rules post-processing reliance; only structural normalization is allowed.
- Automated tests cover both happy path (with `CakeInstructor` test doubles) and required failure paths, and run without external network calls in CI.
- Optional real-provider smoke test exists and is disabled by default in CI.
