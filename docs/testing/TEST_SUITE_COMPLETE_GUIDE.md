# Test Suite & CI Guardrails: Complete Reference Guide

## Quick Navigation

**I want to...**

- 🚀 [Run all CI guardrails locally](#run-guardrails)
- ⚡ [Quick start with practical commands](TESTING_QUICK_START.md) — **START HERE**
- 📐 [Understand the 5 CI lanes architecture](CI_LANES_ARCHITECTURE.md)
- 📖 [Write a new test](#writing-tests)
- 🐛 [Handle a flaky test](#flaky-tests)
- 👀 [Review a test PR](#code-review)
- 🔍 [Debug a guard failure](#guard-failures)
- 📋 [Understand test standards](#standards)
- 🔄 [Maintain the test suite](#maintenance)

---

## <a id="run-guardrails"></a>🚀 Run All CI Guardrails

### Before pushing code:

```bash
# Run all compliance & test guards (takes ~30-60 seconds)
bash scripts/ci.sh

# Or run just the test isolation guards (faster)
php artisan test tests/Unit/ModuleUnitIsolationGuardTest.php \
                 tests/Unit/RefreshDatabaseUsageGuardTest.php \
                 tests/Architecture/ --parallel --processes=10
```

### Individual guard runs:

```bash
# Architecture compliance (core blindness, atomic counters, etc.)
bash scripts/ci/check-architecture-compliance.sh

# Test isolation and module boundaries
php artisan test tests/Unit/ModuleUnitIsolationGuardTest.php

# Code style and PHPStan
bash scripts/ci/check-code-style.sh && bash scripts/ci/check-static-analysis.sh
```

---

## <a id="writing-tests"></a>📖 Writing & Adding Tests

**Start here:** [TESTING_CONTRIBUTION_GUIDE.md](TESTING_CONTRIBUTION_GUIDE.md)

**Check these standards:**
- Layer placement (Unit vs Feature vs Integration)
- No `RefreshDatabase` in unit tests
- No cross-module coupling
- High-signal assertions (not framework internals)
- Descriptive test names

**Quick checklist before pushing:**
```bash
# 1. Run the specific test file
php artisan test path/to/NewTest.php

# 2. Run isolation guards
php artisan test tests/Unit/ModuleUnitIsolationGuardTest.php

# 3. Check for high-noise assertions
grep -n "assertSee\|makePartial" path/to/NewTest.php
```

**See also:** [TEST_REGRESSION_PREVENTION.md](TEST_REGRESSION_PREVENTION.md#code-review-checklist-for-prs)

---

## <a id="flaky-tests"></a>🐛 Handle a Flaky Test

**Step 1: Detect**

You notice a test fails inconsistently in CI but passes locally.

**Step 2: Document**

Create or update a GitHub issue with label `flaky-test` and details:
- Test name
- Failure rate (1/5 runs? 1/20?)
- Error message
- Environment (CI only? Local too?)

**Step 3: Quarantine (Temporary)**

```php
skip('Quarantined: timeout on parallel runs — issue #4567');
test('it processes webhook', function () {
    // test code
});
```

**Step 4: Investigate & Fix**

Follow patterns in [FLAKY_TEST_TRIAGE.md](FLAKY_TEST_TRIAGE.md):
- Check if it's a timing issue
- Check if mocking is incomplete
- Check for unclean database state
- Check for non-deterministic collection ordering

**Step 5: Verify & Remove Quarantine**

```bash
for i in {1..20}; do php artisan test --filter=testName; done
```

Once it passes consistently, remove the `skip()` call.

---

## <a id="code-review"></a>👀 Review a Test PR

**Checklist:** [TEST_REGRESSION_PREVENTION.md](TEST_REGRESSION_PREVENTION.md#code-review-checklist-for-prs)

**Key questions:**
- [ ] Test in the right layer?
- [ ] No cross-module coupling?
- [ ] High-signal assertions?
- [ ] No `RefreshDatabase` in unit tests?
- [ ] Guardrails pass?

**Patterns to watch for:**
- Junk tests (testing framework, not application logic)
- Weak assertions (side effects not verified)
- Brittle tests (timing dependencies, fragile selectors)
- Coupled tests (cross-module models in unit tests)

See [TEST_REGRESSION_PREVENTION.md](TEST_REGRESSION_PREVENTION.md#preventing-specific-regression-patterns) for detailed patterns and fixes.

---

## <a id="guard-failures"></a>🔍 Debug a Guard Failure

### Guard: ModuleUnitIsolationGuardTest

**Error:** `RefreshDatabase in non-allowlisted unit test`

**Solutions:**
1. Move test to Feature layer (where `RefreshDatabase` is allowed)
2. Mock the database dependency instead of using `RefreshDatabase`
3. Add to allowlist only if truly necessary (with expiry date)

See [CI_GUARD_STAGES.md](CI_GUARD_STAGES.md#handling-guard-failures) for detailed troubleshooting.

### Guard: Architecture Compliance

**Error:** `Core code imports from Modules`

**Solution:**
Create a contract interface and have the module implement it. Core uses the interface.

```php
// app/Contracts/BillingTemplateInterface.php
interface BillingTemplateInterface { }

// Modules/PIB/Services/BillingTemplate.php
class BillingTemplate implements BillingTemplateInterface { }
```

**See also:** [CI_GUARD_STAGES.md](CI_GUARD_STAGES.md#architecture--code-compliance-guards)

### Guard: Atomic Counters

**Error:** `Raw increment() on financial table`

**Solution:**
```php
app(AtomicCounterService::class)->increment(
    table: 'client_credits',
    where: ['client_id' => $id],
    column: 'balance',
    amount: 100
);
```

---

## <a id="standards"></a>📋 Understand Test Standards

**Read these in order:**

1. [tests/testing_standards.md](../../tests/testing_standards.md) — The target
2. [TESTING_CONTRIBUTION_GUIDE.md](TESTING_CONTRIBUTION_GUIDE.md) — How to write tests
3. [TEST_MAINTENANCE_CADENCE.md](TEST_MAINTENANCE_CADENCE.md) — When to audit

**Key concepts:**

| Term | Meaning |
|---|---|
| **Testing Pyramid** | 55% unit, 25% feature, 15% integration, ≤5% browser |
| **Signal Quality** | ≤150 assertSee, ≤10 makePartial, 0 relation assertions |
| **Isolation** | 0 RefreshDatabase in unit tests, 0 cross-module coupling |
| **Coverage** | Critical services ≥85% coverage, ≥80% mutation score (Phase 8+) |

---

## <a id="maintenance"></a>🔄 Maintain the Test Suite

**Weekly Checklist:**

```bash
# Check test reliability
tail -n 100 reports/test-results-latest.log

# Count high-noise assertions
grep -rc "assertSee\|makePartial" tests Modules --include='*.php' | \
  awk -F: '{sum+=$2} END {print "Total assertSee/makePartial: " sum}'

# Run isolation guard
php artisan test tests/Unit/ModuleUnitIsolationGuardTest.php

# Check for quarantined tests
grep -r "skip(" tests Modules --include='*.php' | grep -i "flaky\|quarantine"
```

**Bi-Weekly Tasks:**

- Review failed tests in CI
- Check signal quality trends
- Update allowlist expiry dates
- Investigate new flaky patterns

**Monthly Audit:**

See [TEST_MAINTENANCE_CADENCE.md](TEST_MAINTENANCE_CADENCE.md#monthly) for full scorecard.

---

## Guard & Compliance Matrix

| Guard | Script/Test | Runs | Blocks |
|---|---|---|---|
| **Module Isolation** | `ModuleUnitIsolationGuardTest.php` | Pre-merge | RefreshDatabase, cross-module coupling |
| **RefreshDatabase Usage** | `RefreshDatabaseUsageGuardTest.php` | Pre-merge | Direct RefreshDatabase in unit tests |
| **Architecture Compliance** | `check-architecture-compliance.sh` | Pre-merge | Core blindness, cross-module imports |
| **Atomic Counters** | `check-atomic-counters.sh` | Pre-merge | Raw increment on financial tables |
| **Code Style** | `check-code-style.sh` | Pre-merge | PHP formatting, naming violations |
| **Static Analysis** | `check-static-analysis.sh` | Pre-merge | PHPStan errors |
| **Strict Types** | `check-strict-types.sh` | Pre-merge | Missing declare(strict_types=1) |

---

## Common Workflows

### Workflow: Fix a Guard Failure

```bash
# 1. Understand the failure
cat reports/test-results-latest.log  # or reports/ci_master.log

# 2. Read the relevant guard docs
# (See "Guard Failures" section above)

# 3. Apply the fix

# 4. Re-run just that guard
php artisan test tests/Unit/ModuleUnitIsolationGuardTest.php

# 5. Push and verify in CI
```

### Workflow: Debug a Test Failure

```bash
# 1. Inspect the error
grep -A 10 "FAILED.*TestName" reports/test-results-latest.log

# 2. Run locally in isolation
php artisan test --filter=TestName

# 3. Run in parallel (if only fails in parallel)
php artisan test --filter=TestName --parallel --processes=10

# 4. If it's flaky, see the Flaky Tests section

# 5. If deterministic, debug and fix the test logic
```

### Workflow: Add a Critical Service Test

```bash
# 1. Read TESTING_CONTRIBUTION_GUIDE.md
# 2. Determine layer: Unit (pure logic) or Feature (integration)
# 3. Write test with high-signal assertions
# 4. Run guards
php artisan test tests/Unit/ModuleUnitIsolationGuardTest.php

# 5. Commit with clear message
git commit -m "test: add coverage for critical service XYZ

- Tests the happy path
- Tests error handling
- Verifies side effects (events, state changes)
"
```

---

## Links & References

**Roadmap & Sustainment:**
- `docs/testing/TESTING_ROADMAP_OUTCOMES.md` — Outcomes, operating model, and escalation triggers
- `docs/testing/TEST_MAINTENANCE_CADENCE.md` — Recurring audit rhythm

**Documentation:**
- [tests/testing_standards.md](../../tests/testing_standards.md)
- [TESTING_CONTRIBUTION_GUIDE.md](TESTING_CONTRIBUTION_GUIDE.md)
- [TEST_MAINTENANCE_CADENCE.md](TEST_MAINTENANCE_CADENCE.md)
- [CI_GUARD_STAGES.md](CI_GUARD_STAGES.md)
- [FLAKY_TEST_TRIAGE.md](FLAKY_TEST_TRIAGE.md)
- [TEST_REGRESSION_PREVENTION.md](TEST_REGRESSION_PREVENTION.md)

**CI Scripts:**
- `scripts/ci.sh` — Master orchestrator
- `scripts/ci/check-architecture-compliance.sh` — All architecture checks
- `scripts/ci/check-*.sh` — Individual compliance checks

---

## Questions?

1. **"Is this test good?"** → [Code Review Checklist](TEST_REGRESSION_PREVENTION.md#code-review-checklist-for-prs)
2. **"My test is flaky"** → [Flaky Test Triage](FLAKY_TEST_TRIAGE.md)
3. **"Guard failed, what do I do?"** → [Guard Failures](CI_GUARD_STAGES.md#handling-guard-failures)
4. **"How do I write a test?"** → [Contribution Guide](TESTING_CONTRIBUTION_GUIDE.md)
5. **"When should I audit?"** → [Maintenance Cadence](TEST_MAINTENANCE_CADENCE.md)

---

**Last Updated:** 2026-03-23
**Phase:** 10 - Sustainment
**Status:** ✅ Active maintenance mode
