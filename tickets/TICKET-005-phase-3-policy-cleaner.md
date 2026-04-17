# TICKET-005: Implement Phase 3 - Policy Cleaner & Final Validator

## Description
Implement Phase 3 of the prompt pipeline: Policy Cleaner. This phase ensures the final prompt is safe for external AI music generators by removing any real artist names, song titles, or policy-violating direct references.

## Tasks
- [ ] Create a `Prompt\PolicyCleaner` service.
- [ ] Implement a rules-based primary pass to strip out phrases like "type beat", "in the style of", and specific artist names identified in Phase 0.
- [ ] Implement an optional, cheap LLM fallback (through `CakeInstructor`) to rewrite sentences if the rule-based replacement damages the semantic meaning of the prompt.
- [ ] Ensure the final prompt preserves the intended musical qualities without direct attribution.

## Acceptance Criteria
- The final output prompt must never contain the original artist's name or risky phrasing.
- Any optional LLM fallback in this phase must use `CakeInstructor` as the shared integration layer.
