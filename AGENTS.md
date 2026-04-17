# AGENTS

This repository is a CakePHP project template. Treat it as reusable infrastructure, not as an inherited product codebase.

## Core Standards

- Keep the template generic. Do not introduce or retain domain-specific MVC, policies, seeds, fixtures, or tests unless they are intentionally part of the new project.
- Make public API surface explicit. Prefer named or explicit routes over implicit controller exposure.
- Favor removal over inheritance when copied code no longer serves the template.
- Keep static analysis current. Fix real issues when practical and remove stale suppressions when code is removed.
- Preserve reusable auth and platform plumbing only when it is still intentionally part of the template.

## Routing Policy

- Do not use `->fallbacks()` inside API-prefixed scopes in non-prototype code.
- Public endpoints must be explicitly declared in routing and intentionally documented.
- Do not leave placeholder or inherited endpoints exposed just because a controller exists.
- Any public unauthenticated endpoint must have an explicit reason in code or docs.

## Template Boundary Policy

- Before keeping copied code, ask whether it is framework infrastructure or old product behavior.
- Remove copied domain code that encodes product concepts, business rules, seeded content, or public API shape from the source project.
- Keep tests aligned with the retained surface area. Do not keep tests for removed subsystems.
- Keep migrations aligned with the intended template baseline, not the source app's schema.

## Static Analysis Policy

- Prefer code fixes over adding new PHPStan baseline entries.
- Do not keep baseline ignores for files that no longer exist.
- Treat the baseline as temporary debt accounting, not a permanent shield.
- When the codebase shrinks, simplify analysis config to match the current repo.

## Review Expectations

Reviewers and agents should explicitly check:

- Is any API route implicit when it should be explicit?
- Is any retained code still tied to the source product instead of the template?
- Is any endpoint public without an intentional reason?
- Is static analysis being suppressed instead of corrected?
- Are docs, tests, and config consistent with the current codebase?

## Change Discipline

- When making structural cleanup changes, update the docs and tests in the same change.
- Leave short, durable rationale near sensitive boundaries when a rule is easy to accidentally undo.
- Do not reintroduce removed product behavior without documenting why the template now needs it.
- Before handing work back, run `composer push-check` at the repository root and fix all violations it reports.
