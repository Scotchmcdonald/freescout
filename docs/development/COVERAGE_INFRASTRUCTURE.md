# Coverage Infrastructure Guide

**Last Updated:** March 2026
**Status:** ✅ Operational (Post-remediation Phase 1)

## Overview

This document describes the test coverage infrastructure setup, memory configuration, and how to collect coverage reports reproducibly.

## Key Changes (March 2026)

- **phpunit.xml memory limit:** Raised from `512M` to `2048M` per parallel worker
  - Root cause of prior OOM in `ParaTest::CoverageMerger`: authoritative fork-time ceiling was too low
  - New limit accommodates all 10 parallel workers merging coverage simultaneously
- **XDEBUG_MODE env var:** Now set in `.env.testing` to `coverage`
- **Wrapper script:** New `scripts/test-coverage.sh` handles orchestrator-level Xdebug setup

## Running Coverage Reports

### Option 1: Using the wrapper script (recommended)

```bash
# Full suite with coverage (HTML + Clover XML)
./scripts/test-coverage.sh

# With minimum coverage threshold
./scripts/test-coverage.sh --min=75

# Specific suite only
./scripts/test-coverage.sh --processes=4 tests/Unit

# All options
./scripts/test-coverage.sh --processes=8 --min=80 tests/Feature tests/Integration
```

Output files:
- `reports/coverage.xml` — Clover XML (for CI/CD integration)
- `reports/coverage-html/` — HTML dashboard (open `reports/coverage-html/index.html`)

### Option 2: Direct invocation (if wrapper not available)

```bash
export XDEBUG_MODE=coverage
php artisan test \
  --coverage \
  --coverage-html=reports/coverage-html \
  --coverage-clover=reports/coverage.xml \
  --parallel --processes=10
```

### Option 3: Memory-constrained environments

If your runner has <2GB available, use sequential or chunked coverage:

```bash
# Sequential (safest, slowest)
XDEBUG_MODE=coverage php artisan test --coverage --no-parallel

# Chunked by suite
for suite in Unit Feature Integration; do
  XDEBUG_MODE=coverage php php artisan test \
    --testsuite=$suite \
    --coverage-php reports/coverage-${suite}.cov
done

# Merge coverage files
./vendor/bin/phpcov merge --clover reports/coverage.xml reports/
```

## Configuration Details

### phpunit.xml

```xml
<php>
    <env name="XDEBUG_MODE" value="coverage"/>
    <ini name="memory_limit" value="2048M"/>
    <ini name="zend.enable_gc" value="1"/>
</php>
```

**Key points:**
- `XDEBUG_MODE=coverage` in phpunit.xml applies to **worker subprocesses only**
- The orchestrator (ParaTest / Pest CLI) needs `XDEBUG_MODE=coverage` in the parent shell or `.env.testing`
- `memory_limit=2048M` is the fork-time ceiling for each parallel worker

### .env.testing

```ini
XDEBUG_MODE=coverage
```

Ensures Xdebug coverage mode is active when tests are invoked. Also set automatically by `scripts/test-coverage.sh`.

### tests/Pest.php

Memory escalation logic (lines 36–43):

```php
$configuredMemoryLimit = env('TEST_MEMORY_LIMIT');
$minimumMemoryLimit = env('TEST_MIN_MEMORY_LIMIT', '1536M');

if (is_string($configuredMemoryLimit) && $configuredMemoryLimit !== '') {
    ini_set('memory_limit', $configuredMemoryLimit);
} elseif ($memoryToBytes((string) ini_get('memory_limit')) < $memoryToBytes((string) $minimumMemoryLimit)) {
    ini_set('memory_limit', $minimumMemoryLimit);
}
```

**Note:** This escalation logic runs *inside* the already-forked worker and does not affect the fork-time ceiling set by `phpunit.xml`. Use the phpunit.xml setting for the authoritative memory limit.

## Troubleshooting

### "Unable to get coverage using Xdebug: ... coverage mode is not enabled"

**Cause:** `XDEBUG_MODE` not set in the parent shell when invoking pest/php.

**Fix:**
```bash
# Option A: Export before running
export XDEBUG_MODE=coverage
./scripts/test-coverage.sh

# Option B: Inline
XDEBUG_MODE=coverage ./scripts/test-coverage.sh

# Option C: Verify it's in .env.testing
grep XDEBUG_MODE .env.testing
```

### OOM during coverage merge

**Symptoms:** `Killed` or `Fatal error: Out of memory` in `vendor/brianium/paratest/src/Coverage/CoverageMerger.php:27` during parallel runs.

**Fix:**
1. Confirm `phpunit.xml` has `<ini name="memory_limit" value="2048M"/>`
2. If running in a memory-constrained CI environment, use sequential or chunked approach (Option 3 above)
3. As a last resort, reduce parallel worker count: `--processes=5` instead of 10

### Coverage reports not generated

**Cause:** Test suite exited before coverage serialization (crash, timeout, or OOM).

**Check:**
```bash
# Verify the run completed
tail -n 100 reports/test-results-latest.log | grep -i "error\|failed\|memory"

# Check for partial coverage files
ls -lh reports/coverage*.cov reports/coverage*.xml 2>&1
```

## Performance Notes

- **Coverage overhead:** ~50% slower than non-coverage runs (Xdebug introspection cost)
- **Parallel worker memory:** Each of 10 workers needs ~200MB working set + coverage serialization (~500MB)
- **Total time (full suite):** ~3–4 minutes with coverage, ~1.5 minutes without

## CI/CD Integration

### GitHub Actions Example

```yaml
- name: Run tests with coverage
  run: ./scripts/test-coverage.sh --min=75
  env:
    XDEBUG_MODE: coverage
```

### GitLab CI Example

```yaml
test_cover:
  script:
    - ./scripts/test-coverage.sh --min=75
  artifacts:
    reports:
      coverage_report:
        coverage_format: cobertura
        path: reports/coverage.xml
```

## See Also

- [Testing Architecture Guide](./WIP/testing-audit-remediation/README.md) — Full test infrastructure audit
- [Pest Documentation](https://pestphp.com) — Official Pest documentation
- [ParaTest Parallel Testing](https://github.com/paratestphp/paratest) — Parallel test runner
