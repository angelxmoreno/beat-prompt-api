# API Routing Standard

## Rule

API routes must be explicit.

## Why

Explicit routing keeps the public surface controlled, reviewable, and predictable. Implicit routing is convenient during prototyping, but it makes it easier to expose endpoints accidentally through framework convention.

In template code, route definitions should tell a reviewer exactly which endpoints exist. If an endpoint is not intentionally part of the contract, it should not be reachable.

## Required Practices

- Define API endpoints with explicit `connect()` or `resources()` calls.
- Keep API route declarations limited to intentionally supported endpoints.
- Treat public unauthenticated endpoints as exceptions that require a stated reason.
- Remove inherited routes when the corresponding subsystem is removed.

## Not Allowed

- `->fallbacks()` inside `/api` or prefixed API scopes in non-prototype code
- Leaving controllers reachable only because CakePHP can infer them
- Keeping source-project endpoints in a template without clear intent

## Review Questions

- Can a reader identify the full API surface from `config/routes.php`?
- Would adding a controller accidentally expose a new API action?
- Is each public endpoint intentional and documented enough to defend?
