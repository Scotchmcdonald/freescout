# Phase 3 - Architecture (Test Taxonomy and Arch Governance)

## Goal
Ensure unit/feature/integration boundaries stay intentional and architecture rules remain enforceable.

## Baseline (2026-03-25)
- Dedicated suites and base classes exist (`UnitTestCase`, `PureUnitTestCase`, `FeatureTestCase`, `IntegrationTestCase`).
- Arch tests are present (`tests/ArchTest.php`, `tests/Architecture`).

## Plan
1. Add explicit architecture score checks into the quality-gate summary (presence + pass status of Arch tests).
2. Flag unit tests that use framework-heavy traits where pure unit alternatives exist.
3. Keep architecture checks as blocking in CI.

## Deliverables
- Architecture section in test quality report.
- Guidance notes for migrating heavy unit tests to `PureUnitTestCase` where possible.

## Success Criteria
- Architecture checks are visible and attributable in one consolidated report.
- No regressions in suite separation policy.

---

## Wave 2 Assessment (2026-03-25)

### Findings from live data
- **8 arch-test files** found in `tests/Architecture/`: BillingPaymentTypeCoverageGuardTest, CriticalNamespaceBoundaryGuardTest, EnhancedArchitectureTest, InterfaceSegregationTest, LayerTest, ModuleBoundariesTest, ModuleBoundaryContractsTest, NamingConventionsTest.
- **All unit tests correctly use `PureUnitTestCase`** — zero framework-booting unit tests detected. Architecture Score = very high.
- `tests/ArchTest.php` contains 17 arch rules including strict-types, enums, readonly DTOs, core blindness, security, and queue isolation.

### Wave 2 Actions
1. The gate now lists arch files with counts — next step is **asserting the arch suite passes** (run `php artisan test tests/Architecture tests/ArchTest.php` and store exit code in gate).
2. Add an arch rule asserting that `App\Http\Controllers` never instantiate Services directly via `new` — use constructor injection only.
3. Add a Module-level arch rule for each domain module: verify `Services` namespace is `final` to prevent inheritance-based tight coupling.
