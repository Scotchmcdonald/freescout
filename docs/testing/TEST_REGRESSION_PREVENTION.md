# Test Regression Prevention Guide

## Purpose

This guide helps developers and maintainers prevent backsliding on the quality of the test suite. Use this for code reviews, pre-commit checks, and maintenance workflows.

## The Six Dimensions of Test Health

All regression prevention work focuses on these six dimensions (from `tests/testing_standards.md`):

| Dimension | Goal | Guard | Command |
|---|---|---|---|
| **Reliability** | 3+ consecutive green CI runs | None (manual monitoring) | `tail reports/test-results-latest.log` |
| **Signal Quality** | ≤150 `assertSee`, ≤10 `makePartial`, 0 relation assertions | Code review, static scan | `grep -r "assertSee\|makePartial\|assertHasMany"` |
| **Pyramid Balance** | 55% unit, 25% feature, 15% integration, ≤5% browser | Code review | Count test files by layer |
| **Module Isolation** | 0 `RefreshDatabase` in unit tests, 0 cross-module coupling | Automated guard | `ModuleUnitIsolationGuardTest` |
| **Coverage & Mutation** | Critical services ≥85% coverage, ≥80% mutation score | Mutation CI (Phase 8+) | `infection run` |
| **Type Safety** | 100% param and return-type coverage; `strict_types=1` in all files | `check-type-coverage.php`, quality gate | `php scripts/ci/check-type-coverage.php` |
| **Developer Experience** | Clear docs, automated guards, mutation CI | This document | All of the above |

> **Type Safety is a blocking gate.** Any PR that introduces untyped params or missing return types will fail `scripts/ci/check-testing-quality-gate.php`. See [TESTING_CONTRIBUTION_GUIDE.md](TESTING_CONTRIBUTION_GUIDE.md#type-safety-requirements) for the full rules.

---

## Code Review Checklist for PRs

### Before Approving a PR That Touches Tests

**New test added?**

- [ ] Test is in the correct layer (Unit/Feature/Integration/Browser)
- [ ] Does it import anything that shouldn't be there?
  - ❌ Unit tests should NOT import `RefreshDatabase` or cross-module Models
  - ❌ Feature tests should NOT use `makePartial()` to skip key features
  - ❌ Tests should NOT stub out the thing being tested
- [ ] All new methods and parameters have explicit type declarations
  - ❌ `public function process($data)` — missing types
  - ✅ `public function process(string $data): string` — fully typed
- [ ] New PHP file has `declare(strict_types=1)` at the top
- [ ] Test name is descriptive and human-readable
  - ✅ `it_prevents_non_admins_from_creating_modules`
  - ❌ `test_permissions` or `testIt`
- [ ] Assertions are high-signal
  - ✅ State changes: `assertDatabaseHas('users', [...])`
  - ✅ Event dispatch: `Event::assertDispatched(UserCreated::class)`
  - ❌ Framework internals: `assertInstanceOf(BelongsTo::class, ...)`
  - ❌ Config values: `assertEquals(config('auth.driver'), 'sanctum')`

**Test deleted or moved?**

- [ ] Was it a `@mutation-sensitive` test? Escalate to coverage lead if unclear

**Test modified?**

- [ ] Does it still test the right behavior or was the assertion weakened?
- [ ] If `skip()` added: does it reference an issue number? Is there a remediation plan?

**Architecture or isolation tests modified?**

- [ ] Were allowlists reduced or expanded? Require clear justification

---

## Pre-Commit Checklist for Developers

Before pushing code:

1. **Run focused test suite:**
   ```bash
   # Modified one test file?
   php artisan test path/to/TestFile.php

   # Modified a service?
   php artisan test path/to/*/Tests/Unit/ServiceTest.php
   ```

2. **Run guard tests (5 seconds):**
   ```bash
   php artisan test tests/Unit/ModuleUnitIsolationGuardTest.php \
                tests/Unit/RefreshDatabaseUsageGuardTest.php \
                tests/Architecture/
   ```

3. **Run the type coverage and quality gate checks:**
   ```bash
   php scripts/ci/check-type-coverage.php
   php scripts/ci/check-testing-quality-gate.php
   ```

4. **Inspect the log for warnings:**
   ```bash
   tail -n 50 reports/test-results-latest.log
   ```

4. **If any tests were deleted, verify on coverage dashboard** (Phase 8+):
   - Did mutation score decrease for that service?
   - If so, add a new test to compensate

---

## Weekly Maintenance Checklist

Use this cadence (see `TEST_MAINTENANCE_CADENCE.md` for full schedule):

### Reliability Review (Every Monday)
```bash
# Check recent CI runs
tail -n 100 reports/test-results-latest.log

# Identify any failing tests
grep -A 5 "FAILED" reports/test-results-latest.log

# If you see repeated failures, investigate immediately
```

### Signal Quality Scan (Every 2 Weeks)
```bash
# Count high-noise assertions
grep -r "assertSee\|assertSeeText" tests Modules --include='*.php' | wc -l

# Target: ≤150 total
# If trend is increasing, review recent PRs and push back

# Count low-value mocks
grep -r "makePartial()" tests Modules --include='*.php'

# Target: ≤10 total
# Each one should have a clear comment explaining why partial mocking is necessary
```

### Isolation Scan (Every 2 Weeks)
```bash
# Run the isolation guard
php artisan test tests/Unit/ModuleUnitIsolationGuardTest.php

# It should always pass. If it fails, investigate immediately.
# Allowlist should be shrinking, not growing.
```

### Coverage Delta (Every 2 Weeks)
```bash
# Check if any critical services lost coverage
git diff --stat HEAD~20 -- app/Services/**/*.php

# If services changed but tests didn't, add coverage or escalate
```

---

## Preventing Specific Regression Patterns

### Pattern 1: "The Junk Test"

**Symptom:** Test added that checks framework behavior, not application logic

**Example:**
```php
// ❌ BAD: Tests Laravel's hasMany, not our logic
test('user has many posts', function () {
    $user = User::factory()->create();
    $post = Post::factory()->for($user)->create();

    expect($user->posts)->toContain($post);
});
```

**Prevention:**
- Code reviewer asks: "What application behavior does this test verify?"
- If the answer is "It tests Eloquent," request deletion
- If the answer is "It documents our schema," move to docs/database/SCHEMA.md instead

---

### Pattern 2: "The Weak Assertion"

**Symptom:** Test has side effects but doesn't verify all of them

**Example:**
```php
// ❌ BAD: Checks one thing, but multiple things happen
test('create invoice', function () {
    $response = $this->post('/invoices', [...]);

    expect($response->status())->toBe(201);
    // ❌ Oops, forgot to verify:
    //   - Invoice was created
    //   - LineItems were attached
    //   - Event was dispatched
    //   - Notification was queued
});
```

**Prevention:**
- Code reviewer checks: "Did the test verify all the _effects_ of the action?"
- Fix:
```php
// ✅ GOOD: Comprehensive
test('create invoice', function () {
    $response = $this->post('/invoices', [...]);

    expect($response->status())->toBe(201);
    assertDatabaseHas('invoices', ['client_id' => ...]);
    assertDatabaseHas('invoice_line_items', ['invoice_id' => ...]);
    Event::assertDispatched(InvoiceCreated::class);
    Notification::assertSent(...); // or similar
});
```

---

### Pattern 3: "The Brittle Test"

**Symptom:** Test fails when unrelated code refactors, or only passes on specific timing

**Example:**
```php
// ❌ BAD: Brittle assertions
test('process queue', function () {
    dispatch(new ProcessInvoice($invoice));
    sleep(2); // Hope it's done by now
    assertDatabaseHas('invoice_summaries', [...]); // ❌ Order not guaranteed
});
```

**Prevention:**
- Code reviewer asks: "Does this test depend on implementation details or timing?"
- Fix:
```php
// ✅ GOOD: Robust
test('process queue', function () {
    Queue::fake();
    dispatch(new ProcessInvoice($invoice));
    Queue::assertPushed(ProcessInvoice::class);
}); // Don't test the queue processor itself; that's integration-level
```

---

### Pattern 4: "The Coupled Test"

**Symptom:** Unit test imports and instantiates a cross-module model

**Example:**
```php
// ❌ BAD: Cross-module coupling in unit test
use Modules\Crm\Models\Client;

test('charge service charges client', function () {
    $client = Client::factory()->create(); // ❌ Cross-module DB
    $service = app(ChargeService::class);
    $result = $service->charge($client);
    expect($result->success)->toBeTrue();
});
```

**Prevention:**
- Code reviewer checks: Are we instantiating cross-module models in a unit test?
- Fix: Move to Feature test or mock the Client:
```php
// ✅ GOOD: Unit isolation
test('charge service charges client', function () {
    $mockClient = Mockery::mock(Client::class, [
        'id' => 1,
        'email' => 'client@test.com'
    ]);

    $service = app(ChargeService::class);
    $result = $service->charge($mockClient);
    expect($result->success)->toBeTrue();
});
```

---

## Automated Guardrails

For critical checks, lean on automated tests that run in CI:

| Check | Test File | How It Works |
|---|---|---|
| `RefreshDatabase` in unit | `RefreshDatabaseUsageGuardTest` | Regex scan for `RefreshDatabase` keyword in `tests/Unit/**` |
| Cross-module coupling | `ModuleUnitIsolationGuardTest` | Detect direct model instantiation & cross-module service resolution |
| Service contracts | `ModuleBoundaryContractsTest` | Verify all contract implementations are bound in service providers |
| Architecture rules | `ArchTest`, `EnhancedArchitectureTest` | Pest's architecture rules (layer enforcement, naming, etc.) |

**These run automatically in CI and must pass before merge.** They are the final safety net for patterns that code review might miss.

---

## Escalation & Decision Making

### When to Escalate

**Unclear if something is test debt?**
- File issue with `test-debt` label
- Include: test name, concern, current value, target value

**Allowlist needs to grow?**
- Provide justification in PR comments
- Include: why exception is needed, remediation plan, expiry date

**Need to weaken a guard test?**
- Escalate to team lead immediately
- Guards are only relaxed if the codebase isn't ready yet, not the other way around

### Decisions That Must Involve the Team

- Changes to testing standards (`tests/testing_standards.md`)
- Changes to layer definitions (Unit/Feature/Integration/Browser)
- Allowlist expansions that don't have expirations
- Disabling or removing guard tests

---

## Quick Reference: Commands

```bash
# Run all guards (CI equivalent)
php artisan test tests/Unit/ModuleUnitIsolationGuardTest.php \
             tests/Unit/RefreshDatabaseUsageGuardTest.php \
             tests/Architecture/

# Quick signal quality scan
grep -r "assertSee\|makePartial" tests Modules --include='*.php' | wc -l

# Find quarantined tests
grep -r "skip(" tests Modules --include='*.php' | grep -i "flaky\|quarantine"

# List all RefreshDatabase usage in unit scope
grep -r "RefreshDatabase" Modules/*/Tests/Unit --include='*.php'

# Check coverage report (Phase 8+)
open reports/coverage/index.html
```

---

## Related Documentation

- `tests/testing_standards.md` — Target standards (the "what")
- `TESTING_CONTRIBUTION_GUIDE.md` — How to write good tests
- `CI_GUARD_STAGES.md` — Automated guardrails that run in CI
- `FLAKY_TEST_TRIAGE.md` — How to handle flaky tests
- `TEST_MAINTENANCE_CADENCE.md` — When to audit (the "when")
- `docs/testing/TESTING_ROADMAP_OUTCOMES.md` — Outcomes and sustainment focus (the "why")
