# Phase 1 - Reliability (Coverage vs Mutation Depth)

## Goal
Raise confidence that tests validate behavior, not just line execution, by reducing the coverage-to-mutation gap.

## Baseline (2026-03-25)
- Parallel test health is strong: 6346 passed, 2 skipped.
- Combined parallel+coverage is known to be unstable in this repo; coverage and mutation must run in separate phases.
- Mutation tooling exists (`scripts/ci/check-mutation-tier2.sh`) but score gating should be consolidated and visible in CI summaries.

## Plan
1. Add a CI quality summary gate script that reads latest coverage and mutation outputs and enforces explicit thresholds.
2. Standardize output artifacts so thresholds are auditable (`reports/testing-quality-gate-latest.md`).
3. Add remediation guidance in gate output for escaped mutants.

## Deliverables
- `scripts/ci/check-testing-quality-gate.php`
- CI pipeline integration from `scripts/ci.sh`

## Success Criteria
- CI fails when mutation/coverage floor is missed.
- Gate output includes exact measured values and threshold deltas.

---

## Wave 2 Assessment (2026-03-25)

### Findings from live data
- **Mutation MSI = 100%** (from `reports/infection-extended-summary.json`) — extremely strong reliability signal for the scanned tier-2 scope.
- **Coverage artifact absent** — `reports/coverage-final.txt` has never been committed. The gate correctly reports this as a FAIL and provides the command to generate it.
- `check-testing-quality-gate.php` now reads infection JSON directly; text-log fallback is retained for older environments.

### Wave 2 Actions
1. Run `XDEBUG_MODE=coverage php artisan test` in CI after parallel-test phase and commit `reports/coverage-final.txt` as a CI artifact.
2. Raise `TEST_MIN_MSI` to **70** once coverage is confirmed (current 100% on tier-2 scope warrants ambition).
3. Add trend tracking: store daily gate results to `reports/quality-gate-history.jsonl` so regression is visible over sprint boundaries.
