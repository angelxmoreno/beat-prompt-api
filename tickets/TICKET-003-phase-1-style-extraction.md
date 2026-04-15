# TICKET-003: Implement Phase 1 - Style Extraction

## Description
Implement Phase 1 of the prompt pipeline: Style Extraction. This phase takes the canonical request from Phase 0 and infers musical attributes using a high-quality LLM, with PHP Instructor shaping the output into a typed contract.

## Tasks
- [ ] Create a `Prompt\StyleExtractor` service.
- [ ] Implement integration with the primary LLM through PHP Instructor to extract musical attributes based on the canonical request (artists, target, modifiers).
- [ ] Define the structured output schema for extracted attributes (genre, mood, energy, tempoBpm, tempoSource, instruments, rhythm_traits, production_traits).
- [ ] Implement caching for the extracted style profile using the canonical cache key generated in Phase 0 (e.g., under an `artist_profile|...` namespace).

## Technical Notes
- This is the "smartest" part of the pipeline; use the most capable model available to ensure accurate attribute extraction from vague artist references.
- Tempo must resolve to a single BPM value in the returned schema. If the LLM is uncertain, it may provide a hint internally, but the structured API field exposed to the rest of the pipeline should be `tempoBpm`.
