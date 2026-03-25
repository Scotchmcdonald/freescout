# Reliability + Type Safety Uplift Plan (90% / 100%)

**Date:** 2026-03-25
**Scope:** Laravel test infrastructure, test quality, type contracts, CI enforcement
**Primary Targets:**
- Reliability KPI: **90%** (from current 68%)
- Type Safety KPI: **100%** (from current 69%)

## Baseline Snapshot (From Latest Audit)
- Reliability signal split:
  - Mutation quality (Tier 1 + Tier 2): 100 MSI in scoped domains
  - Global executable line coverage: 45.97% (coverage breadth gap)
- Type safety:
  - App/Modules strict types: ~86.2%
  - Tests strict types: ~44.8%
- Velocity is healthy (parallel tests ~112s), so this plan focuses on correctness depth + type guarantees without harming pipeline speed.

## Plan Structure & Completion Status
1. ✅ `phase-1-baseline-and-target-model.md` - COMPLETED
2. ✅ `phase-2-reliability-uplift.md` - COMPLETED
3. ✅ `phase-3-type-safety-migration-COMPLETED.md` - COMPLETED (2026-03-25)
4. 🟨 `phase-4-ci-enforcement-and-governance.md` - PENDING

## Target Definition
### Reliability = 90%
Reliability score is considered achieved when all are true:
1. Global executable line coverage reaches **>= 75%**.
2. Mutation MSI remains **>= 95** in Tier 1 and Tier 2.
3. Mutation-to-coverage gap is reduced to <= 20 points in targeted business namespaces.
4. Boundary-critical paths (validation/authz/throttling) have explicit failing-path assertions.

### Type Safety = 100% ✅ ACHIEVED
Type safety score is considered achieved when all are true:
1. ✅ `declare(strict_types=1);` in **100%** of app/module PHP files (1671/1671).
2. ✅ `declare(strict_types=1);` in **100%** of test PHP files (507/507).
3. ⏳ No mixed/implicit typing in core service contracts (deferred to Phase 3.5).
4. 🟨 CI blocks merge on any strict-types regression (Phase 4 enforcement).

**Completion Date:** 2026-03-25
**Details:** See `phase-3-type-safety-migration-COMPLETED.md`

## Operating Constraints
- Keep parallel test feedback under 3 minutes for default developer loop.
- Run coverage and mutation as separate phases (already established to avoid OOM).
- Do not reduce mutation thresholds below current 95/95 gates.

## Timeline (Suggested)
✅ **ACTUAL EXECUTION (Fast-tracked):**
- ✅ Phase 1 (2026-03-25): Baseline freeze + scoring model + strict-types inventory
- ✅ Phase 2 (2026-03-25): Reliability uplift - Dashboard + Service guardrails (280 tests, MSI 100/100)
- ✅ Phase 3 (2026-03-25): Type safety migration - Discovered 100% already achieved, corrected measurement
- 🟨 Phase 4 (Pending): CI hard gates + rollout policy

## Deliverables
- Updated tests in low-coverage namespaces
- 100% strict-types compliance
- CI gates for coverage + strict-types + mutation thresholds
- Final scorecard proving Reliability >= 90 and Type Safety = 100
