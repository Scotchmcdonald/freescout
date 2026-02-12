# Pest Browser Tests (Playwright)

This directory contains automated browser tests using [Pest Browser](https://pestphp.com/docs/browser-testing) powered by Playwright.

## 🚀 Quick Start

Run tests using `pest` or `artisan test`.

```bash
# Run all browser tests
php artisan test tests/Browser

# Run a specific test file
php artisan test tests/Browser/PIB/EntitlementUXPestTest.php

# Run with filter
php artisan test tests/Browser --filter="access"
```

## 🛠 Configuration

Tests use the `pestphp/pest-plugin-browser` dependency.
- **Engine**: Playwright (Headless Chromium by default)
- **Database**: `RefreshDatabase` trait is active (configured in `tests/Pest.php`).

## 📁 Structure

Tests are located in `tests/Browser` and mirror the application structure or feature set.
Example: `tests/Browser/PIB/EntitlementUXPestTest.php`

## 📝 Writing Tests

Use the `test()` function and the `$this->visit()` API.

```php
test('user can login', function () {
     = User::factory()->create();

    $this->visit('/login')
         ->type('email', $user->email)
         ->type('password', 'password')
         ->click('button[type="submit"]')
         ->assertPathIs('/dashboard');
});
```

> **Note:** Legacy Dusk references (Page Objects, `DuskTestCase`) have been removed.
