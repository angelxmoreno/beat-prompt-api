# Prompt Normalization Policy

## Purpose
This document freezes the normalization contract for prompt pipeline phases so behavior stays predictable as models/providers are swapped.

## Core Rule
LLMs own semantic intent extraction and transformation.

Application code may only perform minimal structural normalization required for stable typed handling and cache behavior.

## Allowed Structural Normalization
- Trim and normalize whitespace.
- Lowercase where canonical casing is explicitly required for stable key generation.
- Deduplicate list fields.
- Normalize ordering where deterministic keys require it.
- Validate required shape/types and throw explicit domain errors when invalid.

## Disallowed Post-Processing
- Lexical/rules stopword stripping to "fix" model outputs.
- Keyword or pattern matching used as semantic fallback intent detection.
- Provider-specific hardcoded behavior that remaps user intent labels.

## Field-Level Expectations
- `kind`: produced by LLM; code may validate against accepted enum and fail on invalid output.
- `artists`: produced by LLM; code may apply canonical structural formatting for stability (for example lowercase + punctuation removal).
- `target`: produced by LLM; code should only apply structural normalization (trim/lowercase + empty fallback), not semantic allowlists.
- `modifiers`: produced by LLM; code may trim/lowercase/deduplicate only.

## Validation Harness Requirement
Each prompt phase must provide a compare/eval command that:
- accepts a JSON case set (`input` + `expected`),
- runs the phase against a specified connection/model,
- reports pass/fail and mismatch details per case,
- returns non-zero on failures for CI or local gating.

This harness is required so model/provider quality can be measured objectively before changing defaults.
