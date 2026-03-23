# Testing Contribution Guide

This guide defines how to add or modify tests in this repository with high signal, fast feedback, and strong module boundaries.

## Purpose

Use this guide when writing or reviewing tests so contributions align with:
- docs/testing/TESTING_ROADMAP_OUTCOMES.md
- tests/testing_standards.md
- architecture and isolation guard tests

## Quick Start

1. Put the test in the right layer: Unit, Feature, Integration, or Browser.
2. Prefer behavior assertions over framework internals.
3. Use the smallest test scope needed to prove the behavior.
4. Run focused tests first, then broader suites only when needed.
5. Inspect reports/test-results-latest.log after each run.

## Layer Placement Rules

### Unit

Use Unit tests for pure logic and deterministic behavior.

Required:
- no RefreshDatabase
- no direct cross-module persistence
- no cross-module service resolution via app()->make or resolve in unit scope

### Feature

Use Feature tests for controller behavior, authorization, validation, middleware, and request flow.

### Integration

Use Integration tests when persistence, framework wiring, event chains, or external adapters are part of the contract.

### Browser

Use Browser tests only for high-value end-to-end UX journeys.

## High-Signal Assertion Patterns

Prefer:
- state change assertions
- domain event dispatch assertions
- authorization and policy assertions
- HTTP status and structured payload assertions
- side-effect assertions with explicit fakes

Avoid:
- relation-type assertions for framework internals
- broad copy matching where behavior can be asserted directly
- over-mocking the service under test

## External API and HTTP Rules

- Always isolate external HTTP calls with Http::fake or equivalent boundary fakes.
- Never hit live services in test runs.
- Verify request payloads and retry behavior where relevant.

## Mocking Rules

Use mocks only when needed for boundaries.

Prefer:
- contract-level mocks
- fake adapters
- deterministic test doubles

Avoid:
- makePartial on the primary service under test unless there is no viable alternative

## Unit Isolation Rules

These rules are enforced by tests/Unit/ModuleUnitIsolationGuardTest.php and architecture tests.

Contributors must not introduce:
- new RefreshDatabase usage in unit scope
- cross-module persistence in unit tests
- direct cross-module service resolution in unit tests

## Required Local Validation Flow

Run the narrowest relevant checks first:

```bash
php artisan test path/to/changed/test-file.php
```

Then run broader checks as needed:

```bash
php artisan test tests/Unit/ModuleUnitIsolationGuardTest.php
php artisan test tests/Architecture/
php artisan test
```

Important:
- The project already writes test logs to reports/test-results-<timestamp>.log and updates reports/test-results-latest.log.
- Prefer inspecting reports/test-results-latest.log over rerunning expensive suites.

## Pull Request Checklist

- [ ] Tests are in the correct layer.
- [ ] No new unit-scope RefreshDatabase usage.
- [ ] No new cross-module unit coupling.
- [ ] Assertions focus on behavior, not framework internals.
- [ ] External HTTP interactions are faked.
- [ ] Touched tests pass.
- [ ] Architecture and isolation checks remain green when applicable.
- [ ] Any temporary exception is documented with an owner and expiry date.

## Autonomous Agent Contribution Mode

When an LLM agent is working in this repository, autonomous execution is expected for:
- read-only inspection
- minimal focused edits
- non-destructive test runs
- report-log analysis

The agent should pause only when:
- requirements are ambiguous
- a major architecture decision is required
- a change would alter business behavior rather than test quality

## Ownership

- QA Lead: policy and cadence stewardship
- Module Owners: test quality in their modules
- Reviewers: enforce layer placement and isolation compliance

Last updated: 2026-03-19
