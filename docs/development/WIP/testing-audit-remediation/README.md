# Testing Infrastructure Remediation Plan

**Audit Score:** 62/100 — "Operational but fragile"
**Priority:** High — coverage telemetry unavailable on-demand; 2 known flaky tests in CI path

## Background

A full executive audit of the test suite was run against the current `main` branch. The audit scored 5 weighted KPIs:

| KPI | Score | Weight | Contribution |
|-----|-------|--------|-------------|
| Test Reliability (flaky, coverage repro) | 45/100 | 25% | 11.25 |
| Velocity (suite speed, parallelism) | 72/100 | 20% | 14.40 |
| Architecture Fitness | 78/100 | 25% | 19.50 |
| Boundary Coverage (auth, rate-limit, contracts) | 62/100 | 20% | 12.40 |
| Type Safety Breadth | 61/100 | 10% | 6.10 |
| **Total** | | | **63.65 / 100** |

## Criticism Index → Phase Files

| # | Criticism | Phase File | Target Score Δ |
|---|-----------|------------|----------------|
| 1 | Coverage infrastructure OOM — `phpunit.xml` memory cap; no reproducible full-suite coverage | [phase-1-coverage-infrastructure.md](phase-1-coverage-infrastructure.md) | Reliability +25 |
| 2 | Two flaky tests in Integration suite (unique constraint, brittle output assertion) | [phase-2-flaky-tests.md](phase-2-flaky-tests.md) | Reliability +10 |
| 3 | `tests/Unit/` globally bound to `UnitTestCase` (framework + `RefreshDatabase`) instead of `PureUnitTestCase` | [phase-3-unit-base-class.md](phase-3-unit-base-class.md) | Architecture +8 |
| 4 | Throttle/rate-limiting boundary coverage concentrated in one file; 15+ API surfaces unprotected | [phase-4-throttle-boundary-coverage.md](phase-4-throttle-boundary-coverage.md) | Boundary +15 |
| 5 | 191/491 test PHP files lack `declare(strict_types=1)` (61.1%) | [phase-5-strict-types-enforcement.md](phase-5-strict-types-enforcement.md) | Type Safety +20 |

## Execution Order

Phases are ordered by impact-to-effort ratio. Complete them sequentially — phase 2 and 3 are blockers for an accurate re-score because they affect the reproducibility of all subsequent runs.

```
Phase 1 → Phase 2 → Phase 3 → Phase 4 → Phase 5
(~2h)      (~1h)     (~2h)     (~3h)     (~2h)
```

## Re-score Checkpoint

After completing all phases, re-run the executive audit:

```bash
# Verify clean parallel run (no flakiness)
php artisan test --parallel --processes=10

# Verify coverage now works without OOM
XDEBUG_MODE=coverage php artisan test --coverage --min=80

# Verify arch rules pass
php artisan test tests/ArchTest.php tests/Architecture/ --parallel --processes=4
```

Expected post-remediation score: **82–85 / 100**

## Cleanup

Once all phases are verified green in CI, delete this WIP folder:

```bash
rm -rf docs/development/WIP/testing-audit-remediation/
```
