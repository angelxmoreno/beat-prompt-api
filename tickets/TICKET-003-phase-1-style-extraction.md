# TICKET-003: Implement Phase 1 - Style Extraction

## Description
Implement Phase 1 of the prompt pipeline: Style Extraction. This phase takes the canonical request from Phase 0 and infers musical attributes using a high-quality LLM, with `CakeInstructor` as the shared integration layer for typed structured extraction.

Prompt class convention:
- Namespace: `App\Prompt\<Feature>\<Class>`
- Path: `src/Prompt/<Feature>/<Class>.php`

## Tasks
- [x] Create `App\Prompt\StyleExtractor\StyleExtractor` (`src/Prompt/StyleExtractor/StyleExtractor.php`).
- [x] Implement integration with the primary LLM through `CakeInstructor` (`CakeInstructor::extract(...)` or injected `InstructorStructuredExtractor`) to extract musical attributes based on the canonical request (artists, target, modifiers).
- [x] Define the structured output schema for extracted attributes (genre, mood, energy, tempoBpm, instruments, rhythm_traits, production_traits).
- [x] Defer phase-level style profile caching. Extraction runs per request/model-eval call at this phase to keep behavior transparent during model benchmarking.
- [x] Add a style-extraction compare/eval command (`prompt_style_extract_compare`) that consumes case fixtures and reports model/provider pass/fail with field-level mismatches.

## Technical Notes
- This is the "smartest" part of the pipeline; use the most capable model available to ensure accurate attribute extraction from vague artist references.
- Tempo must resolve to a single BPM value in the returned schema. If the LLM is uncertain, it may provide a hint internally, but the structured API field exposed to the rest of the pipeline should be `tempoBpm`.
- Do not wire `cognesy/instructor-php` directly inside feature code; route all provider calls through `CakeInstructor` as the shared integration layer.
- Do not rely on lexical/rules post-processing to "fix" extracted content. Normalization should remain structural only (type/shape validation, trimming/lowercasing where needed, deduplication).
- Include provider/model benchmarking fixtures so cost/quality tradeoffs can be evaluated with deterministic expected outputs before connection defaults are changed.
- Do not add prompt-layer caching in this phase. If caching is introduced later, it belongs to pipeline/application orchestration boundaries, not `App\Prompt\StyleExtractor`.
