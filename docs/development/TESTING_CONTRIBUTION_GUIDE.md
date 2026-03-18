# Testing Contribution Guide

*Last updated: 2026-03-18 — tracked by CI.*

## Decision Tree: Which Test Class to Use

```
Is the behavior you are testing…

── purely about logic/computation with no DB or HTTP?
   └── UnitTestCase (tests/UnitTestCase.php)
       Modules/*/Tests/Unit/
       NO factories create(); NO RefreshDatabase; use make() or value objects.

── about persistence, model events, or Eloquent relationships?
   └── IntegrationTestCase (tests/IntegrationTestCase.php)
       Modules/*/Tests/Integration/
       RefreshDatabase is acceptable. Scope to one module.
       Do NOT call other modules' real services directly; use fakes or contracts.

── about HTTP routes, middleware, or the full request/response cycle?
   └── TestCase (tests/TestCase.php) with RefreshDatabase
       tests/Feature/ or Modules/*/Tests/Feature/
       Assert response codes + business outcomes, not view copy.
       Keep assertSee() only for copy that is a product requirement.

── about user-visible, multi-step browser workflows (a real risk)?
   └── DuskTestCase in tests/Browser/
       Use sparingly. One file per critical UX flow.
       Must justify why this cannot be covered as a feature test.
```

## Required Patterns (Templates)

### Unit Test (Service with Pure Logic)

```php
<?php

declare(strict_types=1);

namespace Modules\YourModule\Tests\Unit;

use Modules\YourModule\Services\YourService;
use Tests\UnitTestCase;

it('describes the specific invariant being tested', function () {
    $service = new YourService();

    $result = $service->calculate(/* inputs */);

    expect($result)->toBe(/* expected output */);
});

it('throws when given invalid input', function () {
    $service = new YourService();

    expect(fn () => $service->calculate(-1))->toThrow(\InvalidArgumentException::class);
});
```

### Integration Test (Service with DB side-effects)

```php
<?php

declare(strict_types=1);

namespace Modules\YourModule\Tests\Integration;

use Modules\YourModule\Models\YourModel;
use Modules\YourModule\Services\YourService;
use Tests\IntegrationTestCase;

it('persists the expected state after the operation', function () {
    $model = YourModel::factory()->create(['status' => 'draft']);

    app(YourService::class)->process($model);

    expect($model->fresh()->status)->toBe('processed');
});
```

### External API Call (Boundary Contract)

```php
<?php

declare(strict_types=1);

it('handles gateway failure gracefully', function () {
    Http::fake([
        'api.external.com/*' => Http::response(['error' => 'rate_limit'], 429),
    ]);
    Http::preventStrayRequests();

    expect(fn () => app(ExternalService::class)->call())
        ->toThrow(\App\Exceptions\GatewayException::class);
});
```

## Forbidden Patterns (Do Not Write These)

### ❌ Framework Wiring Test — Verifies Laravel, Not Your Code

```php
// BAD: This tests that BelongsTo exists in Laravel, not your model.
it('client belongs to company', function () {
    expect(new Client())
        ->client()
        ->toBeInstanceOf(BelongsTo::class); // FORBIDDEN
});
```

### ❌ makePartial on the Service Under Test

```php
// BAD: You are mocking the object you are testing.
// The internal method can do anything — you get no real coverage.
$service = Mockery::mock(QuoteService::class)->makePartial();
$service->shouldReceive('createQuote')->andReturn($fakeQuote);
// The real createQuote logic is never exercised.
```

**Correct pattern:** use a real service with a faked repository/Http.

### ❌ Hollow Assertion

```php
// BAD: This test always passes regardless of what the service does.
it('creates a quote', function () {
    $service = app(QuoteService::class);
    $quote = $service->createQuote($client, $data);
    expect($quote)->not->toBeNull(); // Every object will pass this
});
```

**Correct pattern:** assert the specific state produced: `$quote->status`, `$quote->line_items->count()`.

### ❌ UI Copy as Business Rule Proxy

```php
// BAD: Brittle to any phrasing change, tests no behavior.
$response->assertSee('Your invoice has been generated successfully.');
```

**Correct pattern:**
```php
$response->assertStatus(201);
expect(Invoice::latest()->first()->status)->toBe('published');
```

### ❌ RefreshDatabase in a Unit Test Folder

```php
// BAD: This makes your "unit" test 10x slower and couples it to the DB.
namespace Modules\YourModule\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;

class YourServiceTest extends UnitTestCase
{
    use RefreshDatabase; // FORBIDDEN in Tests/Unit/ — move to Tests/Integration/
}
```

## Anti-Pattern Detection Commands

Run before submitting a PR:

```bash
# Count your assertSee additions (target: 0 new per PR)
git diff --unified=0 | grep '^\+.*assertSee\b' | wc -l

# Count new RefreshDatabase in unit folders (target: 0)
git diff --unified=0 | grep -E '^\+.*RefreshDatabase' -- 'Modules/*/Tests/Unit/*.php' | wc -l

# Check for makePartial on live services (target: 0)
git diff --unified=0 | grep '^\+.*makePartial()' | wc -l
```

## Module Test Folder Structure (Required)

Every module MUST have:

```
Modules/YourModule/Tests/
├── Unit/           ← service logic, pure computation, no DB
├── Integration/    ← persistence, events, DB-backed behavior
└── Feature/        ← HTTP routes, forms, middleware
```

Browser tests may be added only in `Modules/YourModule/Tests/Browser/` with written
justification in the test file's docblock.
