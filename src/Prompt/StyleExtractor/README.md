# StyleExtractor Module

This directory contains the Phase 1 style extraction code path.

## Files

### `StyleExtractor.php`
Main style extraction entrypoint used by the app pipeline.

Responsibilities:
- Accept canonical request + canonical key.
- Build Instructor extraction request.
- Map raw structured output into `StyleProfile`.

### `StyleExtractionResult.php`
DTO/schema for Instructor structured extraction output (`responseModel`).

Expected fields:
- `genre`
- `mood`
- `energy`
- `tempoBpm`
- `instruments`
- `rhythmTraits` / `rhythm_traits`
- `productionTraits` / `production_traits`

### `StyleProfileMapper.php`
Structural normalization and validation from raw extraction output to `StyleProfile`.

### `StyleProfile.php`
Immutable internal representation of extracted style attributes.

### `StyleExtractComparisonService.php`
Benchmark case loader + evaluator for style extraction compare command.

## Runtime Flow

1. Build canonical request in Phase 0.
2. Call `StyleExtractor::extract($canonical, $canonicalKey)`.
3. Call Instructor + map output.
4. Return `StyleProfile`.
