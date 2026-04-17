# TICKET-004: Implement Phase 2 - Prompt Synthesis

## Description
Implement Phase 2 of the prompt pipeline: Prompt Synthesis. This phase converts the structured style attributes extracted in Phase 1 into a cohesive, descriptive prompt for music generation tools like Lyria.

## Tasks
- [ ] Create a `Prompt\PromptSynthesizer` service.
- [ ] Develop prompts/templates to instruct a fast, rewrite-oriented LLM (invoked via `CakeInstructor`) to compose a natural language instrumental prompt from the structured JSON attributes, including a single `tempoBpm` value when present.
- [ ] Ensure the generated prompt flows naturally and highlights the requested energy, mood, instrumentation, and exact BPM when the backend has one.
- [ ] Cache the synthesized prompt where appropriate.

## Technical Notes
- Since structured attributes are already available, a cheaper/faster model can be used for this text generation step.
- Tempo should be treated as a concrete scalar here, not a range. If the upstream profile only has an inferred BPM, the synthesis prompt should still render a single BPM value in the text output.
- Keep LLM integration concerns in `CakeInstructor`; `PromptSynthesizer` should depend on the shared integration layer rather than provider-specific SDK calls.
