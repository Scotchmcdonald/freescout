# Phase 0: Stabilize

## Objective

Restore a trustworthy baseline so later cleanup work is not built on noisy failures.

## Current Baseline

**PHASE 0 COMPLETE — 2026-03-19**

- Architecture suite green: 65 passed (84 assertions) ✅
- Isolation guard green: 2 passed ✅
- 3 consecutive parallel green runs achieved: 5460 passed, 2 skipped, 0 failures ✅
- Root cause of recurring failures identified — all were parallel test pollution from `CustomerControllerTest` using common first names ("John") shared with adjacent tests across worker processes
- Fix: [tests/Integration/Controllers/CustomerControllerTest.php](../../../../tests/Integration/Controllers/CustomerControllerTest.php) — replaced generic names ("John"/"Jane") with `UniqueFirstA/B/C/D` to prevent cross-worker search result contamination
- Commit: `894b5f8e2` — `fix: use unique names in CustomerControllerTest to prevent parallel test pollution`

## Ready To Start Checklist

- [x] Contribution guide exists: `docs/development/TESTING_CONTRIBUTION_GUIDE.md`
- [x] Maintenance cadence exists: `docs/development/TEST_MAINTENANCE_CADENCE.md`
- [x] Phase execution pack exists: `docs/development/WIP/Testing/README.md`
- [x] Run architecture suite and capture latest report
- [x] Triage top recurring failures from recent logs
- [x] Establish 3 consecutive canonical green runs

## Exit Criteria

- 3 consecutive canonical green runs are documented in `reports/`
- architecture guard suite is green
- recurring failures are triaged and tracked
- any flaky tests are explicitly marked and documented

## Implementation Plan

1. Verify architecture and isolation guards first; if any fail, fix the smallest blocker.
2. Re-run the architecture suite and confirm green.
3. Triage the top recurring failures from recent logs.
4. Fix the smallest set of reliability blockers.
5. Produce 3 consecutive green runs.

## High-Value Targets

- inspect `tests/Architecture/EnhancedArchitectureTest.php`
- inspect recurring failures in:
  - CaseManager listener integration tests
  - PIB integration and unit failures
  - ContractManager property tests
  - SoftwareSubscriptions LicenseDeployment tests

## Autonomous Execution Guidance

Autonomy level: high.

The agent should proceed without waiting when:
- fixing obvious architecture guard failures
- running targeted architecture tests
- reviewing recent report logs
- fixing obvious broken expectations, missing docs, or stale test assumptions

The agent should pause only if:
- a fix requires changing business behavior rather than test behavior
- multiple modules need a shared architectural rewrite
- a failure exposes ambiguous product requirements

## Safe Command Patterns

```bash
php artisan test tests/Architecture/
php artisan test tests/Unit/ModuleUnitIsolationGuardTest.php
grep -A 8 "FAILED" reports/test-results-latest.log
tail -n 40 reports/test-results-latest.log
git diff --stat
```

## Effective LLM Prompt

```text
You are executing Phase 0 of the testing roadmap: Stabilize.

Work autonomously where possible. You may inspect files, edit docs and tests, run targeted test commands, and read reports logs without asking for permission. Prefer php artisan test and reports/test-results-latest.log for validation. Do not use destructive commands.

Goals:
1. Fix the current hard architecture failure.
2. Triage and, where practical, fix the highest-frequency failing tests from recent logs.
3. Produce the evidence needed to move toward 3 consecutive green runs.

Required behavior:
- inspect before editing
- make minimal changes
- run the narrowest relevant test slice after each change
- summarize what was fixed, what remains, and whether the suite is stable enough for Phase 1

If you hit an ambiguous product or architectural decision, stop and report the decision point clearly. Otherwise continue autonomously until Phase 0 is complete.
```
