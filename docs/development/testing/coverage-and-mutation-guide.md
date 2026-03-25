# Coverage & Mutation Testing Guide

**Updated:** Phase 5 Implementation (March 2026)
**Purpose:** Explain the parallel/sequential coverage trade-offs and optimal workflows for local dev and CI/CD.

---

## Quick Reference: Coverage Collection Patterns

### ⚡ Local Development: Fast Path (Parallel Only, No Coverage)

**When:** You want quick feedback on test passing/failing.

```bash
# Run tests in parallel, no coverage overhead (2 min for 6K tests)
./vendor/bin/pest --parallel --processes=10
```

**Output:**
```
   6253 passed
   13799 assertions
   Duration: 2m 5s
```

**Pros:** Ultra-fast feedback loop.
**Cons:** No coverage metrics until separately collected.

---

### 🎯 Local Development: Full Coverage (Sequential Only)

**When:** You need to see which lines your tests cover (before mutation testing).

```bash
# Single-process coverage collection (8-10 min)
XDEBUG_MODE=coverage php -d memory_limit=3G ./vendor/bin/pest
```

**Output:**
```
   Code Coverage:
   Lines: 28.33% (47,347 / 167,118)
   Functions: 65.12%
   Classes: 72.45%
```

**Pros:** Accurate, memory-safe.
**Cons:** Slower (10x slower than parallel).

---

### 🚀 CI/CD Pipeline: Coverage + Mutation (Recommended)

**When:** Running in GitHub Actions / GitLab CI / Jenkins (auto-gated).

**Phase 1: Fast Test Execution (Parallel, No Coverage)**
```bash
# Run all 6K tests in parallel to catch failures first
./vendor/bin/pest --parallel --processes=10
```
⏱️ Duration: ~2 min

**Phase 2: Coverage Collection (Sequential)**
```bash
# Collect coverage on single process (safe from OOM)
XDEBUG_MODE=coverage php -d memory_limit=3G ./vendor/bin/pest \
    --coverage-xml=storage/infection/coverage
```
⏱️ Duration: ~8 min

**Phase 3: Mutation Testing (If passing)**
```bash
# Tier 2 mutation gate (requires coverage XML from Phase 2)
bash scripts/ci/check-mutation-tier2.sh
```
⏱️ Duration: ~30–45 min

**Total CI time:** ~45–55 min per PR (for full pipeline).

---

## Why This Design?

### The Parallel + Coverage Problem

**Historical Issue (Phase 4 Audit):**
```bash
# This command FAILS with OOM at 2GB:
XDEBUG_MODE=coverage ./vendor/bin/pest --parallel --processes=10 --coverage-xml=...

Error: Allowed memory size of 2147483648 bytes exhausted
  (tried to allocate 249982360 bytes during merge)
```

**Root Cause:**
- 10 parallel workers each generate ~600KB–1MB coverage XML.
- ParaTest's `CoverageMerger` reads **all per-worker XML into memory simultaneously**.
- Merge requires: (10 workers × ~1MB) + overhead > 2GB ceiling.
- OOM occurs during XML aggregation, **after tests complete successfully**.

### Solution: Separate Concerns

| Concern | Tool | Mode | Duration |
| :--- | :--- | :--- | ---: |
| **Test Execution** | Pest | Parallel (10 workers) | 2 min |
| **Coverage Report** | Pest | Sequential (1 worker) | 8 min |
| **Mutation Testing** | Infection | Parallel-friendly (6 threads) | 30–45 min |

**Benefit:** Each tool operates in its optimal mode without conflicts.

---

## Environment Variables Reference

### XDEBUG_MODE

Controls which Xdebug features are enabled:

```bash
# Coverage mode (for coverage collection)
XDEBUG_MODE=coverage

# Debug mode (for IDE breakpoints)
XDEBUG_MODE=debug

# Profile mode (for performance analysis)
XDEBUG_MODE=profile

# Disable Xdebug entirely
XDEBUG_MODE=off
```

### Memory Limits

**For sequential coverage:**
```bash
# Default (2GB) — adequate for sequential
-d memory_limit=3G
```

**For mutation testing (large coverage XML):**
```bash
# May need more memory during mutation
-d memory_limit=4G
```

---

## Common Workflows

### Workflow 1: TDD Rapid Loop (Dev)

```bash
# 1. Run tests in parallel (2 min)
./vendor/bin/pest --parallel

# 2. If failing, fix code and re-run (step 1)

# 3. Once tests pass, check coverage on specific test file
XDEBUG_MODE=coverage php ./vendor/bin/pest tests/Unit/MyServiceTest.php

# 4. Review coverage report
cat reports/coverage-final.txt
```

---

### Workflow 2: Pre-PR Checklist (Dev)

```bash
# 1. Run full test suite (parallel, 2 min)
./vendor/bin/pest --parallel --processes=10

# 2. Collect coverage (sequential, 8 min)
XDEBUG_MODE=coverage php -d memory_limit=3G ./vendor/bin/pest \
    --coverage-xml=storage/infection/coverage

# 3. Run Tier 2 mutation check (parallel, 30-45 min)
bash scripts/ci/check-mutation-tier2.sh

# 4. If mutation test passes, push to origin
git push origin feature-branch
```

---

### Workflow 3: CI/CD Gate (Automated)

```bash
#!/bin/bash
# Typical GitHub Actions / GitLab CI script

set -e

# Phase 1: Test execution (parallel)
echo "Testing..."
./vendor/bin/pest --parallel --processes=10

# Phase 2: Coverage (sequential)
echo "Collecting coverage..."
XDEBUG_MODE=coverage php -d memory_limit=3G ./vendor/bin/pest \
    --coverage-xml=storage/infection/coverage

# Phase 3: Mutation (if coverage passes)
echo "Running mutation testing..."
bash scripts/ci/check-mutation-tier2.sh

echo "✅ All gates passed"
```

---

## Troubleshooting

### Problem: "Allowed memory size exhausted" During Coverage

**Symptom:**
```
Error: Allowed memory size of 2147483648 bytes exhausted
Memory exhaustion during CoverageMerger merge
```

**Solution:**
1. **Verify you're NOT running parallel + coverage together:**
   ```bash
   # ❌ WRONG (causes OOM)
   ./vendor/bin/pest --parallel --coverage-xml=...

   # ✅ RIGHT (sequential, safe)
   XDEBUG_MODE=coverage php ./vendor/bin/pest --coverage-xml=...
   ```

2. **If still failing, increase memory:**
   ```bash
   # Try 4GB for sequential coverage
   php -d memory_limit=4G ./vendor/bin/pest --coverage-xml=...
   ```

3. **Check coverage XML size:**
   ```bash
   du -sh storage/infection/coverage/
   ```
   (Should be < 500MB; if > 1GB, you may have misconfigured coverage sources.)

---

### Problem: Mutation Test Timeout (> 45 min)

**Symptom:**
```
Mutation testing timed out (pid killed after 45 min)
```

**Solution:**
1. **Check if coverage XML is present:**
   ```bash
   ls -lh storage/infection/coverage/
   ```

2. **Reduce mutation threads (default 6):**
   ```bash
   # In scripts/ci/check-mutation-tier2.sh, change:
   THREADS=6  # ← Change to 4
   ```

3. **Check if your codebase has grown (more files to mutate):**
   ```bash
   # See how many files are in scope
   find app/Services app/Actions Modules/PIB/Services \
     Modules/ContractManager/Services Modules/Payment/Services \
     -name '*.php' | wc -l
   ```

---

### Problem: Coverage Report Shows 0%

**Symptom:**
```
Code Coverage: 0% (0 / 167,118 lines)
```

**Solution:**
1. **Ensure XDEBUG_MODE is set:**
   ```bash
   # Check environment
   php -i | grep XDEBUG_MODE

   # Must output: XDEBUG_MODE => coverage
   ```

2. **Ensure Xdebug is installed:**
   ```bash
   php -m | grep Xdebug
   ```

3. **Try explicit Xdebug mode:**
   ```bash
   XDEBUG_MODE=coverage php ./vendor/bin/pest --coverage-xml=storage/infection/coverage
   ```

---

## Best Practices

### 1. **Always Run Parallel First**
   - In CI, run `./vendor/bin/pest --parallel` **before** coverage collection.
   - If tests fail, fail fast (1–2 min) without wasting time on coverage.
   - Only collect coverage if tests pass.

### 2. **Coverage Is Expensive; Use Selectively**
   - Locally: Collect coverage **after** fixing failing tests (not every run).
   - CI: Collect coverage **once per PR** (not per commit).
   - Use `--filter` to collect coverage for specific test files only:
     ```bash
     XDEBUG_MODE=coverage php ./vendor/bin/pest \
       --filter=ServiceTest --coverage-xml=...
     ```

### 3. **Mutation Testing Requires Coverage**
   - Always run coverage collection before mutation testing.
   - Infection uses coverage XML to skip non-covered code (optimization).
   - Stale coverage XML (> 1 hour old) should be regenerated.

### 4. **Monitor CI Time**
   - Target: < 60 min total per PR (tests + coverage + mutation).
   - Breakdown:
     - Tests: 2 min (non-negotiable speed)
     - Coverage: 8 min (can be optimized with caching)
     - Mutation: 30–45 min (currently expensive; can be improved in Phase 6 with selective mutation).

---

## Advanced: Custom Coverage Filtering

### Collect Coverage for Specific Directory Only

```bash
# Coverage for app/Services only (faster)
XDEBUG_MODE=coverage php ./vendor/bin/pest \
    --coverage-directory=app/Services \
    --coverage-xml=storage/infection/coverage
```

### Exclude Large Generated Files

In `phpunit.xml`, modify the `<source>` section:

```xml
<source>
    <include>
        <directory>app</directory>
        <directory>Modules</directory>
    </include>
    <exclude>
        <directory>app/Http/Controllers/Generated</directory>
        <directory>Modules/*/Generated</directory>
    </exclude>
</source>
```

---

## Summary

| Scenario | Command | Duration | Mode |
| :--- | :--- | ---: | :--- |
| Dev: Quick test | `./vendor/bin/pest --parallel` | 2 min | Parallel |
| Dev: Test + Coverage | `XDEBUG_MODE=coverage php ./vendor/bin/pest` | 10 min | Sequential |
| Dev: Pre-push full check | `check-mutation-tier2.sh` | 45 min | Parallel (6 threads) |
| CI: PR gate | Phase 1 + Phase 2 + Phase 3 (above) | 45 min | Mixed |
| CI: Nightly (Tier 1) | `./vendor/bin/infection` | 2–3 hrs | Parallel (6 threads) |

