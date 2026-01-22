# Dusk Browser Tests - Manual Testing Plan Automation

This directory contains automated browser tests using Laravel Dusk that mirror the manual testing plan.

## Quick Start

```bash
# Run all tests
php artisan dusk

# Run only the manual testing plan tests
php artisan dusk tests/Browser/ManualTestingPlanTest.php

# Run specific test section
php artisan dusk --filter=test_section1
php artisan dusk --filter=test_section4_contract_manager
php artisan dusk --filter=test_section5

# Run with visible browser (for debugging)
php artisan dusk --browse

# Run smoke tests first
php artisan dusk --filter=smoke
```

## File Structure

```
tests/Browser/
├── README.md                      # This file
├── ExampleTest.php                # Default Laravel example
├── ManualTestingPlanTest.php      # Main test suite (matches manual plan)
│
├── Pages/                         # Page Objects (UI abstraction layer)
│   ├── README.md                  # Page Object documentation
│   ├── Page.php                   # Base page with common elements
│   ├── LoginPage.php              # Authentication
│   │
│   ├── Crm/
│   │   └── Client360Page.php      # Client detail/360 view
│   │
│   ├── ContractManager/
│   │   ├── QuoteListPage.php      # Quote listing
│   │   ├── QuoteCreatePage.php    # Quote creation form
│   │   └── QuoteDetailPage.php    # Quote detail & actions
│   │
│   ├── PIB/
│   │   └── CreditLedgerPage.php   # Credit ledger view
│   │
│   ├── AssetManagement/
│   │   └── AssetInventoryPage.php # Asset inventory list
│   │
│   └── SoftwareSubscriptions/
│       └── SoftwareSubscriptionPages.php  # Subscription management
│
└── Traits/
    └── CreatesTestData.php        # Test data factory helpers
```

## When Tests Fail

### Step 1: Take a Screenshot

Tests automatically save screenshots on failure to `tests/Browser/screenshots/`.

### Step 2: Check Page Objects

If UI changed, update the relevant Page Object in `tests/Browser/Pages/`:

```php
// Before (old selector)
'@save-button' => 'button.btn-primary',

// After (new selector - add dusk attribute to template)
'@save-button' => '[dusk="save-button"]',
```

### Step 3: Add `dusk` Attributes to Templates

For stability, add `dusk` attributes to Blade templates:

```blade
<!-- In your .blade.php file -->
<button type="submit" dusk="save-button" class="btn btn-primary">
    Save
</button>
```

Then update the Page Object to use it:

```php
'@save-button' => '[dusk="save-button"]',
```

### Step 4: Re-run the Specific Test

```bash
php artisan dusk --filter=test_section4_1_create_quote
```

## Test Groups

Tests are tagged with groups for selective running:

| Group | Description | Command |
|-------|-------------|---------|
| `smoke` | Basic app health checks | `php artisan dusk --group=smoke` |
| `crm` | CRM module tests | `php artisan dusk --group=crm` |
| `assets` | Asset management tests | `php artisan dusk --group=assets` |
| `contracts` | ContractManager tests | `php artisan dusk --group=contracts` |
| `billing` | PIB billing tests | `php artisan dusk --group=billing` |
| `integration` | Cross-module tests | `php artisan dusk --group=integration` |
| `section1` - `section7` | By manual plan section | `php artisan dusk --group=section4` |

## Configuration

### Environment File

Create `.env.dusk.local` for Dusk-specific settings:

```env
APP_URL=http://localhost:8000
DB_DATABASE=testing
DUSK_DRIVER_URL=http://localhost:9515
```

### Headless Mode

By default, tests run headless. To see the browser:

```bash
# Disable headless
DUSK_HEADLESS_DISABLED=true php artisan dusk

# Or use --browse flag
php artisan dusk --browse
```

## Selector Priority Guide

When choosing selectors, prefer (in order):

1. **`[dusk="name"]`** - Best, add to templates
2. **`input[name="field"]`** - Good for forms
3. **`#id`** - OK if IDs are stable
4. **`.class`** - Avoid (CSS changes often)
5. **XPath** - Last resort

## Test Data Strategy

- Tests use prefix `DUSK-` for created data
- Unique IDs generated per test run
- Data is NOT automatically cleaned up (for debugging)
- Use `RefreshDatabase` trait for full isolation

## Mapping to Manual Test Plan

| Manual Section | Test Method | Page Objects Used |
|----------------|-------------|-------------------|
| 1.1 Create Client | `test_section1_1_create_client` | (direct navigation) |
| 1.2-1.3 Add Contacts | `test_section1_2_add_contacts_to_client` | `Client360Page` |
| 1.4 Client 360 View | `test_section1_4_client_360_view` | `Client360Page` |
| 2.1 Create Windows Asset | `test_section2_1_create_windows_asset` | `AssetInventoryPage` |
| 2.2 Create Chromebook | `test_section2_2_create_chromebook_asset` | `AssetInventoryPage` |
| 4.1 Create Quote | `test_section4_1_create_quote` | `QuoteCreatePage` |
| 4.2 Edit Quote | `test_section4_2_edit_quote` | `QuoteDetailPage` |
| 4.3 Approve Quote | `test_section4_3_approve_quote` | `QuoteDetailPage` |
| 5.3 Add Credit | `test_section5_3_add_client_credit` | `CreditLedgerPage` |
| 5.4 Deduct Credit | `test_section5_4_deduct_credit` | `CreditLedgerPage` |
| 7.3 Widget Registry | `test_section7_3_widget_registry_integration` | `Client360Page` |

## Troubleshooting

### ChromeDriver Issues

```bash
# Update ChromeDriver to match Chrome version
php artisan dusk:chrome-driver --detect

# Or specify version
php artisan dusk:chrome-driver 120
```

### Element Not Found

1. Check if page fully loaded (`->pause(500)`)
2. Verify selector in browser DevTools
3. Check if element is in a modal/iframe
4. Add explicit wait: `->waitFor('@element', 10)`

### Tests Pass Locally, Fail in CI

- Check `APP_URL` in `.env.dusk.local`
- Ensure database is seeded
- Check for timing issues (add `->pause()`)
- Use screenshots to debug

### Selector Cheat Sheet

```php
// By dusk attribute (best)
'[dusk="my-element"]'

// By name attribute (forms)
'input[name="email"]'
'select[name="client_id"]'

// By ID
'#my-element'

// By text content (fragile)
'button:contains("Save")'

// Combining selectors (fallbacks)
'[dusk="save"], button[type="submit"], .btn-primary'

// Within a container
'@container @child-element'
```

## Adding New Tests

1. **Identify the user flow** from manual testing plan
2. **Create/update Page Object** if needed
3. **Write test** using Page Object methods
4. **Add `dusk` attributes** to templates for stability
5. **Tag with appropriate groups**
6. **Document** in this README's mapping table
