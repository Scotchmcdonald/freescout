# Phase 1: Coverage Infrastructure — Fix OOM and Make Coverage Reproducible

**KPI affected:** Test Reliability (current 45/100 → target 70/100)
**Effort:** ~2 hours
**Risk:** Low — changes are isolated to `phpunit.xml` and a dedicated coverage script

---

## Problem Statement

Running `XDEBUG_MODE=coverage php artisan test --coverage --parallel --processes=10` produces an OOM crash in `vendor/brianium/paratest/src/Coverage/CoverageMerger.php:27` before any coverage report is emitted.

**Root cause:** `phpunit.xml` line 47:
```xml
<ini name="memory_limit" value="512M"/>
```
This is the authoritative ceiling set at the point the parallel worker process is **forked**. The `ini_set()` calls in `tests/Pest.php` and `tests/TestCase.php` run *inside* the already-allocated worker process —  they cannot raise a limit that was sealed at fork time by phpunit. The `TEST_MEMORY_LIMIT` env override is also too late for the same reason.

When 10 workers each serialize a full-application `CodeCoverage` object and the merger deserializes all 10 into the same process, the merger process exceeds 512 MB and is killed by PHP.

**Secondary issue:** `XDEBUG_MODE` is not set in the local shell by default, causing the first coverage run attempt to exit immediately with:
```
Unable to get coverage using Xdebug: The Xdebug extension is loaded, but the
'coverage' mode is not enabled.
```
The `phpunit.xml` already has `<env name="XDEBUG_MODE" value="coverage"/>` at line 43, but this only takes effect for PHPUnit's own subprocess, not for the parent Pest/ParaTest orchestrator.

---

## Fix Steps

### Step 1: Raise the per-worker memory limit in `phpunit.xml`

**File:** `phpunit.xml` line 47

Change:
```xml
<!-- Memory limit: 512M per parallel worker is sufficient; sequential run needs more -->
<ini name="memory_limit" value="512M"/>
```
To:
```xml
<!-- Memory limit: raised to 2048M so ParaTest coverage merger can aggregate all workers.
     512M was the authoritative fork-time ceiling and caused OOM in CoverageMerger.php:27  -->
<ini name="memory_limit" value="2048M"/>
```

> **Note:** 2048M is the minimum viable ceiling. If the suite grows significantly (>8,000 tests), monitor merger peak usage and raise to 4096M.

### Step 2: Ensure `XDEBUG_MODE` is exported before any coverage run

The `phpunit.xml` `<env>` tag only covers worker subprocesses. The orchestrator (ParaTest / Pest CLI) must also have it. Add a check to the project's `.env.testing` or the invocation:

**Option A — `.env.testing` (recommended for CI):**
```ini
XDEBUG_MODE=coverage
```

**Option B — makefile / CI script wrapper (recommended for local dev):**

Create `scripts/test-coverage.sh`:
```bash
#!/usr/bin/env bash
set -euo pipefail

export XDEBUG_MODE=coverage

php artisan test \
  --coverage \
  --coverage-html reports/coverage-html \
  --coverage-clover reports/coverage.xml \
  --parallel \
  --processes=10 \
  "$@"
```
```bash
chmod +x scripts/test-coverage.sh
```

Then update docs and CI to use `./scripts/test-coverage.sh` instead of bare `php artisan test --coverage`.

### Step 3: Add a coverage smoke-test to CI

The current CI pipeline has no step that asserts a minimum coverage threshold. This means OOM failures are silent — the pipeline just skips coverage without failing. Add a required CI step:

```yaml
# In your CI workflow (e.g. .github/workflows/ci.yml)
- name: Run tests with coverage
  run: ./scripts/test-coverage.sh --min=75
  env:
    XDEBUG_MODE: coverage
```

### Step 4: Fallback — chunk coverage by suite if OOM persists

If 2048M is insufficient in CI (limited runner memory), generate coverage per-suite and merge with `phpcov`:

```bash
# Coverage by suite only — lower peak memory per pass
XDEBUG_MODE=coverage php artisan test --testsuite=Unit --coverage-php reports/coverage-unit.cov
XDEBUG_MODE=coverage php artisan test --testsuite=Feature --coverage-php reports/coverage-feature.cov
XDEBUG_MODE=coverage php artisan test --testsuite=Integration --coverage-php reports/coverage-integration.cov

# Merge
./vendor/bin/phpcov merge --clover reports/coverage.xml reports/
```

---

## Verification

```bash
# Verify the memory change took effect in a worker
php artisan test tests/Unit/PureUnitTestCase.php --coverage 2>&1 | grep -i "memory\|error\|ok"

# Run full suite coverage and confirm report is generated
XDEBUG_MODE=coverage ./scripts/test-coverage.sh
ls -lh reports/coverage.xml  # should exist and be non-empty
```

**Success criterion:** `reports/coverage.xml` is generated without OOM or Xdebug mode errors. The aggregate line-coverage percentage appears in the test runner output.

---

## Files Changed

| File | Change |
|------|--------|
| `phpunit.xml` | Line 47: `512M` → `2048M` |
| `scripts/test-coverage.sh` | New file — wrapper that exports `XDEBUG_MODE=coverage` and runs with `--coverage` |
| `.env.testing` *(optional)* | Add `XDEBUG_MODE=coverage` |

---

## Done When

- [ ] `XDEBUG_MODE=coverage php artisan test --coverage --parallel --processes=10` completes without OOM
- [ ] `reports/coverage.xml` is generated
- [ ] CI step enforces `--min=75` threshold and fails build if coverage drops below it
