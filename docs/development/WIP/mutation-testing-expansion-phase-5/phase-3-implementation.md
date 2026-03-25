# Phase 5: Mutation Testing Expansion — Implementation Checklist

**Target Completion:** Week 1–4 of Phase 5 (beginning March 25, 2026)

---

## Pre-Implementation Requirements

- [ ] **Code Freeze:** Notify team of WIP branch work on mutation config.
- [ ] **Baseline Capture:** Run current `./vendor/bin/infection` and commit results to `reports/infection-summary.json`.
- [ ] **Memory Testing:** Verify 3GB heap is available on CI agents.

---

## Week 1: Config Refactor & Fast CI Gate

### Task 1.1: Create infection-extended.json5
**Owner:** QA
**Effort:** 2 hours
**Status:** ✅ **COMPLETED** (Mar 25, 2026)

**Steps:**
1. ✅ Copied `infection.json5` to `infection-extended.json5`.
2. ✅ Expanded `source.directories` to include app/Services and app/Actions.
3. ✅ Set `minMsi: 70` (lower than Tier 1 for growth room).
4. ✅ Updated `logs` paths to include `-extended` suffix (5 log outputs configured).
```

**Expected Mutant Count:** 3,500–4,000 (vs 1,378 in Tier 1).

---

### Task 1.2: Implement check-mutation-tier2.sh
**Owner:** Platform Engineering
**Effort:** 3 hours
**Status:** ✅ **COMPLETED** (Mar 25, 2026)

**File:** `scripts/ci/check-mutation-tier2.sh` ✅ Created & tested

```bash
#!/usr/bin/env bash
# MUTATION TESTING: TIER 2 (App Services + Actions)
# Runs post-PR to gate MSI degradation in critical business logic.
```

**Features Implemented:**
- ✅ Automatic coverage collection if missing (pre-gate).
- ✅ 6-thread parallel mutation execution (~40 min expected).
- ✅ JSON summary parsing and human-readable output.
- ✅ Timeout handling (45 min limit).
- ✅ Escape mutant log reporting.
- ✅ Script is executable and tested.

### Task 1.3: Add to check-architecture-compliance.sh
**Owner:** Platform Engineering
**Effort:** 1 hour
**Status:** ✅ **COMPLETED** (Mar 25, 2026)

**Update:** `scripts/ci/check-architecture-compliance.sh`

✅ Added after BillingPaymentTypeCoverageGuardTest:

```bash
# Phase 5: Mutation testing gate for critical app services (Tier 2)
bash "$SCRIPT_DIR/check-mutation-tier2.sh" || EXIT_CODE=1
```

**Rationale:** Mutation tier 2 now becomes part of architecture/quality gate in CI/CD.

## Week 2: Coverage Merge Fix & Documentation

### Task 2.1: Update phpunit.xml Memory & Coverage Settings
**Owner:** QA
**Effort:** 1 hour
**Status:** ✅ **COMPLETED** (Mar 25, 2026)

**Changes to `phpunit.xml`:**

✅ Added comprehensive comments explaining parallel vs sequential trade-off:
- Documented why parallel + coverage together causes 2GB OOM.
- Explained solution: sequential coverage collection.
- Added memory limit guidance (2GB baseline, 3G for sequential, 4G for mutation).
- References Phase 5 audit findings and CoverageMerger OOM incident.

**File Updated:** `phpunit.xml` lines 43–68

**Key Documentation:**
```
COVERAGE & PARALLEL EXECUTION: IMPORTANT TRADE-OFF
- Running "pest --parallel --processes=10" WITH coverage hits OOM at 2GB.
- Root cause: ParaTest's CoverageMerger aggregates ~600KB-1MB XML per worker.
- Solution: Run tests parallel FIRST, then coverage sequentially.
```
**Owner:** QA
**Effort:** 2 hours

**New File:** `docs/development/testing/coverage-and-mutation-guide.md`

```markdown
# Coverage & Mutation Testing Guide

## Quick Reference

### Local Development: Fast Coverage (Parallel Only)
```bash
# Run tests in parallel, no coverage (2 min)
./vendor/bin/pest --parallel --processes=10
```

### Local Development: Full Coverage (Sequential)
```bash
# Single-process coverage collection (8–10 min)
XDEBUG_MODE=coverage php -d memory_limit=3G ./vendor/bin/pest
```

### CI: Coverage + Mutation (Gated)
```bash
# 1. Test suite (parallel, no coverage) — 2 min
./vendor/bin/pest --parallel --processes=10

# 2. Coverage report (sequential pickup) — 8 min
XDEBUG_MODE=coverage php -d memory_limit=3G \
    ./vendor/bin/pest \
    --coverage-xml=storage/infection/coverage

# 3. Mutation Tier 2 (app/Services scope) — 30–45 min
bash scripts/ci/check-mutation-tier2.sh
```

## Why This Pattern?

- **Why Parallel Without Coverage?**
  - Faster feedback loop.
  - Avoids memory exhaustion.
  - Coverage merge is unnecessary at test runtime.

- **Why Sequential Coverage?**
  - Memory-safe; single worker doesn't need to merge results.
  - Accurate per-line execution tracking.
  - Mutation testing depends on coverage XML accuracy.

- **Why Mutation After Coverage?**
  - Infection uses coverage XML to skip non-executed code.
  - Ensures mutation doesn't waste cycles on untested paths.
```

---

### Task 2.3: Create CI Test Job Template
**Owner:** Platform Engineering
**Effort:** 2 hours
**Status:** ✅ **COMPLETED** (Mar 25, 2026)

**Files Created:**

1. **`scripts/ci/test-with-coverage-and-mutation.sh`** ✅
   - Full bash reference script for 3-phase orchestration.
   - Phase 1: Test execution (parallel, 5 min timeout).
   - Phase 2: Coverage collection (sequential, 10 min timeout).
   - Phase 3: Mutation testing Tier 2 (50 min timeout).
   - Detailed timing output and report paths.
   - Proper error handling and graceful fallback.
   - Executable and tested.

2. **`.github/workflows/mutation-testing-tier2.yml`** ✅
   - Complete GitHub Actions workflow template.
   - Configures PHP 8.3 with Xdebug coverage mode.
   - Caches Composer dependencies for speed.
   - Runs 3-phase pipeline with proper timeouts.
   - Uploads coverage and mutation artifacts.
   - Posts PR comment with test results (GitHub Script).
   - Clear job naming and step annotations.

**Features:**
- ✅ Modular phases with independent timeouts.
- ✅ Artifact upload (coverage + mutation reports).
- ✅ PR comment integration (requires Actions permissions).
- ✅ Memory configuration (3G for coverage, environment-aware).
- ✅ Dependency caching to speed up runs.
- ✅ Cross-platform compatible (runs-on: ubuntu-latest).

## Week 3: Coverage Uplift in app/Services

### Task 3.1: Audit app/Services for Test Coverage
**Owner:** QA / Backend Team
**Effort:** 4 hours

**Steps:**

1. Generate coverage report for `app/Services` only:
   ```bash
   XDEBUG_MODE=coverage php -d memory_limit=3G ./vendor/bin/pest \
       --coverage-xml=storage/infection/coverage \
       --coverage-text=reports/app-services-coverage.txt
   ```

2. Parse coverage to identify:
   - Classes with < 50% line coverage.
   - High-cyclomatic-complexity methods (CRAPi > 20).
   - Untested branches (conditionals without both true/false paths).

3. Output to: `docs/development/WIP/mutation-testing-expansion-phase-5/coverage-audit-app-services.md`

**Example Audit Table:**
| Service | Lines | Coverage | CRAP | Priority |
| :--- | ---: | ---: | ---: | :--- |
| EntitlementEngine | 156 | 42% | 18 | HIGH |
| CacheService | 89 | 78% | 4 | MEDIUM |
| AuditLogService | 203 | 65% | 12 | MEDIUM |

---

### Task 3.2: Add Unit Tests for Top 5 Untested Services
**Owner:** Backend Team
**Effort:** 8 hours (2 hrs per service avg)

**Target:** Increase app/Services coverage from ~35% to ≥ 60%.

**Pattern (tests/Unit/Services/):**
```php
// tests/Unit/Services/CacheServiceTest.php
describe('CacheService', function () {
    test('builds cache keys with namespace', function () {
        $service = new CacheService($cache);
        $key = $service->buildKey('user', 123);
        expect($key)->toBe('app:user:123');
    });

    test('remembers values across calls', function () {
        $service = new CacheService($cache);
        $value = $service->remember('key', 60, fn() => 'computed');

        expect($value)->toBe('computed');
        expect($service->get('key'))->toBe('computed');
    });
});
```

---

### Task 3.3: Add Integration Tests for Critical Workflows
**Owner:** Backend Team
**Effort:** 6 hours (2 hrs per workflow avg)

**Pattern (tests/Integration/Services/):**
```php
// tests/Integration/Services/EntitlementEngineTest.php
test('accrues usage and enforces limits', function () {
    $contract = Contract::factory()->create(['usage_limit' => 100]);
    $engine = app(EntitlementEngine::class);

    $result = $engine->accrueUsage($contract, 50);
    expect($result->isValid())->toBeTrue();

    $result = $engine->accrueUsage($contract, 60);
    expect($result->isValid())->toBeFalse();
    expect($result->error())->toContain('exceeded');
});
```

**Pre-Task:**
- [ ] Identify 5 critical workflows in app/Services.
- [ ] Document which are pure logic (Unit) vs stateful (Integration).
- [ ] Assign owners per service.

---

## Week 4: Documentation & Baseline Capture

### Task 4.1: Finalize Mutation Testing Guide
**Owner:** QA
**Effort:** 3 hours

**File:** `docs/development/mutation-testing-guide.md`

Topics to cover:
- Running mutation locally (Tier 1 + Tier 2).
- Interpreting escaped mutants.
- Using `@infection:ignore-all` for generated code.
- Adding assertions when mutation escapes.
- CI integration and post-PR feedback.

---

### Task 4.2: Commit Baseline & Final Validation
**Owner:** QA
**Effort:** 2 hours

**Steps:**

1. Run full suite and capture:
   ```bash
   # Tier 1
   ./vendor/bin/infection
   cp reports/infection-summary.json reports/baseline-tier1-$(date +%Y%m%d).json

   # Tier 2
   ./vendor/bin/infection --configuration=infection-extended.json5
   cp reports/infection-tier2-summary.json reports/baseline-tier2-$(date +%Y%m%d).json
   ```

2. Create deployment summary:
   - Tier 1 MSI: ___%
   - Tier 2 MSI: ___%
   - app/Services coverage: ___%
   - Total tests: 6,255+
   - CI gate time: ~45 min (Tier 2 post-PR).

3. Commit all WIP files with message:
   ```
   Phase 5: Mutation testing expansion implementation complete

   - Expanded mutation scope from 3 to 8+ directories (Tier 2)
   - Fixed parallel coverage merge OOM via sequential mode
   - Integrated mutation gate in CI (check-mutation-tier2.sh)
   - Improved app/Services test coverage (35% → 60%+)
   - Documented infection best practices & workflows

   Baselines captured in reports/baseline-tier*.json
   Reference: docs/development/WIP/mutation-testing-expansion-phase-5/
   ```

---

### Task 4.3: Validate All Integration Points
**Owner:** Platform Engineering
**Effort:** 2 hours

**Checklist:**

- [ ] `scripts/ci/check-mutation-tier2.sh` runs without errors.
- [ ] `check-architecture-compliance.sh` includes mutation gate.
- [ ] `phpunit.xml` memory/coverage comments updated.
- [ ] `docs/development/testing/coverage-and-mutation-guide.md` is complete.
- [ ] Local `./vendor/bin/infection` still works (Tier 1 intact).
- [ ] CI job template `test-with-coverage-and-mutation.sh` is tested.
- [ ] Coverage audit (`coverage-audit-app-services.md`) populated.
- [ ] Baseline JSON files committed to `reports/`.

---

## Rollout & Team Communication

### Before Deployment
1. Code review of all 4 phase documents.
2. Test on a CI/CD trial run (staging branch).
3. Brief team on new Tier 2 gate and expected PR feedback.

### Deployment
- Merge Phase 5 WIP branch to main.
- Update CI/CD to call `check-mutation-tier2.sh`.
- Add to definition of "done" for PRs: "Mutation Tier 2 passes."

### Post-Deployment Monitoring
- Week 1: Monitor CI run times; adjust threads if > 50 min.
- Week 2: Review escaped mutants; identify common gaps in tests.
- Week 4: Retrospective on coverage uplift effort.

---

## Appendix: Command Reference

```bash
# Run Tier 1 locally (3 service dirs, ~2 hrs)
./vendor/bin/infection

# Run Tier 2 locally (extended scope, ~45 min)
./vendor/bin/infection --configuration=infection-extended.json5

# Run Tier 2 CI gate (what PR sees)
bash scripts/ci/check-mutation-tier2.sh

# Dry-run only (no actual mutation)
./vendor/bin/infection --configuration=infection-extended.json5 --dry-run

# Generate coverage for mutation to use
XDEBUG_MODE=coverage php -d memory_limit=3G ./vendor/bin/pest \
    --coverage-xml=storage/infection/coverage

# View mutation log
tail -n 200 reports/infection-tier2.log
```

