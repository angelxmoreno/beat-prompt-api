# TICKET-002: Implement Phase 0 - Canonicalization

## Description
Implement Phase 0 of the prompt pipeline: Canonicalization and intent resolution. This phase normalizes user input into a canonical request object that can be safely used as a primary cache key.

## Tasks
- [x] Create a `Prompt\Canonicalizer` service.
- [x] Implement input normalization (e.g., lowercase, trimming).
- [x] Implement rule-based intent detection, alias resolution, and phrase-pattern parsing (e.g., mapping "beats like X" and "X type beat" to the same intent).
- [x] Generate a canonical request object with properties: `kind`, `artists`, `target`, and `modifiers`.
- [x] Implement deterministic serialization of the canonical request object to generate a canonical cache key (e.g., `kind:artist_style_prompt|artists:joyner lucas|target:beat|modifiers:`).

## Technical Notes
- This phase must be rules-first, avoiding LLM calls.
- The canonical cache key will be used to look up results for subsequent phases.
- This phase should remain independent of `CakeInstructor`; only downstream LLM-backed phases consume the shared `CakeInstructor` integration layer.
