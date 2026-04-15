# TICKET-004: Implement Phase 2 - Prompt Synthesis

## Description
Implement Phase 2 of the prompt pipeline: Prompt Synthesis. This phase converts the structured style attributes extracted in Phase 1 into a cohesive, descriptive prompt for music generation tools like Lyria.

## Tasks
- [ ] Create a `Prompt\PromptSynthesizer` service.
- [ ] Develop prompts/templates to instruct a fast, rewrite-oriented LLM to compose a natural language instrumental prompt from the structured JSON attributes.
- [ ] Ensure the generated prompt flows naturally and highlights the requested energy, mood, and instrumentation.
- [ ] Cache the synthesized prompt where appropriate.

## Technical Notes
- Since structured attributes are already available, a cheaper/faster model can be used for this text generation step.