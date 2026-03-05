# Browser Testing Guide (Pest & Playwright)

**Current Status:** Active
**Stack:** PHP (Pest) + Playwright (via `pest-plugin-browser`)
**Location:** `tests/Browser/`

## Overview

We currently use **Pest PHP** for all application testing, including browser-based End-to-End (E2E) tests powered by **Playwright**. The `pest-plugin-browser` package provides a PHP-friendly API for Playwright, allowing us to write browser tests in PHP while leveraging Playwright's robust browser automation.

### Why this stack?
*   **Direct Database Access:** Tests can use Laravel Models and Factories (`User::factory()->create()`) to seed complex scenarios instantly without needing an external API.
*   **Unified Language:** Developers write tests in PHP, the same language as the application core.
*   **Deep Integration:** Authentication, Session, and Cache facades are available directly within the test context.
*   **Playwright Power:** Real browser automation with modern web testing capabilities (headless/headed Chrome, network interception, etc.)

## Running Tests

To run the full browser test suite:

```bash
php artisan test tests/Browser
# OR with tee to save output
php artisan test tests/Browser | tee reports/browserTests.txt
```

### Running Specific Tests

```bash
# Run specific test file
php artisan test tests/Browser/CrmFeaturePestTest.php

# Run tests with specific group
php artisan test --group=smoke
```

## Writing Tests

Tests are located in `tests/Browser`. The `pest-plugin-browser` provides a familiar API that's similar to Laravel Dusk but uses Playwright as the browser driver.

**Example:**
```php
use App\Models\User;

test('admin can see dashboard', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]')
        ->visit('/dashboard')
        ->assertSee('Overview');
});
```

### Common Methods

- `visit($url)` - Navigate to a URL
- `type($selector, $value)` - Type into an input field
- `click($selector)` - Click an element
- `assertSee($text)` - Assert text is visible on page
- `waitForText($text)` - Wait for text to appear
- `waitFor($selector)` - Wait for an element to exist

## Debugging

If a test fails, screenshots are automatically saved to `tests/Browser/screenshots`.

### Common Issues
*   **Timeouts:** If the UI uses heavy JavaScript (Alpine/Livewire), use `->waitFor('.selector')` or `->waitForText('text')` instead of immediately calling `->assertSee()`.
*   **Database State:** Tests run with `RefreshDatabase`. Ensure your factories populate all required foreign keys.
*   **Selector Issues:** Use `[dusk="selector-name"]` attributes for stable test selectors that won't change with UI updates.

## Technical Details

### Playwright Configuration

Playwright is configured via `playwright.config.ts` in the project root. The `pest-plugin-browser` uses this configuration automatically.

### Test Execution

Browser tests run through Pest's test runner and use Playwright's Node.js process under the hood. The `pest-plugin-browser` bridges between PHP and Playwright's browser automation.
