# Testing Quick Start for Developers

**TL;DR before pushing:**
```bash
# 1-minute check
bash scripts/ci/check-architecture-compliance.sh
php artisan test tests/Unit/ModuleUnitIsolationGuardTest.php -q

# Full suite (takes ~2 minutes)
php artisan test --parallel --processes=10
```

---

## The Five Test Lanes

CI runs these **in parallel** to give you fast feedback on different aspects of code quality:

| Lane | Purpose | Your Command | When to Run | Time |
|---|---|---|---|---|
| **🛡️ Guards** | Block obvious violations (core blindness, cross-module coupling, atomic counters) | `bash scripts/ci/check-architecture-compliance.sh` | Before feature code | 30-60s |
| **✅ Unit** | Pure logic, helpers, DTOs (no database) | `php artisan test tests/Unit --parallel --processes=10` | Always | 15-30s |
| **🧪 Feature** | Controllers, forms, requests, authorization | `php artisan test tests/Feature --parallel --processes=10` | For routes/requests | 30-60s |
| **🏗️ Architecture** | Module boundaries, contracts, service providers | `php artisan test tests/Architecture --parallel --processes=10` | After config changes | 5-10s |
| **🔗 Integration** | APIs, external services, module integration | `php artisan test tests/Integration --parallel --processes=10` | For external APIs | 30-60s |

Phase 5 guardrails (CI-aligned) after a lane run:

```bash
# Skip governance baseline + metadata policy
php scripts/ci/check-skip-governance.php

# Quarantine registry governance + expiry policy
php scripts/ci/check-quarantine-registry.php

# Runtime budget check example (replace lane and duration)
php scripts/ci/check-test-lane-runtime-budgets.php --lane=unit --duration=28

# Flake trend snapshot from recent logs
php scripts/ci/generate-flake-report.php --lane=local --output=reports/flake-report-local-latest.md
```

---

## Before You Push (2-3 Minutes)

Run this sequence:

```bash
# 1. Architecture guards (fails fast if you broke core isolation)
bash scripts/ci/check-architecture-compliance.sh
if [ $? -ne 0 ]; then
  echo "❌ Fix architecture violations before pushing"
  exit 1
fi

# 2. Test isolation guards (fails if you used RefreshDatabase in unit tests)
php artisan test tests/Unit/ModuleUnitIsolationGuardTest.php \
                 tests/Unit/RefreshDatabaseUsageGuardTest.php -q
if [ $? -ne 0 ]; then
  echo "❌ Fix isolation violations before pushing"
  exit 1
fi

# 3. Updated tests
php artisan test path/to/YourNewTest.php

# 4. Relevant lanes
# If you modified a service: run unit tests
php artisan test tests/Unit --parallel --processes=10

# If you modified a route/controller: run feature tests
php artisan test tests/Feature --parallel --processes=10

echo "✅ Ready to push!"
```

---

## Where to Find Reports & Logs

| Type | Location | View With |
|---|---|---|
| **Latest CI run** | `reports/test-results-latest.log` | `tail -f reports/test-results-latest.log` |
| **Master compliance** | `reports/ci_master.log` | `cat reports/ci_master.log` |
| **Test failures** | `reports/test-results-latest.log` | `grep -A 5 "FAILED" reports/test-results-latest.log` |
| **Skip governance** | `reports/skip-governance-latest.md` | `cat reports/skip-governance-latest.md` |
| **Quarantine governance** | `reports/quarantine-registry-latest.md` | `cat reports/quarantine-registry-latest.md` |
| **Runtime budget report** | `reports/lane-runtime-budget-<lane>-latest.md` | `cat reports/lane-runtime-budget-unit-latest.md` |
| **Flake trend report** | `reports/flake-report-<lane>-latest.md` | `cat reports/flake-report-local-latest.md` |
| **Coverage (local)** | `reports/coverage/index.html` | Open in browser |

---

## Understanding Failures

### 🛡️ Guard Failed: "Core imports from Modules"

**Problem:** Your `app/` code imports from a feature module.

**Fix:**
```php
// ❌ WRONG in app/Services/
use Modules\PIB\Models\Invoice;

// ✅ RIGHT: Use a contract interface
use App\Contracts\BillingTemplateInterface;
// Module implements it: Modules\PIB\Services\BillingTemplate
```

### ✅ Unit Test Failed with "RefreshDatabase"

**Problem:** You used `RefreshDatabase` in a unit test.

**Fix:**
- Move test to `tests/Feature/` instead of `tests/Unit/`
- OR mock the database layer instead of using `RefreshDatabase`

**See:** [TESTING_CONTRIBUTION_GUIDE.md](TESTING_CONTRIBUTION_GUIDE.md)

### 🧪 Feature Test Timeout

**Problem:** Test is flaky or timing-dependent.

**Fix:**
- Use `Http::fake()` for external APIs (don't actually call them)
- Use queue fakes (`Queue::fake()`) instead of `sleep()`
- Don't sleep; verify eventual consistency properly

**See:** [FLAKY_TEST_TRIAGE.md](FLAKY_TEST_TRIAGE.md)

### 🏗️ Architecture Test Failed: "Service not bound"

**Problem:** A service implements a contract but isn't registered.

**Fix:**
```php
// In Modules/YourModule/Providers/ServiceProvider.php
public function register(): void
{
    $this->app->bind(
        \App\Contracts\YourContract::class,
        \Modules\YourModule\Services\YourService::class
    );
}
```

---

## Common Workflows

### Workflow: I wrote a new test

```bash
# 1. Run just your test
php artisan test --filter="TestClassName" -v

# 2. Run guards to check isolation
php artisan test tests/Unit/ModuleUnitIsolationGuardTest.php -q

# 3. Run the test again without -v to see only failures
php artisan test --filter="TestClassName"

# 4. If it passed both, push!
```

### Workflow: I modified a critical service

```bash
# 1. Run unit tests for that service
php artisan test --filter="ServiceNameTest" --parallel --processes=10

# 2. Run isolation guard
php artisan test tests/Unit/ModuleUnitIsolationGuardTest.php -q

# 3. Full unit lane (catches regressions elsewhere)
php artisan test tests/Unit --parallel --processes=10 -q

# 4. If all green, push
```

### Workflow: I modified a route or controller

```bash
# 1. Run feature tests that use that route
php artisan test --filter="RouteNameTest" --parallel --processes=10

# 2. Run full feature lane
php artisan test tests/Feature --parallel --processes=10 -q

# 3. Run guards
bash scripts/ci/check-architecture-compliance.sh

# 4. Push
```

### Workflow: I see a "flaky test" failure in CI

1. **Document it:**
   ```bash
   # Create an issue with label `flaky-test`
   # Include: test name, failure rate, error message
   ```

2. **Quarantine it (temporary):**
   ```php
   skip('Quarantined: timeout issue #123');
   test('flaky behavior', function () { ... });
   ```

3. **Investigate:**
   ```bash
   # Run it 10 times locally
   for i in {1..10}; do php artisan test --filter="TestName"; done
   ```

4. **Fix & verify:**
   - See [FLAKY_TEST_TRIAGE.md](FLAKY_TEST_TRIAGE.md) for patterns
   - Remove `skip()` once consistently passing

---

## Default Commands by Role

### I'm a Backend Developer

```bash
# Before every push
bash scripts/ci/check-architecture-compliance.sh && \
  php artisan test tests/Unit/ModuleUnitIsolationGuardTest.php -q && \
  php artisan test tests/Unit tests/Feature tests/Architecture --parallel --processes=10 -q
```

### I'm a Frontend Developer

```bash
# Before every push
php artisan test tests/Feature --parallel --processes=10 -q && \
  bash scripts/ci/check-ui-ux-standards.sh
```

### I'm Reviewing a PR

```bash
# Check what tests were added/changed
git diff --name-only origin/main -- "*Test.php"

# Run just the changed tests
php artisan test <changed-test-files>

# Run full guards
bash scripts/ci/check-architecture-compliance.sh
php artisan test tests/Unit/ModuleUnitIsolationGuardTest.php -q
```

---

## Useful Aliases (Add to ~/.bashrc or ~/.zshrc)

```bash
# Run guards (fast feedback)
alias guard='bash scripts/ci/check-architecture-compliance.sh && \
             php artisan test tests/Unit/ModuleUnitIsolationGuardTest.php -q'

# Run all unit tests with parallel
alias unit='php artisan test tests/Unit --parallel --processes=10 -q'

# Run all feature tests with parallel
alias feature='php artisan test tests/Feature --parallel --processes=10 -q'

# Run all tests
alias fulltest='php artisan test --parallel --processes=10'

# Quick pre-push validation
alias prepush='guard && unit && feature && php artisan test tests/Architecture -q'
```

Then just run:
```bash
guard        # Check guards
unit         # Run unit tests
feature      # Run feature tests
fulltest     # Run everything
prepush      # Validate before push
```

---

## Still Confused?

**Read the detailed docs in order:**

1. **First time writing a test:** [TESTING_CONTRIBUTION_GUIDE.md](TESTING_CONTRIBUTION_GUIDE.md)
2. **Debugging a failure:** [TEST_REGRESSION_PREVENTION.md](TEST_REGRESSION_PREVENTION.md)
3. **Test is flaky:** [FLAKY_TEST_TRIAGE.md](FLAKY_TEST_TRIAGE.md)
4. **Want to understand standards:** [tests/testing_standards.md](../../tests/testing_standards.md)
5. **Complete reference:** [TEST_SUITE_COMPLETE_GUIDE.md](TEST_SUITE_COMPLETE_GUIDE.md)

**Or ask for help:**
- Flaky test? File issue with `flaky-test` label
- Unclear standards? File issue with `test-debt` label
- Guard failed? Check [CI_GUARD_STAGES.md](CI_GUARD_STAGES.md#handling-guard-failures)

---

## CI Lanes Map (GitHub Actions)

```
Every push/PR:
├─ 🛡️  Guards (30s) ──────────────────────────────────┐
│  ├─ Architecture Compliance (core blindness, etc)    │
│  └─ Module Isolation & RefreshDatabase Guard         │
│                                                      │
├─ ✅ Unit Tests (20s) ─────────────────┐            │
├─ 🧪 Feature Tests (50s) ──────────────├─ Wait ─────┤
├─ 🏗️  Architecture Tests (10s) ────────┤  for       │
├─ 🔗 Integration Tests (50s) ──────────┤  guards   │
│                                        │            │
└─ 📈 Summary ◄────────────────────────┘            │
    (reports all results)

Artifact Publishing:
├─ Test Results → reports/test-results-latest.log
├─ Coverage Reports → codecov.io
└─ Compliance Logs → reports/ci_master.log
```

---

## Q&A

**Q: Do I have to run all lanes locally?**
A: No! Run just the lanes relevant to your change:
- Changed a service? Run unit tests
- Changed a route? Run feature tests
- Changed config? Run architecture tests

**Q: How long does a full test run take?**
A: Locally: ~5 minutes. In CI: ~3-5 minutes (parallel).

**Q: My test passed locally but failed in CI!**
A: Probably a flaky test. See [FLAKY_TEST_TRIAGE.md](FLAKY_TEST_TRIAGE.md).

**Q: Can I skip running guards?**
A: No—they run in CI anyway, so push will fail. Better to fix locally.

**Q: What do I do if a guard test has a bug?**
A: File an issue with `guard-failure` label. We'll fix it fast.

---

**Last Updated:** 2026-03-23
**Phase:** 9 - Developer Experience
**Status:** ✅ Clear lanes, fast feedback, explicit commands
