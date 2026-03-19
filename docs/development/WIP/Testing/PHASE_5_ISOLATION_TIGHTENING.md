# Phase 5: Isolation Tightening

## Objective

Reduce cross-module coupling and prepare the suite for zero-allowlist enforcement.

## Current Baseline

- isolation guard currently passes
- architecture suite still has a separate documentation failure until Phase 0 completes
- allowlisted unit DB baseline: 11 files
- guarded hotspot patterns: 4
- modules without listeners: 7 of 16

## Exit Criteria

- allowlist is materially smaller than the current baseline
- guarded hotspot tests no longer over-mock gateway internals
- cross-module coupling remains at 0 in unit scope

## Implementation Plan

1. Burn down the allowlist in small batches.
2. Replace direct over-mocking of gateways with external boundary fakes.
3. Push cross-module coordination toward contracts, events, or integration tests.

## Autonomous Execution Guidance

Autonomy level: medium.

The agent should proceed autonomously when:
- converting a known allowlisted file off `RefreshDatabase`
- replacing service-internal mocks with boundary fakes
- strengthening guard tests after cleanup

The agent should pause only if:
- a module boundary must change in production code
- an event-vs-contract choice requires team direction

## Safe Command Patterns

```bash
php artisan test tests/Unit/ModuleUnitIsolationGuardTest.php
grep -RIl "RefreshDatabase" Modules/*/Tests/Unit --include='*.php'
grep -RIn "Mockery::mock" Modules/*/Tests --include='*.php'
```

## Effective LLM Prompt

```text
You are executing Phase 5 of the testing roadmap: Isolation Tightening.

Work autonomously where possible. Reduce the allowlist, remove over-mocking of gateway internals, and preserve strict module isolation. Use the ModuleUnitIsolationGuardTest as the safety rail and keep changes incremental.

Continue without asking when the work is clearly a test-isolation cleanup. Pause only for real architecture decisions.
```
