# Static Analysis Standard

## Rule

Static analysis configuration must reflect the current codebase and remain trustworthy.

## Why

A stale baseline hides signal. Once suppressions outlive the code that justified them, static analysis stops telling the truth about the repository. That is especially dangerous in template repos, where future projects will inherit whatever standards debt is left behind.

The default posture should be: keep analysis clean, fix real issues, and remove ignores that no longer match the repo.

## Required Practices

- Prefer fixing PHPStan findings over adding new ignore entries.
- Remove ignore entries when files or behaviors are deleted.
- Keep config small enough that a failure is actionable.
- Re-run static analysis after structural cleanup and update config immediately if it no longer matches the repo.

## Baseline Policy

- A baseline is allowed only as temporary debt tracking.
- A baseline must not reference deleted files.
- A baseline must not be used to preserve inherited problems from removed code.
- If the remaining issues are few and local, fix them instead of regenerating a broad baseline.

## Review Questions

- Does the analysis config describe the current codebase or an older one?
- Are suppressions justified by present code?
- Is a new ignore hiding a real type or architecture problem that should be fixed instead?
