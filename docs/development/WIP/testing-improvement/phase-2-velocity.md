# Phase 2 - Velocity (Execution Speed and Parallelization)

## Goal
Preserve fast feedback while keeping quality checks deterministic.

## Baseline (2026-03-25)
- Parallel runtime is excellent: 113.57s with 10 processes.
- Existing lane budget tooling is present (`scripts/ci/check-test-lane-runtime-budgets.php`).

## Plan
1. Keep `php artisan test --parallel --processes=10` as default fast lane.
2. Keep coverage sequential to avoid OOM and preserve report correctness.
3. Add gate summary script that reports elapsed times and warns when quality checks exceed expected ranges.

## Deliverables
- Runtime visibility in consolidated quality report.
- Explicit CI recommendation order: tests -> coverage -> mutation -> quality gate.

## Success Criteria
- Fast lane remains under 150s median for current suite size.
- Quality pipeline remains deterministic with clear per-phase timings.

---

## Wave 2 Assessment (2026-03-25)

### Findings from live data
- **113.57s for 6346 tests across 10 processes** — well inside the 150s budget.
- `TIMING_TESTS_S`, `TIMING_COVERAGE_S`, `TIMING_MUTATION_S` env vars are now wired into the quality-gate report for per-phase visibility.
- Lane budget history JSONL files already exist in `reports/`.

### Wave 2 Actions
1. Inject `TIMING_TESTS_S=$TESTS_DURATION` from `test-with-coverage-and-mutation.sh` into the quality gate call so timing appears in gate report automatically.
2. If test count grows past 8000, evaluate moving `--processes=10` to `--processes=14` and validate no OOM occurs.
3. Profile the 8 `!` (skipped) test dots that appear in parallel output — if they are blocked by an unavailable service they may hide real failures.
