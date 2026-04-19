# TICKET-006: Implement POST /optimize Endpoint

## Description
Expose the primary API endpoint for the MVP. The `POST /optimize` route will receive user input, orchestrate the prompt pipeline (Phases 0 through 3), and return the final sanitized prompt along with extracted metadata.

Prompt class convention:
- Namespace: `App\Prompt\<Feature>\<Class>`
- Path: `src/Prompt/<Feature>/<Class>.php`

## Tasks
- [ ] Create `Api\PromptController` (or similar) to handle the route.
- [ ] Define the `POST /optimize` route in `config/routes.php`.
- [ ] Create `App\Prompt\PromptPipeline\PromptPipeline` (`src/Prompt/PromptPipeline/PromptPipeline.php`) to orchestrate:
  - `App\Prompt\Canonicalizer\Canonicalizer`
  - `App\Prompt\StyleExtractor\StyleExtractor`
  - `App\Prompt\PromptSynthesizer\PromptSynthesizer`
  - `App\Prompt\PolicyCleaner\PolicyCleaner`
- [ ] Implement request validation (e.g., requiring `text` string, optional `instrumentalOnly` boolean, optional explicit BPM input if supported later).
- [ ] Return the required JSON response structure containing: `input`, `normalized`, `canonicalKey`, `style` (attributes), and the final `prompt`.
- [ ] Ensure the response shape uses a single `tempoBpm` scalar in `style` rather than a BPM range.
- [ ] Ensure all LLM-backed phases in the pipeline call through `CakeInstructor` (no direct provider/Instructor SDK calls from controller or pipeline facade).
- [ ] Ensure pipeline phases do not rely on lexical/rules post-processing for content correction; only minimal structural normalization is allowed between phase boundaries.
- [ ] Ensure prompt modules remain stateless with no phase-local caching; if caching is needed, implement it only at pipeline/app orchestration boundaries.
- [ ] Add endpoint-level evaluation harness command (for example `prompt_pipeline_compare`) that executes fixture inputs end-to-end and reports phase-aware mismatches by connection/model.

## Example Request
```json
{
  "text": "Joyner Lucas type beat",
  "instrumentalOnly": true
}
```

## Example Response
```json
{
  "input": "...",
  "normalized": "...",
  "canonicalKey": "...",
  "style": {
    "tempoBpm": 94
  },
  "prompt": "..."
}
```

## Benchmarking Requirement
- Keep compare/eval commands for each phase (and pipeline-level) so model/provider swaps can be evaluated with deterministic fixtures before rollout.
