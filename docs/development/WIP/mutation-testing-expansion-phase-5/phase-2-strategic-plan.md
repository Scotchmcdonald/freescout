# Phase 5: Mutation Testing Expansion — Strategic Plan

**Owners:** QA / Platform Engineering  
**Timeline:** 4 weeks (2 weeks infrastructure, 2 weeks coverage uplift)  
**Success Metric:** Mutation MSI ≥ 75 across `app/Services` + 3 module services.

---

## Objectives

1. **Expand mutation scope** from 3 modules to include critical app services.
2. **Gate mutation in CI/CD** with a dedicated infection runner script and minimum thresholds.
3. **Fix parallel coverage merge** by sequencing or streaming XML aggregation.
4. **Document infection best practices** for local dev and CI workflows.
5. **Establish coverage baselines** for each scope tier to prevent regressions.

---

## Proposed Scope Expansion

### Tier 1: Currently Tested (3 service directories) — Keep as-is
- `Modules/PIB/Services`
- `Modules/ContractManager/Services`
- `Modules/Payment/Services`
- **MSI Threshold:** ≥ 80 (existing)
- **Run Frequency:** Weekly (full mutation suite, ~2–3 hours)

### Tier 2: Critical App Layer (Expand in Phase 5)
- `app/Services` (business logic hub)
- `app/Actions` (domain operations)
- **Coverage Prerequisite:** ≥ 60% line coverage in scope (current: ~35% in app/Services).
- **MSI Threshold:** ≥ 70 (starting point)
- **Run Frequency:** Weekly or post-PR (fast subset, ~30–45 min).
- **Rationale:** Services/Actions are core to reliability; worth mutation investment.

### Tier 3: Deferred (Post-Phase-5)
- `app/Http/Requests` (validation rules)
- `app/Models` (entity logic)
- `app/Policies` (authorization)
- **Reason:** Requires broader test suite first; revisit when coverage > 50%.

---

## Implementation Plan

### Step 1: Refactor infection.json5 into Multi-Tier Config (Week 1)

Create two infection configs:

**`infection.json5`** (existing, unchanged)
- Scope: 3 module services
- Runs: Manual or weekly scheduled.

**`infection-extended.json5`** (new)
- Scope: 3 modules + app/Services + app/Actions
- Runs: Post-PR (gated in CI).
- `minMsi`: 70 (lower than Tier 1 to allow coverage growth).
- `minCoveredMsi`: 75.

**File Structure:**
```
project-root/
├── infection.json5              # Tier 1 (3 modules)
├── infection-extended.json5     # Tier 1 + 2 (CI gate)
├── scripts/ci/
│   ├── check-mutation-tier1.sh  # Weekly full suite
│   └── check-mutation-tier2.sh  # Post-PR extended scope
└── docs/development/WIP/mutation-testing-expansion-phase-5/
    ├── phase-1-diagnosis.md
    ├── phase-2-strategic-plan.md (this file)
    ├── phase-3-implementation.md
    └── phase-4-ci-integration.md
```

### Step 2: Implement Fast CI Gate (Week 1–2)

**Goal:** Mutation testing in CI without blocking for hours.

**Approach:**
- Post-PR: Run `check-mutation-tier2.sh` (30–45 min for app/Services + app/Actions).
- Weekly: Run full suite `check-mutation-tier1.sh` (2–3 hours offline).
- Baseline: Store MSI per commit; alert if Tier 2 drops > 5 points.

**Implementation in `scripts/ci/check-mutation-tier2.sh`:**
```bash
#!/usr/bin/env bash
set -e

echo "🧬 Running Mutation Testing (Tier 2: App Services + Actions)..."

INFECTION_CONFIG="infection-extended.json5"
MIN_MSI=70

# Run infection with extended scope
php vendor/bin/infection \
    --configuration="$INFECTION_CONFIG" \
    --threads=6 \
    --min-msi="$MIN_MSI" \
    --log-junit=reports/infection-tier2-junit.xml \
    --logger-text=reports/infection-tier2.log

if [ $? -ne 0 ]; then
    echo "❌ Mutation score below threshold ($MIN_MSI)."
    exit 1
fi
echo "✅ Mutation Tier 2 passed."
```

### Step 3: Fix Parallel Coverage Merge (Week 2)

**Problem:** `XDEBUG_MODE=coverage ./vendor/bin/pest --parallel` exhausts memory.

**Solution A (Recommended for CI):** Use **sequential coverage collection**.
```bash
# For CI reporting, use single process (slower but reliable)
XDEBUG_MODE=coverage php -d memory_limit=3G \
    ./vendor/bin/pest \
    --coverage-text=reports/coverage-summary.txt \
    --coverage-xml=storage/infection/coverage
```

**Solution B (For Local Dev):** Stream parallel results.
```bash
# Parallel testing without coverage merge
./vendor/bin/pest --parallel --processes=10

# Then run coverage separately on single process
XDEBUG_MODE=coverage php -d memory_limit=3G \
    ./vendor/bin/pest \
    --coverage-text=reports/coverage-final.txt \
    --coverage-xml=storage/infection/coverage
```

**Update `phpunit.xml` to document this trade-off:**
```xml
<!-- 
  IMPORTANT: Parallel + Coverage merge together exhaust memory at >30K tests.
  For CI: Run parallel without coverage; then sequential coverage pass.
  For Local: Run parallel OR coverage separately (not both).
  Memory needed: 3GB for sequential coverage merge.
-->
<ini name="memory_limit" value="2048M"/>
```

### Step 4: Uplift Coverage in app/Services (Weeks 2–3)

**Current:** ~35% line coverage in app/Services.  
**Target:** ≥ 60% to support Tier 2 mutation testing.

**Action Items:**
- Run coverage report for app/Services only.
- Identify 5–10 high-value services missing tests.
- Add unit/integration tests (focus on critical decision paths).
- Use `tests/Integration/Services/` for stateful tests.
- Use `tests/Unit/Services/` for pure logic (with mocks).

**Example:** If `app/Services/EntitlementEngine` is 20% covered:
```php
// tests/Integration/Services/EntitlementEngineTest.php
test('entitlement engine accrues usage correctly', function () {
    $engine = new EntitlementEngine($repository);
    $result = $engine->accrueUsage($contract, $lineItem);
    
    expect($result->isValid())->toBeTrue();
    expect($repository->saved)->toBe(true);
});
```

### Step 5: Document Infection Best Practices (Week 3–4)

Create `docs/development/mutation-testing-guide.md` covering:

1. **Running Locally**
   ```bash
   # Tier 1 (full, ~2hrs)
   ./vendor/bin/infection

   # Tier 2 only (fast, ~30min)
   ./vendor/bin/infection --configuration=infection-extended.json5
   ```

2. **Interpreting Results**
   - MSI ≥ 80: Excellent (rare for new code).
   - MSI 70–79: Good; consider why mutants escaped.
   - MSI < 70: Weak test suite; investigate escaped mutants.

3. **Escaping Mutants**
   - `@infection:ignore-all` PHPDoc for generated/trivial code.
   - Review escape paths; often indicate gaps in test assertions.

4. **CI Integration**
   - Tier 2 runs post-PR (cost: 30–45 min extra CI time).
   - Tier 1 runs nightly/weekly offline.
   - Baseline reported in commit status.

---

## Expected Outcomes

| Metric | Before | After | Status |
| :--- | :--- | :--- | :--- |
| Mutation scope files | 3 dirs (~50 files) | 3 dirs + 20 app services | Expanded |
| Total mutants tracked | 1,378 | ~3,500–4,000 | Estimate |
| MSI coverage (limited) | 100% (3 dirs) | 100% (3) + 75% (app) | More realistic |
| CI mutation gate | None | Tier 2 in PR | Risk mitigated |
| Coverage merge OOM | Frequent | Eliminated | Fixed |
| Local dev feedback | Manual Infection | Documented workflow | Enabled |

---

## Success Criteria

- ✅ Mutation config split into 2 tiers (by Week 1).
- ✅ `check-mutation-tier2.sh` integrated in CI (by Week 2).
- ✅ Coverage sequential mode documented & CI uses it (by Week 2).
- ✅ app/Services coverage ≥ 60% (by Week 3).
- ✅ Mutation guide published (by Week 4).
- ✅ Tier 2 MSI ≥ 70 sustained (by Week 4).

---

## Risk Mitigation

| Risk | Impact | Mitigation |
| :--- | :--- | :--- |
| Extended config breaks local workflow | High | Test locally before CI merge; document switch. |
| Tier 2 runs take > 45 min per PR | Medium | Start with 6 threads; tune if slow. |
| Coverage uplift stalls (tests hard to write) | High | Use integration tests; accept slower timeline. |
| Mutation MSI doesn't improve | Medium | Review escaped mutants; iterative test refinement. |

---

## Deferred Decisions

- **Nightly Tier 1 Automation:** Can be scheduled after Phase 5 success.
- **Tier 3 Expansion:** Post Phase 5 (depends on Models/Policies test suite maturity).
- **Live Dashboard:** Can add mutation trend reporting in Phase 6.

