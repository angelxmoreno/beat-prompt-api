# TICKET-005: Implement Phase 3 - Policy Cleaner & Final Validator

## Description
Implement Phase 3 of the prompt pipeline: Policy Cleaner. This phase ensures the final prompt is safe for external AI music generators by removing any real artist names, song titles, or policy-violating direct references.

Prompt class convention:
- Namespace: `App\Prompt\<Feature>\<Class>`
- Path: `src/Prompt/<Feature>/<Class>.php`

## Tasks
- [ ] Create `App\Prompt\PolicyCleaner\PolicyCleaner` (`src/Prompt/PolicyCleaner/PolicyCleaner.php`).
- [ ] Implement LLM-based policy cleaning via `CakeInstructor` with explicit policy constraints and structured validation feedback.
- [ ] Apply only minimal structural normalization after cleaning (format/whitespace/shape normalization), with no lexical/rules stripping pipeline.
- [ ] Ensure the final prompt preserves the intended musical qualities without direct attribution.
- [ ] Add a policy-cleaner compare/eval command (for example `prompt_policy_clean_compare`) with pass/fail fixtures for safety/compliance outputs across providers/models.

## Acceptance Criteria
- The final output prompt must never contain the original artist's name or risky phrasing.
- This phase must not depend on lexical/rules post-processing; safety/compliance should come from model instructions + constrained output.
- Benchmark fixtures and compare command output must make provider/model quality visible before changing default deployment connection.
