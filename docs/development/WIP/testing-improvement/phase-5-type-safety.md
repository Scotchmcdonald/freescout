# Phase 5 - Type Safety (Strict Types and Test Type Discipline)

## Goal
Enforce consistent strict typing in test code and make regressions impossible.

## Baseline (2026-03-25)
- Arch tests assert strict types for `Tests`, but many existing test files still miss `declare(strict_types=1);`.
- CI strict-types script currently audits `app/` and `Modules/` only.

## Plan
1. Extend strict-types CI check to include `tests/` PHP files (excluding generated/browser JS assets).
2. Add `declare(strict_types=1);` to the most impactful missing test files first.
3. Keep output actionable with exact file list and fail-fast behavior.

## Deliverables
- Updated `scripts/ci/check-strict-types.sh` including tests.
- Initial strict-types backfill in selected `tests/` files.

## Success Criteria
- New or modified tests without strict types fail CI immediately.
- Progressive reduction of missing strict-types backlog.

---

## Wave 2 Assessment (2026-03-25)

### Findings from live data
- **`bash scripts/ci/check-strict-types.sh` passes** — all files in `app/`, `Modules/`, and `tests/` already have `declare(strict_types=1);`.
- `tests/ArchTest.php` includes a Pest-level arch assertion `arch('tests strict types')->expect('Tests')->toUseStrictTypes()` — so compliance is double-enforced.
- `tests/Architecture/BillingPaymentTypeCoverageGuardTest.php` asserts 100% strict-types in the `Modules/PIB/Services` and `Modules/Payment/Services` critical domains.

### Wave 2 Actions
1. **Extend domain type-coverage guard** — Add `Modules/ContractManager/Services` and `Modules/SoftwareSubscriptions/Services` to the type-coverage guard baselines (currently only PIB and Payment are covered).
2. **Add return-type density assertion** — Create an arch-test that counts functions with explicit return types across `App\Services` and fails if coverage drops below 95%. PHP 8.1+ makes this feasible with static analysis.
3. **PHPStan baseline tightening** — Raise `phpstan.neon` level to max (currently check what level is set) and target zero ignored errors in `app/Services` as the next type-safety frontier.
