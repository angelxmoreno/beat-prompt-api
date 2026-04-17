# TICKET-003: Implement Phase 1 - Style Extraction

## Description
Implement Phase 1 of the prompt pipeline: Style Extraction. This phase takes the canonical request from Phase 0 and infers musical attributes using a high-quality LLM, with `CakeInstructor` as the shared integration layer for typed structured extraction.

Prompt class convention:
- Namespace: `App\Prompt\<Feature>\<Class>`
- Path: `src/Prompt/<Feature>/<Class>.php`

## Tasks
- [ ] Create `App\Prompt\StyleExtractor\StyleExtractor` (`src/Prompt/StyleExtractor/StyleExtractor.php`).
- [ ] Implement integration with the primary LLM through `CakeInstructor` (`CakeInstructor::extract(...)` or injected `InstructorStructuredExtractor`) to extract musical attributes based on the canonical request (artists, target, modifiers).
- [ ] Define the structured output schema for extracted attributes (genre, mood, energy, tempoBpm, instruments, rhythm_traits, production_traits).
- [ ] Implement caching for the extracted style profile using the canonical cache key generated in Phase 0 (e.g., under an `artist_profile|...` namespace).

## Technical Notes
- This is the "smartest" part of the pipeline; use the most capable model available to ensure accurate attribute extraction from vague artist references.
- Tempo must resolve to a single BPM value in the returned schema. If the LLM is uncertain, it may provide a hint internally, but the structured API field exposed to the rest of the pipeline should be `tempoBpm`.
- Do not wire `cognesy/instructor-php` directly inside feature code; route all provider calls through `CakeInstructor` as the shared integration layer.
- Do not rely on lexical/rules post-processing to "fix" extracted content. Normalization should remain structural only (type/shape validation, trimming/lowercasing where needed, deduplication).
