# Phase 4: Throttle / Rate-Limit Boundary Coverage Expansion

**KPI affected:** Boundary Coverage (current 62/100 → target 77/100)
**Effort:** ~3 hours
**Risk:** Low — additive only; no existing tests changed

---

## Problem Statement

Rate-limiting and throttle boundary verification is concentrated in a single file:

```
tests/Feature/AuthRateLimitingTest.php   ← only file with actual 429 assertions
```

The audit found 16 files containing any throttle keyword (`assertStatus(429)`, `throttle`, `RateLimiter`, `assertTooManyRequests`). Of those, only `AuthRateLimitingTest.php` has real threshold-exhaustion assertions (i.e., it makes N+1 requests and confirms the (N+1)th returns `429` with correct `Retry-After` and `X-RateLimit-*` headers).

**Unprotected API surfaces (confirmed by absence in grep results):**

| Surface | Route/Entrypoint | Risk if untested |
|---------|-----------------|-----------------|
| Webhook ingestion endpoints | `Modules/*/routes/api.php` webhook paths | No guarantee throttle middleware is applied — a missing `throttle:` route group silently allows unlimited ingest |
| Module API routes (non-auth) | `Modules/CaseManager`, `Modules/Crm`, `Modules/PIB` API paths | Rate-limit middleware application is unverified |
| Password reset / email verification | `POST /forgot-password`, `POST /email/verification-notification` | Auth-adjacent endpoints easy to enumerate |
| Payment gateway callbacks | `Modules/Payment/routes/` callback handlers | High-value target; should have both throttle AND idempotency protection |

---

## Current Coverage Baseline

```bash
# Current files with any 429/throttle/rate signal
grep -rl 'assertStatus(429)\|throttle\|RateLimiter\|assertTooManyRequests' tests/Feature tests/Integration --include='*.php'
# Result: 16 files — but only AuthRateLimitingTest.php has threshold-exhaustion 429 assertions
```

---

## Fix Steps

### Step 1: Audit which routes have `throttle:` middleware applied

Before writing tests, establish ground truth on which routes actually have throttle middleware registered. This ensures tests are testing real behavior, not mocking it away.

```bash
# Dump all registered routes and filter for throttle middleware
php artisan route:list --json | python3 -c "
import json, sys
routes = json.load(sys.stdin)
for r in routes:
    if 'throttle' in str(r.get('middleware', '')):
        print(r.get('method'), r.get('uri'), r.get('middleware'))
" | sort
```

Save this output to `reports/throttled-routes-baseline.txt` for reference.

### Step 2: Write threshold-exhaustion tests for each unprotected surface

For each surface identified in Step 1 (and any gaps found), write a test that:
1. Makes `N` requests (at the rate limit threshold)
2. Asserts the `N+1`th request returns `429`
3. Asserts `Retry-After` or `X-RateLimit-Remaining: 0` headers are present

**Pattern to follow (from `AuthRateLimitingTest.php`):**

```php
it('throttles webhook ingestion after N attempts', function (): void {
    $limit = 60; // match the throttle:60,1 definition on the route

    // Exhaust the rate limit
    for ($i = 0; $i < $limit; $i++) {
        $this->postJson(route('webhooks.ingest'), ['payload' => 'data'])
            ->assertStatus(200); // or 422 — whatever the normal response is
    }

    // The next request must be throttled
    $this->postJson(route('webhooks.ingest'), ['payload' => 'data'])
        ->assertStatus(429)
        ->assertHeader('Retry-After');
})->group('boundary');
```

**Suggested new test files:**

| New File | Surface Tested |
|----------|---------------|
| `tests/Feature/Webhooks/WebhookRateLimitingTest.php` | Webhook ingest throttle |
| `tests/Feature/Auth/PasswordResetRateLimitingTest.php` | Password reset + email verification throttle |
| `tests/Feature/Payment/PaymentCallbackRateLimitingTest.php` | Payment gateway callback throttle |
| `tests/Feature/Api/ModuleApiRateLimitingTest.php` | General module API rate limits |

> **Only create tests for routes that actually pass through throttle middleware** (confirmed in Step 1). Do not mock `RateLimiter` — test the middleware stack as it is.

### Step 3: Add a Pest arch rule to guard against future rate-limit regressions

Add to `tests/ArchTest.php`:

```php
arch('all webhook route handlers apply throttle middleware')
    ->expect('App\Http\Controllers')
    ->not->toBeUsedIn('Modules\Webhooks')
    ->ignoring('some-specific-exception-if-needed');
```

> **Note:** A pure arch test cannot verify that a route *has* throttle middleware — that requires a Feature test. Skip this arch rule if it does not genuinely enforce the constraint.

As an alternative, add a route-audit test:

```php
it('all webhook routes have throttle middleware', function (): void {
    $webhookRoutes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route) => str_starts_with($route->uri(), 'webhooks/'));

    foreach ($webhookRoutes as $route) {
        expect($route->getAction('middleware'))
            ->toContain('throttle')
            ->because("webhook route [{$route->uri()}] must be throttled");
    }
});
```

### Step 4: Run the new tests

```bash
php artisan test tests/Feature/Webhooks/WebhookRateLimitingTest.php \
                 tests/Feature/Auth/PasswordResetRateLimitingTest.php \
                 tests/Feature/Payment/PaymentCallbackRateLimitingTest.php \
                 --parallel --processes=4
```

---

## Acceptance Criteria

| Criterion | Check |
|-----------|-------|
| At least 3 new files with `assertStatus(429)` threshold exhaustion assertions | `grep -rl 'assertStatus(429)' tests/Feature --include='*.php'` now returns ≥ 4 files |
| No new test mocks `RateLimiter::shouldRateLimitFor` or similar — tests go through real middleware | Code review |
| All new tests are in the `boundary` group | `->group('boundary')` present in each test |
| All new tests pass in isolation | `php artisan test tests/Feature/<new-file> --no-parallel` |

---

## Files Changed

| File | Change |
|------|--------|
| `tests/Feature/Webhooks/WebhookRateLimitingTest.php` | **New** — throttle boundary tests for webhook ingestion |
| `tests/Feature/Auth/PasswordResetRateLimitingTest.php` | **New** — throttle tests for reset + email verification |
| `tests/Feature/Payment/PaymentCallbackRateLimitingTest.php` | **New** — throttle tests for payment callbacks |
| `reports/throttled-routes-baseline.txt` | **New** (generated) — route list dump showing throttle middleware assignments |

---

## Done When

- [ ] `reports/throttled-routes-baseline.txt` generated and reviewed
- [ ] At least 3 new test files created covering distinct API surfaces
- [ ] Each new file has at least one `assertStatus(429)` threshold exhaustion test
- [ ] All new tests pass in parallel suite
- [ ] Boundary Coverage KPI re-score estimated at 77+/100
