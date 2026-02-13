# Browser Testing Guide (Pest & Dusk)

**Current Status:** Active
**Stack:** PHP (Pest) + Laravel Dusk (via `pest-plugin-browser`)
**Location:** `tests/Browser/`

## Overview

We currently use **Pest PHP** for all application testing, including browser-based End-to-End (E2E) tests. This allows us to leverage ensuring consistency between our backend logic and our test suites.

### Why this stack?
*   **Direct Database Access:** Tests can use Laravel Models and Factories (`User::factory()->create()`) to seed complex scenarios instantly without needing an external API.
*   **Unified Language:** Developers write tests in PHP, the same language as the application core.
*   **Deep Integration:** Authenticaton, Session, and Cache facades are available directly within the test context.

## Running Tests

To run the full browser test suite:

```bash
php artisan test --group=browser
# OR
./vendor/bin/pest --group=browser
```

### Running Specific Tests

```bash
# Run only the CRM feature tests
./vendor/bin/pest tests/Browser/CrmFeaturePestTest.php
```

## Writing Tests

Tests are located in `tests/Browser`.

**Example:**
```php
use App\Models\User;

test('admin can see dashboard', function () {
    $user = User::factory()->create(['role' => 'admin']);

    $this->browse(function ($browser) use ($user) {
        $browser->loginAs($user)
                ->visit('/dashboard')
                ->assertSee('Overview');
    });
});
```

## Debugging

If a test fails, screenshots are automatically saved to `tests/Browser/screenshots`.

### Common Issues
*   **Timeouts:** If the UI uses heavy JavaScript (Alpine/Livewire), use `->waitFor('.selector')` instead of `->assertSee()`.
*   **Database State:** Tests run with `RefreshDatabase`. Ensure your factories populate all required foreign keys.

---

## Future Roadmap

We are aware of **Native Playwright** (TypeScript) as an alternative. While technically faster, it currently lacks direct database access, which would complicate our data seeding strategy. Only migrate if the "Black Box" testing benefits outweigh the setup costs.

See [MIGRATION_TO_NATIVE_PLAYWRIGHT.md](MIGRATION_TO_NATIVE_PLAYWRIGHT.md) for details.
