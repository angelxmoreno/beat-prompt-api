# TICKET-002: Implement Phase 0 - Canonicalization

## Description
Implement Phase 0 of the prompt pipeline: Canonicalization and intent resolution. This phase normalizes user input into a canonical request object that can be safely used as a primary cache key.

Prompt class convention for this and subsequent tickets:
- Namespace: `App\Prompt\<Feature>\<Class>`
- Path: `src/Prompt/<Feature>/<Class>.php`

## Tasks
- [x] Create `App\Prompt\Canonicalizer\Canonicalizer` (`src/Prompt/Canonicalizer/Canonicalizer.php`).
- [x] Implement input normalization (e.g., lowercase, trimming).
- [x] Implement LLM-based intent extraction through `CakeInstructor` with structured output.
- [x] Generate a canonical request object with properties: `kind`, `artists`, `target`, and `modifiers`.
- [x] Implement deterministic serialization of the canonical request object to generate a canonical cache key (e.g., `kind:artist_style_prompt|artists:joyner lucas|target:beat|modifiers:`).
- [x] Keep post-processing minimal: structural normalization only (lowercase/trim/deduplicate), no lexical/rules stopword stripping in mapper logic.
- [x] Provide canonicalization model-eval command support via `prompt_canonicalize_compare` (default case file + custom file/JSON input) so connections/models can be benchmarked before promotion.

## Technical Notes
- This phase is LLM-first and fail-fast (no rules fallback).
- The canonical cache key will be used to look up results for subsequent phases.
- This phase consumes `CakeInstructor` directly as the shared integration layer.
- Evaluation harness for this phase: `bin/cake prompt_canonicalize_compare [--connection=...]`.
