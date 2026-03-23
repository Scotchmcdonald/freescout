# CI Guard Stages & Automated Regression Prevention

## Purpose

This document defines the automated guardrails that run in CI to prevent backsliding on:
- **Test Guards:** Test isolation, signal quality, and database hygiene
- **Architecture Guards:** Module boundaries, core blindness, atomic operations, rate limiting
- **Compliance Checks:** Code style, migrations, structure, and patterns

All guard tests and compliance checks must pass before merges are accepted.

## Quick Start

**Run all CI guardrails locally:**
```bash
# Test + Architecture guards
bash scripts/ci/check-architecture-compliance.sh && php artisan test tests/Unit/ModuleUnitIsolationGuardTest.php tests/Architecture/
```

**See the master CI pipeline:**
```bash
bash scripts/ci.sh  # Runs all checks, logs to reports/ci_master.log
```

---

## Architecture & Code Compliance Guards

These guards are orchestrated by `scripts/ci/check-architecture-compliance.sh` and enforce architectural patterns from `SYSTEM_ARCHITECTURE.md`.

### 1. Core Blindness Check
**File:** `scripts/ci/check-core-blindness.sh`
**Purpose:** Prevent core code from importing feature modules

**Violations Blocked:**
- ❌ Core (app/) imports from Modules/
- ❌ Core listeners import cross-module Models
- ✅ Core CAN import module Events/DTOs/Contracts

**Example:**
```php
// ❌ BLOCKED in app/Services/BillingService.php
use Modules\PIB\Models\Invoice;

// ✅ ALLOWED in app/Services/BillingService.php
use Modules\PIB\Contracts\BillingTemplateInterface;
```

**Running Locally:**
```bash
bash scripts/ci/check-core-blindness.sh
```

---

### 2. Cross-Module Import Check
**File:** `scripts/ci/check-cross-module-imports.sh`
**Purpose:** Reduce module coupling via careful dependency direction

**Violations Blocked:**
- ❌ Core code imports from Modules
- ❌ Module listeners import Models from other modules
- ✅ Module listeners CAN import Events from other modules

**Running Locally:**
```bash
bash scripts/ci/check-cross-module-imports.sh
```

---

### 3. Atomic Counter Operations Check
**File:** `scripts/ci/check-atomic-counters.sh`
**Purpose:** Prevent race conditions on financial tables

**Violations Blocked:**
- ❌ Raw `increment()` on financial tables
- ❌ Direct `DB::table()` operations on credit/counter tables
- ✅ Must use `AtomicCounterService` for financial operations

**Financial Tables Protected:**
- `client_asset_counters`
- `client_user_counters`
- `client_credits`
- `credit_ledger`

**Example:**
```php
// ❌ BLOCKED: Race condition
DB::table('client_credits')->where('client_id', $id)->increment('balance', 100);

// ✅ REQUIRED: Atomic operation
app(AtomicCounterService::class)->increment(
    table: 'client_credits',
    where: ['client_id' => $id],
    column: 'balance',
    amount: 100
);
```

**Running Locally:**
```bash
bash scripts/ci/check-atomic-counters.sh
```

---

### 4. Rate Limiter Usage Check
**File:** `scripts/ci/check-rate-limiter-usage.sh`
**Purpose:** Ensure API endpoints have rate limiting

**Running Locally:**
```bash
bash scripts/ci/check-rate-limiter-usage.sh
```

---

### 5. Event Inheritance Check
**File:** `scripts/ci/check-event-inheritance.sh`
**Purpose:** Ensure events extend the correct base class

**Running Locally:**
```bash
bash scripts/ci/check-event-inheritance.sh
```

---

### 6. Listener Inheritance Check
**File:** `scripts/ci/check-listener-inheritance.sh`
**Purpose:** Ensure listeners extend the correct base class

**Running Locally:**
```bash
bash scripts/ci/check-listener-inheritance.sh
```

---

### 7. UI/UX Standards Check
**File:** `scripts/ci/check-ui-ux-standards.sh`
**Purpose:** Enforce design system compliance

**Running Locally:**
```bash
bash scripts/ci/check-ui-ux-standards.sh
```

---

### 8. Additional Compliance Checks
Other important checks in the CI pipeline:

| Check | Script | Purpose |
|---|---|---|
| Code Style | `check-code-style.sh` | PHP formatting, naming conventions |
| Static Analysis | `check-static-analysis.sh` | PHPStan errors |
| Strict Types | `check-strict-types.sh` | All files must declare(strict_types=1) |
| Migration Safety | `check-migration-safety.sh` | Ensure down() methods exist |
| Env Parity | `check-env-parity.sh` | .env.example matches config |
| Folder Structure | `check-folder-structure-capitalization.sh` | Consistent naming |

---

## Test Guard Suite

### 1. Module Unit Isolation Guard
**File:** `tests/Unit/ModuleUnitIsolationGuardTest.php`
**Runs:** On every push to main branches
**Purpose:** Enforce strict module isolation in unit tests

**Checks:**
- ✅ No `RefreshDatabase` in non-allowlisted unit tests
- ✅ No cross-module model instantiation in unit tests
- ✅ No cross-module service resolution via `app()->make()` or `resolve()`
- ✅ No feature tests importing external APIs without `Http::fake()` or `Http::preventStrayRequests()`
- ✅ Allowlist entries have not expired

**Failure Examples:**
```php
// ❌ NOT ALLOWED in Modules/CaseManager/Tests/Unit/
// Creating a cross-module model directly
$client = \Modules\Crm\Models\Client::factory()->create();

// ❌ NOT ALLOWED - RefreshDatabase in unit test
use RefreshDatabase;
```

**Running Locally:**
```bash
php artisan test tests/Unit/ModuleUnitIsolationGuardTest.php --parallel --processes=10
```

---

### 2. RefreshDatabase Usage Guard
**File:** `tests/Unit/RefreshDatabaseUsageGuardTest.php`
**Runs:** On every push to main branches
**Purpose:** Catch accidental `RefreshDatabase` usage in unit tests

**Checks:**
- ✅ No `RefreshDatabase` trait usage in non-allowlisted files
- ✅ Reports new violations immediately

**Failure Examples:**
```php
// ❌ NOT ALLOWED in tests/Unit/**
use Illuminate\Foundation\Testing\RefreshDatabase;
```

**Running Locally:**
```bash
php artisan test tests/Unit/RefreshDatabaseUsageGuardTest.php --parallel --processes=10
```

---

### 3. Module Boundary Contracts Guard
**File:** `tests/Architecture/ModuleContracts/ModuleBoundaryContractsTest.php`
**Runs:** On every push to main branches
**Purpose:** Ensure modules provide required service contracts

**Checks:**
- ✅ All modules have service providers registered
- ✅ Core contracts are bound in module providers
- ✅ No unregistered contract implementations floating in service classes

**Failure Examples:**
```php
// ❌ Service implements a core contract but is not bound
class GoogleUserProvider implements \App\Contracts\UserProvider {
    // ... not registered in GoogleAdminServiceProvider
}
```

**Running Locally:**
```bash
php artisan test tests/Architecture/ModuleContracts/ --parallel --processes=10
```

---

### 4. Architecture Rules Guard
**File:** `tests/ArchTest.php`
**Runs:** On every push to main branches
**Purpose:** Enforce architectural rules via PestArchitect (if configured)

**Checks:**
- May include layer enforcement (if configured)
- May include naming conventions (if configured)
- May include forbidden dependencies (if configured)

**Running Locally:**
```bash
php artisan test tests/ArchTest.php --parallel --processes=10
```

---

### 5. Enhanced Architecture Guard
**File:** `tests/Architecture/EnhancedArchitectureTest.php`
**Runs:** On every push to main branches
**Purpose:** Validate module organization and structure

**Checks:**
- ✅ Module directory structure compliance
- ✅ Service provider wiring
- ✅ Contract implementation discovery

**Running Locally:**
```bash
php artisan test tests/Architecture/EnhancedArchitectureTest.php --parallel --processes=10
```

---

## Complete CI Orchestration

### Master CI Pipeline

The `scripts/ci.sh` script orchestrates ALL guards in a single run:

```bash
bash scripts/ci.sh
```

**Output:**
- Logs to `reports/ci_master.log`
- Runs all scripts in `scripts/ci/` sequentially
- Reports status for each check
- Exits with code 1 if any check fails

**Full sequence:**
1. Architecture compliance checks (7 checks)
2. Code style and static analysis
3. Migration safety checks
4. Environment and structure checks
5. Test execution (if using separate pipeline)

---

## CI Enforcement Schedule

### Every Commit (Pre-Merge)

**Developer runs locally:**
```bash
# Architecture & compliance checks (15-30 seconds)
bash scripts/ci/check-architecture-compliance.sh

# Test isolation guards (5-10 seconds)
php artisan test tests/Unit/ModuleUnitIsolationGuardTest.php \
                 tests/Unit/RefreshDatabaseUsageGuardTest.php \
                 tests/Architecture/
```

**Full CI run:**
```bash
# All guards orchestrated
bash scripts/ci.sh
```

### Weekly (Automated/Manual)

Run full test suite and watch for patterns:
```bash
php artisan test --parallel --processes=10

# Check for new violations
tail -n 100 reports/test-results-latest.log
grep -E "FAILED|Failed asserting" reports/test-results-latest.log
```

### Monthly (Full Audit)

See `TEST_MAINTENANCE_CADENCE.md` for comprehensive scorecard refresh:
```bash
# Test distribution scan
find tests Modules -name "*Test.php" -o -name "*PestTest.php" | \
  grep -E "(Unit|Feature|Integration|Browser)" | \
  sed 's|.*/\(Unit\|Feature\|Integration\|Browser\)/.*|\1|' | \
  sort | uniq -c

# Architecture compliance audit
bash scripts/ci/check-architecture-compliance.sh -v
```

---

## Handling Guard Failures

### Module Isolation Guard Failed

**Example failure:**
```
Module unit tests must not use RefreshDatabase unless allowlisted.
Violations:
Modules/Payment/Tests/Unit/ChargeServiceTest.php
```

**Resolution paths:**

1. **If the test can use mocking instead:**
   ```php
   // Before (uses RefreshDatabase)
   use RefreshDatabase;
   test('charge succeeds with valid card', function () {
       $charge = Charge::factory()->create();
   });

   // After (mocks persistence)
   test('charge succeeds with valid card', function () {
       $chargeData = ['amount' => 100, 'card' => '4242...'];
       $service = app(ChargeService::class);
       // Test pure logic without DB
       expect($service->validate($chargeData))->toBeTrue();
   });
   ```

2. **If the test requires real persistence:**
   - Move test to `Modules/Payment/Tests/Feature/` (Feature layer)
   - Add `use RefreshDatabase`
   - Verify it's testing controller/request behavior, not pure logic

3. **If allowlisting is truly needed (rare):**
   - Add entry to `allowlistedRefreshDatabaseBaseline` in `ModuleUnitIsolationGuardTest.php`
   - Include `@expires YYYY-MM-DD` comment
   - File a follow-up issue to remove allowlist entry by expiry date

---

### Boundary Contracts Guard Failed

**Example failure:**
```
Service GoogleUserProvider implements App\Contracts\UserProvider
but is not bound in GoogleAdminServiceProvider
```

**Resolution:**

```php
// In Modules/GoogleAdmin/Providers/GoogleAdminServiceProvider.php
public function register(): void
{
    $this->app->bind(\App\Contracts\UserProvider::class, function () {
        return new \Modules\GoogleAdmin\Services\GoogleUserProvider();
    });
}
```

---

### Architecture Guard Failed

Check the specific error message in the test output. Common issues:
- Module structure doesn't match expected layout
- Service provider not registered in module.json
- Contract not found in module

Contact the team for clarification; these rules are less frequently changed but require coordination.

---

## Preventing False Positives

### Pattern Tolerance in Guard Regexes

Guards use flexible regex patterns to tolerate whitespace variations:
```php
// These are all detected the same way:
use RefreshDatabase;           // ✅ detected
use  RefreshDatabase;          // ✅ detected (double space)
use \Illuminate\Foundation\Testing\RefreshDatabase;  // ✅ detected
use Illuminate\Foundation\Testing\RefreshDatabase;   // ✅ detected
```

---

## Creating New Guard Tests

When adding a new guardrail:

1. **Place it in `tests/Architecture/` or `tests/Unit/` depending on scope**
2. **Use descriptive assertion messages** so failures explain the problem
3. **Document the guard in this file** (CI_GUARD_STAGES.md)
4. **Add it to the CI workflow** (see `.github/workflows/test.yml`)
5. **Test locally before pushing:**
   ```bash
   php artisan test <new_guard_file> --parallel --processes=10
   ```

---

## Monitoring & Alerting

### Test Result Logs
After every CI run, review:
```bash
tail -n 100 reports/test-results-latest.log
```

### Common Log Patterns

**All guards passing:**
```
Tests:    XX passed (YY assertions)
Duration: X.XXs
```

**Guard failed:**
```
FAILED  Tests\Unit\ModuleUnitIsolationGuardTest > ...
Module unit tests must not use RefreshDatabase unless allowlisted.
```

### Triage Protocol

1. Identify which guard failed
2. Review the log output for the specific violation
3. Follow the "Handling Guard Failures" section above
4. Push fix and re-run CI
5. If fix requires broader discussion, file an issue with `guard-failure` label

---

## Integration with Development Workflow

### Before Pushing Code
Developer runs:
```bash
# Full local validation
php artisan test --parallel --processes=10
```

### In PR Review
Reviewers check:
- ✅ All CI guard tests pass
- ✅ No new allowlist entries without expiry dates
- ✅ New tests follow the standards in `TESTING_CONTRIBUTION_GUIDE.md`

### After Merge
Weekly maintenance:
- Review guard health
- Close resolved issues
- Update allowlist entries nearing expiry

---

## Appendix: Quick Command Reference

```bash
# Run all guards
php artisan test tests/Unit/ModuleUnitIsolationGuardTest.php \
             tests/Unit/RefreshDatabaseUsageGuardTest.php \
             tests/Architecture/ --parallel --processes=10

# Run specific guard
php artisan test tests/Unit/ModuleUnitIsolationGuardTest.php

# Check latest results
tail -f reports/test-results-latest.log

# List failing tests
grep -E "FAILED|Failed asserting" reports/test-results-latest.log

# Count violations by type
grep "RefreshDatabase" reports/test-results-latest.log
grep "cross-module" reports/test-results-latest.log
```

---

## Related Documentation

- `tests/testing_standards.md` — Target standards we're achieving
- `TESTING_CONTRIBUTION_GUIDE.md` — How to write new tests
- `TEST_MAINTENANCE_CADENCE.md` — How often to run audits
- `docs/development/WIP/Testing/` — Phase roadmap and current work
