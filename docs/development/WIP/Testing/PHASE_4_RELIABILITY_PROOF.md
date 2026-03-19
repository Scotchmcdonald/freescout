# Phase 4: Reliability Proof

## Objective

Demonstrate repeatable green execution, not just one-off passing runs.

## Current Baseline

- current canonical green streak: 1
- longest observed canonical streak: 5
- no flaky tags are currently in use
- repeated failures cluster in a small set of modules

## Exit Criteria

- 3 consecutive canonical green parallel runs are documented
- no hangs or unexplained timeouts occur during the validation sequence
- known flaky cases are either fixed or explicitly tagged and tracked

## Implementation Plan

1. Re-run the stable lane repeatedly.
2. Inspect the log after every run instead of re-running blindly.
3. Fix or quarantine repeat offenders.
4. Preserve the three-run evidence trail in `reports/`.

## Autonomous Execution Guidance

Autonomy level: high.

The agent should autonomously:
- run the same lane repeatedly
- inspect `reports/test-results-latest.log`
- patch obvious test ordering, fixture, timing, or stale expectation issues
- continue until the streak target is reached or a true blocker is found

Pause only if:
- failures indicate nondeterministic infrastructure issues outside the repo
- quarantine would hide an unclear product regression

## Safe Command Patterns

```bash
php artisan test
tail -n 30 reports/test-results-latest.log
grep -A 8 "FAILED" reports/test-results-latest.log
ls -1t reports/test-results-*.log | head
```

## Effective LLM Prompt

```text
You are executing Phase 4 of the testing roadmap: Reliability Proof.

Work autonomously where possible. Run the relevant lane, inspect the latest saved report, fix repeatable failures, and keep iterating until you either achieve a 3-run green streak or isolate a real blocker. Do not waste cycles rerunning without inspecting the saved logs.

Document the streak evidence clearly and identify any tests that should be tagged for flaky triage.
```
