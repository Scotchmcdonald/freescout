# Phase 5: Mutation Testing Expansion — Diagnosis & Root Cause Analysis

**Completed:** March 25, 2026  
**Executive Issue:** Mutation MSI artificially inflated (100%) due to scope limitation (3 services only) while global line coverage is 28.33%.

---

## Problem Statement

The current mutation testing infrastructure has several critical gaps:

1. **Narrowly Scoped Mutation Testing**
   - Infection is configured to mutate only:
     - `Modules/PIB/Services`
     - `Modules/ContractManager/Services`
     - `Modules/Payment/Services`
   - MSI = 100 for these 3 directories ≠ system-wide mutation kill ratio.
   - **Impact:** False confidence in reliability; 97% of codebase is unmutated and untested.

2. **Global Line Coverage Mismatch**
   - Aggregate line coverage: 28.33% (47,347 / 167,118 lines executed).
   - Coverage XML shows many critical files with **0% coverage**:
     - `Modules/EmailMigration/Services/LabManager.php`: 1228 lines, 0 executed.
     - `app/Http/Controllers/Auth/SocialAuthController.php`: 102 lines, 0 executed.
     - `app/Console/Commands/PruneDemoAccounts.php`: 103 lines, 0 executed.
   - Blade views, migrations, seeders: 0% coverage by design (not executable PHP).
   - **Impact:** Broad behavior regression risk undetected.

3. **No `--mutation` Flag in Pest/PHPUnit**
   - Pest v2 does not expose a `--mutation` flag.
   - Mutation is run as a **separate orchestrated step** via Infection CLI.
   - Current CI does not invoke Infection at all (not in scripts/ci/).
   - **Impact:** Mutation testing is not gated in CI/CD; only developers running locally see results.

4. **Parallel Coverage Merge Crashes**
   - `XDEBUG_MODE=coverage ./vendor/bin/pest --parallel --processes=10` with full app scope hits **OOM at 2GB**.
   - Coverage merger tries to aggregate 10 workers' worth of per-worker XML in memory.
   - **Impact:** Parallel execution blocks reporting and metric collection.

5. **No Dedicated Mutation CI Step**
   - `scripts/ci/` contains architecture, style, type checks but **no mutation runner**.
   - Mutation is entirely opt-in (manual `./vendor/bin/infection` invocation).
   - **Impact:** CI cannot block merges on mutation score degradation.

---

## Root Cause Analysis

### Why Mutation Scope Is So Narrow

**File:** `infection.json5` (lines 4–8)

```json5
"source": {
    "directories": [
        "Modules/PIB/Services",
        "Modules/ContractManager/Services",
        "Modules/Payment/Services"
    ]
}
```

**Likely Reason:**
- These modules are the **billing/financial core** and have explicit type-safety guards.
- Wider scope would require broader test coverage (currently only 28.33% line coverage).
- Mutation testing is **expensive**:
  - Each mutant requires a full test run.
  - 1,378 mutants × ~6 minutes per run = hours of compute.
- **Decision Trade-off:** Narrow scope = feasible local dev loop; wide scope = hourly CI times.

### Why Coverage Merge Crashes

**Issue:** Memory exhaustion in parallel worker coverage aggregation.

- 10 parallel workers each generate ~600KB–1MB coverage XML.
- PHPUnit's CoverageMerger reads all per-worker XML into memory simultaneously.
- At 2GB heap, aggregator cannot allocate another 250MB chunk.

**Root Cause:** Coverage reporting is **not optimized for parallel execution**.

### Why Mutation Is Disconnected from PHPUnit Flags

**Design Choice:** Infection is a **wrapper, not a plugin**.

- Infection CLI wraps PHPUnit/Pest execution.
- Infection parses coverage XML and generates mutants.
- Infection re-runs each mutant against the test suite.
- PHPUnit/Pest itself never sees `--mutation` because:
  - It's Infection's responsibility to control which mutants run.
  - PHPUnit just runs tests; Infection decides outcome.

---

## Current Mutation Metrics Summary

| Metric | Value | Scope |
| :--- | ---: | :--- |
| Total Mutants | 1378 | 3 service dirs only |
| Killed | 1143 | 83% kill rate |
| Skipped | 235 | 17% (non-critical lines) |
| Escaped | 0 | (None alive) |
| MSI | 100 | **NOT system-wide** |
| Covered MSI | 100 | Covered code only |

**Interpretation:** Within the 3 scoped directories, mutation testing is excellent. Outside of them, reality is unknown.

---

## Next Steps

This diagnosis document serves as basis for the **Phase 2: Strategic Expansion** plan (separate).

**Key Decisions Deferred:**
1. Should we expand mutation scope to `app/Services` (most critical)?
2. Should we create a separate **fast mutation check** for CI (subset of codebase)?
3. Should we split coverage reporting into sequential-only mode for CI?
