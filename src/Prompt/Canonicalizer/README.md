# Prompt Module

This directory contains the Phase 0 canonicalization code path.

## Files

### `Canonicalizer.php`
Main canonicalization entrypoint used by any application layer (controllers, services, commands, jobs, etc.).

Responsibilities:
- Normalizes raw input text.
- Builds the Instructor extraction request.
- Calls CakeInstructor structured extraction.
- Converts extraction or mapping failures into a clear fail-fast exception.

### `CanonicalExtractionResult.php`
DTO/schema for Instructor structured extraction output (`responseModel`).

Expected fields:
- `kind`
- `artists`
- `target`
- `modifiers`

### `CanonicalResponseMapper.php`
Sanitizes and validates raw extraction output, then maps it to a `CanonicalRequest`.

Responsibilities:
- Read object/array extraction results safely.
- Apply minimal structural normalization to `target` (lowercase/trim with empty fallback).
- Enforce valid kinds and cleanup modifiers/artists.
- Produce final `CanonicalRequest` with `source = llm`.

Canonical normalization details:
- `artists`: lowercase, punctuation removed (example: `j. cole` -> `j cole`).
- `target`: LLM-derived intent label; only structural normalization is applied.

### `CanonicalRequest.php`
Immutable internal representation of canonical prompt intent.

Fields:
- `kind`
- `artists`
- `target`
- `modifiers`
- `source`

### `CanonicalKeySerializer.php`
Deterministic serializer for canonical cache keys.

Responsibilities:
- Sort artists/modifiers for stable ordering.
- Build the key format:
  - `kind:<...>|artists:<...>|target:<...>|modifiers:<...>`

## Runtime Flow

1. `Canonicalizer::canonicalize($input)` normalizes input.
2. `Canonicalizer` calls Instructor using `CanonicalExtractionResult` schema.
3. `CanonicalResponseMapper` sanitizes/maps output to `CanonicalRequest`.
4. `CanonicalKeySerializer` creates a stable key when `canonicalKey()` is called.
