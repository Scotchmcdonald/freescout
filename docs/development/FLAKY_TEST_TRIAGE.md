# Flaky Test Triage & Quarantine Workflow

## Purpose

This document defines how to identify, track, and remediate flaky tests that pass/fail inconsistently. Flaky tests undermine confidence in the suite and create false alarms in CI/CD.

## Definitions

**Flaky Test:** A test that sometimes passes and sometimes fails without code changes.

**Common Causes:**
- Timing dependencies (sleep, race conditions)
- External service unavailability (mocked incorrectly)
- Shared state between test runs (databases not cleaned)
- Randomized timeouts or mock return values
- Non-deterministic ordering in collections

---

## Detection & Monitoring

### How to Identify a Flaky Test

1. **Test fails on repeat runs without code changes:**
   ```bash
   for i in {1..5}; do php artisan test TestSomeFeature.php; done
   ```

2. **Failure appears "random" in CI but not locally**
   - Often indicates timing or race condition
   - More likely when tests run in parallel

3. **Failure mentions timeout, connection refused, or "timed out"**
   - Usually external service or timing-dependent

### Reporting Flaky Tests

When you discover a flaky test:

1. **Document the failure pattern:**
   - How often does it fail? (1/5 runs, 1/20 runs?)
   - What's the error message?
   - Does it fail locally or only in CI?

2. **Create or update a GitHub/GitLab issue** with label `flaky-test`:
   ```markdown
   **Test Name:** TestSomeFeature::it_processes_payment_webhook

   **Failure Rate:** ~1/5 CI runs
   **Error:** "Connection refused on stripe.com"
   **Environment:** CI only (Ubuntu, parallel test run)

   **Stack Trace:**
   [paste error]

   **Status:** Investigate mocking / timeout
   ```

3. **Notify the team** (async) so investigation can be prioritized

---

## Quarantine Protocol

### Temporarily Disable a Flaky Test

While investigating:

```php
// Temporarily mark test as skipped with reason
skip('Quarantined: flaky timing on parallel runs — issue #4567');
test('it_processes_webhook', function () {
    // test code...
});
```

Or use Pest's skip syntax:

```php
test('it processes webhook')
    ->skip('#4567: timeout on CI')
    ->expect($result)->toBe(true);
```

**Do NOT just comment out the test.** Use `skip()` so:
- The test still shows in reports
- Future runs automatically skip it
- You remember to come back to it

---

## Investigation & Remediation

### Step 1: Confirm the Issue Locally

```bash
# Run the test multiple times in sequence
for i in {1..10}; do
  php artisan test --filter=testName --parallel --processes=10
done

# Run in isolation (no parallel)
php artisan test --filter=testName  # Run this ~5 times
```

**Parallel vs. Sequential:**
- If it fails in parallel but passes sequentially → likely shared state/timing issue
- If it fails in both → likely test logic or mocking issue

---

### Step 2: Identify the Root Cause

**Common patterns:**

#### A) Timing/Race Condition
**Symptom:** Fails inconsistently, especially with `--parallel`

**Fix:**
```php
// ❌ BAD: Relies on exact timing
test('message is queued', function () {
    dispatch(new SendEmail($user));
    sleep(1);  // Hope it's processed by now
    assertDatabaseHas('emails', ['user_id' => $user->id]);
});

// ✅ GOOD: Explicit eventual consistency
test('message is queued', function () {
    expectWithinSeconds(2, function () {
        assertDatabaseHas('emails', ['user_id' => $user->id]);
    });
});

// ✅ BETTER: Use event spy without timing
Event::fake();
dispatch(new SendEmail($user));
Event::assertDispatched(SendEmail::class);
```

#### B) Incorrect Mocking
**Symptom:** Failure mentions external service (HTTP, external API)

**Fix:**
```php
// ❌ BAD: Mock might not be active on all runs
Stripe::mock();
$response = app(StripeGateway::class)->charge($card, $amount);

// ✅ GOOD: Explicit Http::fake() in feature tests
Http::fake(['api.stripe.com/*' => Http::response(['ok' => true])]);
$response = app(StripeGateway::class)->charge($card, $amount);

// ✅ BETTER: Spy on the request to verify it
Http::fake(['api.stripe.com/*' => Http::response(['ok' => true])]);
$response = app(StripeGateway::class)->charge($card, $amount);
Http::assertSent(fn ($request) => $request->url() === 'https://api.stripe.com/...');
```

#### C) Unclean Database State
**Symptom:** Test passes in isolation, fails when run with other tests

**Fix:**
```php
// ❌ BAD: Relies on previous test cleaning up
test('delete user', function () {
    $user = User::factory()->create();
    $user->delete();
    assertDatabaseMissing('users', ['id' => $user->id]);
});

// ✅ GOOD: Explicit setup/teardown in each test
test('delete user', function () {
    $user = User::factory()->create();

    $user->delete();

    assertDatabaseMissing('users', ['id' => $user->id]);
})->description('Each test is independent');

// ✅ BETTER: Use RefreshDatabase in Feature/Integration tests
test('delete user', function () {
    $user = User::factory()->create();
    $this->delete(route('users.destroy', $user));
    assertDatabaseMissing('users', ['id' => $user->id]);
})->with('feature'); // if using RefreshDatabase trait
```

#### D) Non-Deterministic Collection Ordering
**Symptom:** Assertion expects items in a specific order, but they're random

**Fix:**
```php
// ❌ BAD: Assumes order
$users = User::all()->pluck('name');
expect($users)->seq('Alice', 'Bob', 'Charlie');

// ✅ GOOD: Sort before comparison
$users = User::all()->pluck('name')->sort()->values();
expect($users)->seq('Alice', 'Bob', 'Charlie');

// ✅ BETTER: Test the behavior, not the order
$users = User::all();
expect($users)->toHaveCount(3);
expect($users->pluck('name'))->toContain('Alice', 'Bob', 'Charlie');
```

---

### Step 3: Implement the Fix

1. **Write a clear, minimal test** that reproduces the issue
2. **Apply the fix** (usually one of the patterns above)
3. **Verify locally with repeated runs:**
   ```bash
   for i in {1..20}; do php artisan test --filter=testName; done
   ```
4. **Remove the `skip()` marker** once fixed
5. **Push and monitor in CI** for a few runs

---

### Step 4: Close the Issue

Once the test passes consistently:
1. Remove `skip()` call
2. Add comment explaining the fix:
   ```php
   test('it processes webhook', function () {
       // Fixed issue #4567 by using explicit Http::fake() instead of relying on mock state
       Http::fake(['stripe.com/*' => Http::response(['success' => true])]);
       $result = app(StripeGateway::class)->process($webhook);
       expect($result->success)->toBeTrue();
   });
   ```
3. Link back to the GitHub/GitLab issue as resolved

---

## Flaky Test Prevention Checklist

When writing new tests, prevent flakiness:

- [ ] No `sleep()` or hardcoded timeouts
- [ ] All external APIs are mocked with `Http::fake()` or service spy
- [ ] Database tests use `RefreshDatabase` trait in Feature/Integration scope
- [ ] Unit tests don't use database or external services
- [ ] Tests don't depend on execution order (no shared state)
- [ ] Collection assertions don't assume a specific order
- [ ] Async/job execution is verified with queues, not timing
- [ ] DateTime assertions use freezeTime() or comparisons for tolerances

---

## Monitoring & Metrics

### Weekly Flaky Test Review

```bash
# Count skipped tests
grep -r "skip(" tests Modules --include='*.php' | grep -i "flaky\|quarantine\|issue"

# Check recent CI logs for timeout errors
grep -i "timeout\|connection refused\|timed out" reports/test-results-latest.log
```

### Tracking Metrics

Maintain a spreadsheet or issue tracker with:
- Test name
- Failure rate (% of runs that fail)
- Root cause (once identified)
- Status (quarantined, fixed, investigating)
- Issue link

---

## Related Documentation

- `tests/testing_standards.md` — Testing standards including isolation
- `TESTING_CONTRIBUTION_GUIDE.md` — How to write deterministic tests
- `TEST_MAINTENANCE_CADENCE.md` — Weekly/monthly review cadence
- `CI_GUARD_STAGES.md` — Automated guardrails to prevent test regression
