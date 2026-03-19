# Phase 6: Zero-Allowlist Architecture

## Objective

Reach a state where architecture and isolation guards pass without legacy exceptions.

## Current Baseline

- `allowlistedRefreshDatabaseBaseline`: 11
- `allowlistedPathPrefixes`: 2
- `guardedGatewayHotspotPatterns`: 4
- module boundary contract tests currently pass

## Exit Criteria

- allowlisted refresh baseline reaches 0
- allowlisted path prefixes reach 0
- gateway hotspot exceptions are eliminated or made explicit in stronger tests
- architecture guards pass without legacy carve-outs

## Implementation Plan

1. Remove the smallest allowlist entries first.
2. Replace legacy DB-coupled unit tests with true unit or integration coverage.
3. Tighten the guard once each batch is clean.
4. Repeat until no exceptions remain.

## Autonomous Execution Guidance

Autonomy level: medium.

The agent can work autonomously on:
- allowlist burn-down in already-understood files
- test reclassification
- local seam extraction for testability

The agent should pause when:
- removing an allowlist item requires a larger contract or event redesign across modules

## Safe Command Patterns

```bash
php artisan test tests/Unit/ModuleUnitIsolationGuardTest.php tests/Architecture/
grep -n "allowlistedRefreshDatabaseBaseline\|allowlistedPathPrefixes" tests/Unit/ModuleUnitIsolationGuardTest.php
git diff -- tests/Unit/ModuleUnitIsolationGuardTest.php
```

## Effective LLM Prompt

```text
You are executing Phase 6 of the testing roadmap: Zero-Allowlist Architecture.

Work autonomously where possible. Remove legacy allowlist entries by converting tests to the proper layer or isolating the logic under test. Keep the isolation and architecture guards green after each batch.

Do not leave the guard weakened. Tighten it as legacy exceptions are removed.
```
