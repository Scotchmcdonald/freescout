# Mutation Testing Guide

**Updated:** Phase 5 Finalization (March 2026)
**Purpose:** Best practices for running, interpreting, and optimizing mutation testing locally and in CI/CD.

---

## Quick Start

### Agent-Safe Run (Recommended)

```bash
# Run detached so the process survives agent/tool interruptions
nohup bash scripts/ci/check-mutation-tier2.sh > reports/mutation-tier2-nohup.log 2>&1 &
echo $! > reports/mutation-tier2.pid

# Monitor progress
tail -f reports/mutation-tier2-nohup.log
```

```bash
# Stop manually if needed
kill "$(cat reports/mutation-tier2.pid)"
```

### Run Mutation Locally (Tier 2)

```bash
# Fast Tier 2 check (app/Services + Actions, ~30-45 min)
bash scripts/ci/check-mutation-tier2.sh
```

### Run Full Mutation Test (Tier 1)

```bash
# Complete 3-module mutation suite (re-runs all 6308 tests first, ~25 min initial + mutation phase)
# NOTE: pipe with 2>&1 — Infection writes mutation logs to stderr, so bare '| tee' only captures
# the test-runner stdout and leaves infection.log stale.
XDEBUG_MODE=off ./vendor/bin/infection 2>&1 | tee reports/infection-run-output.log

# Or agent-safe (detached):
nohup XDEBUG_MODE=off ./vendor/bin/infection > /dev/null 2>&1 &
echo $! > reports/infection-tier1.pid
tail -f reports/infection.log   # text logger output; updated during run
```

### View Results

```bash
# Last run summary
cat reports/infection-extended-summary.json

# Detailed escape report
tail -100 reports/infection-extended.log

# JSON results for parsing
cat reports/infection-extended-summary.json | php -r 'echo json_encode(json_decode(file_get_contents("php://stdin")), JSON_PRETTY_PRINT);'
```

---

## Understanding Mutation Testing

### What is a Mutant?

A mutant is a **tiny intentional code change** that should cause a test to fail. If tests still pass after the mutation, the mutant **escaped**, indicating weak test coverage.

**Example:**

```php
// Original code
$result = $price * 1.10;  // Add 10% tax

// Mutant 1: operator change
$result = $price * 1.0;   // Should fail tests

// Mutant 2: constant removal
$result = $price;          // Should fail tests

// Mutant 3: operator flip
$result = $price / 1.10;   // Should fail tests
```

### What Does MSI Mean?

**MSI (Mutation Score Indicator)** = percentage of mutants killed by tests.

```
MSI = (Killed Mutants / Total Mutants) × 100
```

**Interpretation:**
- **MSI ≥ 90:** Excellent; very high confidence in test suite.
- **MSI 70–89:** Good; acceptable for new code; room for improvement.
- **MSI 50–69:** Fair; tests exist but miss critical branches.
- **MSI < 50:** Weak; tests are shallow; high regression risk.

**Example:**
```
Total Mutants: 1000
Killed: 750
Escaped: 250
MSI = 75%  ← Good score, but 250 mutants still uncaught
```

---

## Interpreting Escaped Mutants

### Common Escape Patterns

#### 1. **Missing Assertion**

```php
// Test code
test('increments counter', function () {
    $counter = new Counter();
    $counter->increment();
    // ❌ PROBLEM: No assertion!
});

// Mutant escapes: $counter->count++ removed
// Result: Test passes even with mutation

// FIX: Add assertion
test('increments counter', function () {
    $counter = new Counter();
    $counter->increment();
    expect($counter->get())->toBe(1);  // ✅ Assertion catches mutation
});
```

#### 2. **Untested Branch**

```php
// Code
if ($user->isAdmin()) {
    $this->grantFullAccess($user);
}

// Test only checks non-admin path
test('normal user has limited access', function () {
    $user = User::factory()->create(['role' => 'user']);
    $service->grantAccess($user);
    expect($user->can('read'))->toBeTrue();
});

// Mutant escapes: `isAdmin()` inverted, test never catches it
// FIX: Add admin path test
test('admin user gets full access', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $service->grantAccess($user);
    expect($user->can('admin'))->toBeTrue();
});
```

#### 3. **Type Coercion**

```php
// Code
return $value == 5;  // Loose comparison (type-permissive)

// Mutant: $value = "5" (string, but == accepts it)
// Both pass! Mutation escapes.

// FIX: Use strict comparison
return $value === 5;  // Now string "5" fails, mutant caught
```

#### 4. **Logic Condition**

```php
// Code
if ($age > 18 && $verified) {
    $this->allowAccess();
}

// Test only checks one condition
test('verified adults can access', function () {
    $user = User::factory()->create(['age' => 21, 'verified' => true]);
    $service->allowAccess($user);
    expect($user->can('access'))->toBeTrue();
});

// Mutant: `&&` changed to `||`, test still passes
// FIX: Test both branches
test('unverified user denied even if adult', function () {
    $user = User::factory()->create(['age' => 21, 'verified' => false]);
    $service->allowAccess($user);
    expect($user->can('access'))->toBeFalse();
});
```

---

## Writing Tests to Kill Mutants

### Strategy 1: Test Side Effects

Mutants often **eliminate side effects** (saves, assignments, calls). Test that effects happened:

```php
// ❌ WEAK: Only tests return value
test('processes order', function () {
    $order = Order::factory()->create();
    result = $service->process($order);
    expect($result)->toEqual(['status' => 'processed']);
});

// ✅ STRONG: Tests side effect (database change)
test('processes order and marks as processed', function () {
    $order = Order::factory()->create(['status' => 'pending']);
    $service->process($order);

    $order->refresh();
    expect($order->status)->toBe('processed');
    expect(ProcessLog::count())->toBe(1);
});

// Mutant trying to remove $order->update() call gets caught!
```

### Strategy 2: Test Boundary Conditions

Mutations often **flip operators** (< to <=, > to >=). Test exact boundaries:

```php
// ❌ WEAK: Only happy path
test('applies discount threshold', function () {
    $price = 100;
    $result = $service->applyDiscount($price);
    expect($result)->toBe(80);  // 20% off
});

// ✅ STRONG: Test both sides of boundary
test('applies discount only above threshold of 50', function () {
    // Just below threshold: no discount
    expect($service->applyDiscount(49))->toBe(49);

    // At threshold: discount applies
    expect($service->applyDiscount(50))->toBe(40);  // 20% off

    // Above threshold: discount applies
    expect($service->applyDiscount(100))->toBe(80);
});

// Mutants changing `> 50` to `>= 50` get caught!
```

### Strategy 3: Test Exception Paths

Mutations can **remove error checks**. Verify exceptions are thrown:

```php
// ❌ WEAK: Only success case
test('transfers money', function () {
    $account = Account::factory()->create(['balance' => 100]);
    $service->transfer($account, 50);
    expect($account->balance)->toBe(50);
});

// ✅ STRONG: Also test failure cases
test('throws exception for insufficient funds', function () {
    $account = Account::factory()->create(['balance' => 100]);

    expect(fn () => $service->transfer($account, 150))
        ->toThrow(InsufficientFundsException::class);
});

// Mutant removing the balance check is caught!
```

---

## Using @infection:ignore-all

### When to Ignore

Use `@infection:ignore-all` for code that's **hard to mutate-test** or **non-critical**:

```php
/**
 * @infection:ignore-all
 * This is generated code; mutations are noise.
 * See: generateModelFactories() in bootstrap.
 */
public function autoGeneratedMethod()
{
    // ...
}
```

**Valid reasons:**
- Generated/boilerplate code.
- External API wrappers with no local logic.
- Configuration setters (getters/setters often escape, low risk).
- Logging/telemetry with no business impact.

**Invalid reasons (don't ignore):**
- "Tests are hard to write" (fix by improving tests).
- "We're busy" (technical debt; mutation escapes later).
- "It's in production" (even more reason to test!).

### Format

```php
// Single class method
/**
 * @infection:ignore-all
 * Reason: [brief explanation]
 */
public function methodName() { }

// Entire class
/**
 * @infection:ignore-all
 */
class SkippedClass { }
```

---

## Running Mutation Locally

### Before Pushing to CI

```bash
# 1. Run fast Tier 2 check locally
bash scripts/ci/check-mutation-tier2.sh

# 2. If MSI < 70, review escaped mutants
tail -50 reports/infection-extended.log

# 3. Add tests or @infection:ignore-all as appropriate
# Edit your test file or source file

# 4. Re-run mutation (or full tests to verify fixes)
bash scripts/ci/check-mutation-tier2.sh
```

### For Specific Service

```bash
# Mutation test only app/Services/YourService.php
./vendor/bin/infection \
    --configuration=infection-extended.json5 \
    --filter="app/Services/YourService.php" \
    --threads=4
```

### Debug Specific Mutant

If a mutant is puzzling, inspect the log:

```bash
# View escaped mutants with line context
grep -A 10 "Escaped:" reports/infection-extended.log | head -50
```

Output will show:
```
Escaped:
1) app/Services/YourService.php:42
   - Line 42: $result = $value * 1.10
   - Mutant: Multiplication by 1.0
   - Original: $value *= 1.10
```

Then review line 42 in the actual file and add a test that captures the multiplication.

---

## Performance Tuning

### Mutation Timeout

If mutation takes > 45 min, try:

```bash
# Reduce threads (default 6)
THREADS=4 bash scripts/ci/check-mutation-tier2.sh

# Or run just the module in question
./vendor/bin/infection \
    --filter="app/Services/LargeService.php" \
    --threads=6
```

### Coverage Caching

Infection can reuse coverage from recent runs:

```bash
# Reuse coverage from previous collection
# (If code hasn't changed much)
./vendor/bin/infection --with-coverage-path=storage/infection/coverage
```

### Parallel Optimization

If you have 16+ cores:

```bash
THREADS=8 bash scripts/ci/check-mutation-tier2.sh
```

But watch CI runner CPU; over-subscribing can cause slowdown.

---

## CI Integration Checklist

**Before Merging:** Mutation Tier 2 must pass.

1. **Local validation** (pre-push):
   ```bash
   bash scripts/ci/check-mutation-tier2.sh
   ```

2. **CI will run automatically** on push (GitHub Actions):
   - Phase 1: Parallel tests (2 min)
   - Phase 2: Coverage collection (8 min)
   - Phase 3: Mutation Tier 2 (30-45 min)

3. **PR comment** will show:
   ```
   MSI: 73 / 100
   Covered MSI: 76 / 100
   Killed: 2847 / 3890
   Escaped: 42
   ```

4. **If MSI < 70:**
   - Review escaped mutants comment
   - Add tests or ignore with justification
   - Push fix; CI re-runs automatically

---

## Troubleshooting

### Problem: Timeout (> 50 min)

**Cause:** Too many mutants, threads slow.

**Solution:**
```bash
# Reduce scope
./vendor/bin/infection --filter="app/Services"

# Reduce threads
THREADS=4 bash scripts/ci/check-mutation-tier2.sh

# Skip non-covered code optimization
./vendor/bin/infection --with-uncovered
```

### Problem: MSI Too Low (< 50)

**Cause:** Test suite is shallow.

**Action:** Review escaped mutants in log and add assertions:

```bash
# See which tests/lines are escaping
grep -B 2 "Escaped:" reports/infection-extended.log | grep -E "app/Services.*\.php"
```

Then:
1. Look at the line in the service.
2. Check if tests cover it.
3. Add missing assertion or expand test.

### Problem: "Coverage XML Not Found"

**Cause:** Coverage wasn't collected before mutation run.

**Solution:**
```bash
# Collect coverage first
XDEBUG_MODE=coverage php -d memory_limit=3G ./vendor/bin/pest \
    --coverage-xml=storage/infection/coverage

# Then run mutation
./vendor/bin/infection
```

---

## Best Practices

### 1. **Kill Mutants Incrementally**

Don't ignore escaped mutants; improve tests instead.

```bash
# Bad: Ignore hard mutants
/**
 * @infection:ignore-all
 * TODO: hard to test
 */

# Good: Add better tests
test('boundary condition at 100 units', function () {
    expect($service->calculate(100))->toBe(expectedValue);
});
```

### 2. **Review Escaped Mutants in Code Review**

When a PR shows escaped mutants, ask: "Is this branch tested?" If not, request test improvements.

### 3. **Use Mutation as a Learning Tool**

Escaped mutants often reveal:
- Missing test cases.
- Weak assertions.
- Overly complicated logic (simplify).

### 4. **Set Realistic MSI Targets**

| Code Type | Target MSI |
|:--|--:|
| New feature code | ≥ 80 |
| Refactored code | ≥ 75 |
| Legacy code (uplift) | ≥ 70 |
| UI/Controller logic | ≥ 65 |
| Generated code | ∅ (ignored) |

### 5. **Monitor Trends**

Track MSI per commit:

```bash
# Capture baseline
./vendor/bin/infection 2>&1 | grep "MSI" | tee reports/msi-baseline-$(date +%Y%m%d).log
```

If MSI drops, investigate why (tests regressed, code complexity increased).

---

## Reference: Infection Commands

```bash
# Full mutation (Tier 1: 3 services, ~2-3 hrs)
./vendor/bin/infection

# Extended mutation (Tier 2: +app/Services, ~30-45 min)
./vendor/bin/infection --configuration=infection-extended.json5

# Specific file
./vendor/bin/infection --filter="app/Services/YourFile.php"

# Dry-run (show what would mutate, don't run tests)
./vendor/bin/infection --dry-run

# With minimal coverage (skip non-covered regions)
./vendor/bin/infection --with-uncovered

# Specific mutation type
./vendor/bin/infection --mutators=Increment

# Set threads
./vendor/bin/infection --threads=4

# Skip initial test run (use cached results)
./vendor/bin/infection --skip-initial-tests

# Debug output
./vendor/bin/infection --debug > mutation-debug.log
```

---

## Summary

| Task | Command | Time |
|:--|:--|--:|
| Quick local check | `bash scripts/ci/check-mutation-tier2.sh` | ~1-2 min (mutation only, uses cached coverage) |
| Full mutation suite | `./vendor/bin/infection` | 2-3 hrs |
| Specific service | `./vendor/bin/infection --filter="..."` | 5-15 min |
| View last results | `cat reports/infection-extended.log` | — |
| Parse JSON results | `cat reports/infection-extended-summary.json` | — |

**Tier 1 baseline (2026-03-25):** MSI 100 / Covered MSI 100 — 1143/1378 mutants killed, 0 escaped.
Scope: `Modules/PIB/Services`, `Modules/ContractManager/Services`, `Modules/Payment/Services`.

**Tier 2 baseline (2026-03-25):** MSI 100 / Covered MSI 100 — 1666/2743 mutants killed, 0 escaped, ~1m 12s mutation phase.
Scope: Tier 1 + `app/Services` + `app/Actions`.

**Threshold (both tiers):** MSI ≥ 95, Covered MSI ≥ 95.
**Regression:** MSI drops below 95 → investigate & fix before merging.
**Goal:** Catch real bugs before production — mutation testing proves tests work, not just run.

