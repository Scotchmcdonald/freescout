# Dusk Page Objects

This directory contains Page Object classes for browser tests. The Page Object pattern provides:

1. **Maintainability**: When UI changes, update selectors in ONE place
2. **Readability**: Tests read like user stories
3. **Reusability**: Common actions defined once, used everywhere

## Selector Strategy

We use a priority order for selectors:

1. **`dusk="selector-name"`** - Best. Add these attributes to Blade templates
2. **`name="field_name"`** - Good for form fields
3. **`#id`** - Good when IDs are stable
4. **`.class`** - Avoid when possible (CSS classes change frequently)
5. **XPath** - Last resort

## Adding `dusk` Attributes to Templates

When tests break due to UI changes, add `dusk` attributes:

```blade
<!-- Before -->
<button type="submit" class="btn btn-primary">Save</button>

<!-- After -->
<button type="submit" class="btn btn-primary" dusk="save-button">Save</button>
```

Then update the Page Object:
```php
public function elements(): array
{
    return [
        '@save-button' => '[dusk="save-button"]',
    ];
}
```

## File Structure

```
Pages/
├── BasePage.php              # Common methods for all pages
├── LoginPage.php             # Authentication
├── Crm/
│   ├── ClientListPage.php    # Client list view
│   └── ClientDetailPage.php  # Client 360 view
├── ContractManager/
│   ├── QuoteListPage.php
│   └── QuoteCreatePage.php
├── PIB/
│   └── CreditLedgerPage.php
└── AssetManagement/
    └── AssetInventoryPage.php
```
