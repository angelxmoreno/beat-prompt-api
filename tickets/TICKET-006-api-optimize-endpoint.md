# TICKET-006: Implement POST /optimize Endpoint

## Description
Expose the primary API endpoint for the MVP. The `POST /optimize` route will receive user input, orchestrate the prompt pipeline (Phases 0 through 3), and return the final sanitized prompt along with extracted metadata.

## Tasks
- [ ] Create `Api\PromptController` (or similar) to handle the route.
- [ ] Define the `POST /optimize` route in `config/routes.php`.
- [ ] Create a `Prompt\PromptPipeline` service/facade to orchestrate `Canonicalizer`, `StyleExtractor`, `PromptSynthesizer`, and `PolicyCleaner`.
- [ ] Implement request validation (e.g., requiring `text` string, optional `instrumentalOnly` boolean).
- [ ] Return the required JSON response structure containing: `input`, `normalized`, `canonicalKey`, `style` (attributes), and the final `prompt`.

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
  "style": { ... },
  "prompt": "..."
}
```