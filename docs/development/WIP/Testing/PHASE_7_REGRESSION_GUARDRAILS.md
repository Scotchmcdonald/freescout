# Phase 7: Regression Guardrails

## Objective

Prevent the suite from sliding backward after cleanup phases land.

## Current Need

The suite already has guard tests, but the enforcement model is still incomplete:
- mutation is not yet integrated in CI
- coverage artifacts are not published
- flaky-tag workflow is not established
- the maintenance cadence document referenced by standards is missing

## Exit Criteria

- new junk tests are blocked quickly
- new unit DB violations fail fast
- maintenance and flaky triage workflow is documented
- regression checks run automatically in the main developer path

## Implementation Plan

1. Add missing docs for contribution and maintenance cadence.
2. Promote guard checks into explicit CI stages.
3. Add issue or label conventions for flaky triage.
4. Make regression metrics visible after each significant test change.

## Autonomous Execution Guidance

Autonomy level: high.

The agent should autonomously:
- add or fix documentation
- harden existing checks
- update CI commands and reporting structure

Pause only if:
- the team needs a policy decision on what should block merges

## Safe Command Patterns

```bash
php artisan test tests/Architecture/
php artisan test tests/Unit/ModuleUnitIsolationGuardTest.php
grep -RIn "flaky-triage\|quarantine" tests Modules --include='*.php'
```

## Effective LLM Prompt

```text
You are executing Phase 7 of the testing roadmap: Regression Guardrails.

Work autonomously where possible. Your job is to prevent backsliding by strengthening docs, CI guard stages, and test hygiene enforcement. Reuse existing architecture and isolation tests where possible instead of inventing duplicate checks.

Continue until the repo has clearer automated guardrails and maintenance instructions.
```
