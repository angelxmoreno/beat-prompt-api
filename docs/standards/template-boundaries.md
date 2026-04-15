# Template Boundary Standard

## Rule

A template repository should keep reusable infrastructure and remove inherited product behavior.

## Why

Copied code often carries hidden product assumptions: data model shape, public endpoints, policies, background behaviors, demo data, and tests that enforce someone else's business logic. Those assumptions are expensive because they look legitimate until a new project grows around them.

The template should be small, legible, and safe to extend. New work should start from intentional baseline behavior, not from old application residue.

## Keep

- framework setup
- genuinely reusable authentication or integration plumbing
- neutral models that still serve retained infrastructure
- generic tests that validate the current template surface

## Remove

- source-project controllers, tables, entities, enums, policies, and behaviors
- source-project migrations and seeds that define product schema or sample content
- tests, fixtures, and docs for removed subsystems
- public endpoints that only existed for the old product

## Required Practices

- Re-evaluate copied code by purpose, not by file location.
- Keep schema, docs, and tests aligned with the retained template scope.
- If a retained subsystem is reusable but opinionated, document the assumption clearly.

## Review Questions

- Does this code express platform capability or source-product behavior?
- If a new project started here today, would this file still be the right default?
- Are docs and tests reinforcing the current template, or the old application?
