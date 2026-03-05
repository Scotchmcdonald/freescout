# Module Development Guide

**Version:** 2.2
**Last Updated:** February 13, 2026

This guide defines the architecture, patterns, and best practices for developing and maintaining modular components in the application (Laravel 12 / PHP 8.2+).

---

## Table of Contents

1. [Module Definition](#1-module-definition)
2. [Cross-Module Interactions](#2-cross-module-interactions)
3. [Controller Organization](#3-controller-organization)
4. [The ExtensibleModel Pattern](#4-the-extensiblemodel-pattern)
5. [Dynamic Relationships](#5-dynamic-relationships)
6. [Migration Best Practices](#6-migration-best-practices)
7. [Testing & Compliance](#7-testing--compliance)
8. [Form Validation Extensions](#8-form-validation-extensions)
9. [Quick Reference](#9-quick-reference)
10. [Troubleshooting](#10-troubleshooting)

---

## 1. Module Definition

### 1.1 The `module.json` File

The `module.json` file is the source of truth for your module's identity, dependencies, and capabilities.

**Required Fields:**
```json
{
    "name": "ModuleName",
    "alias": "modulename",
    "description": "A clear description of what this module does",
    "keywords": ["billing", "payments", "invoicing"],
    "priority": 0,
    "providers": [
        "Modules\\ModuleName\\Providers\\ModuleNameServiceProvider"
    ],
    "requires": ["Crm"],
    "permissions": {
        "view_invoices": "View Invoices",
        "manage_settings": "Manage Module Settings"
    }
}
```

### 1.2 Dependencies

Modules **must** explicitly declare their hard dependencies to ensure correct loading order and prevent runtime crashes.

```json
"requires": [
    "Crm",
    "AssetManagement"
]
```

**Best Practice:** Always verify that required modules exist before using their classes:

```php
if (class_exists(\Modules\OtherModule\Models\Target::class)) {
    // Safe to use
}
```

### 1.3 Permission Registration

The application uses dynamic RBAC. Modules define granular permissions in `module.json`. The system automatically generates a master `access_{module_alias}` permission plus any specific ones listed.

```json
"permissions": {
    "view_invoices": "View Invoices",
    "manage_payment_gateways": "Manage Payment Gateways"
}
```

The UI automatically renders these as toggleable options in the permissions interface.

---

## 2. Cross-Module Interactions

### 2.1 Core Principle: "Core Blindness"

**Golden Rule:** Core application modules (like CRM) **must never** have hard dependencies on Feature modules (like Payment, AssetManagement).

❌ **Bad:**
```php
// In Modules\Crm\Models\Client.php
use Modules\Payment\Models\Invoice;

public function invoices() {
    return $this->hasMany(Invoice::class);
}
```

✅ **Good:**
```php
// In Modules\Payment\Providers\PaymentServiceProvider.php
if (class_exists(\Modules\Crm\Models\Client::class)) {
    \Modules\Crm\Models\Client::resolveRelationUsing('invoices', function ($client) {
        return $client->hasMany(\Modules\Payment\Models\Invoice::class);
    });
}
```

### 2.2 Defensive Coding

When a Feature module depends on another Feature module, always guard against missing dependencies:

```php
// In ServiceProvider boot()
if (class_exists(\Modules\OtherModule\Models\Target::class)) {
    // Safe to integrate
} else {
    Log::warning('OtherModule not available, skipping integration');
}
```

---

## 3. Controller Organization

### 3.1 Controller Placement Rules

**Principle:** Controllers should live in the module they primarily serve.

**Module-Specific Controllers** → `Modules/{ModuleName}/Http/Controllers/`
- Controllers that manage a specific module's resources
- Example: `Modules/PIB/Http/Controllers/BillingController.php` (manages billing templates, invoices)
- Example: `Modules/AssetManagement/Http/Controllers/AssetController.php` (manages assets, conflicts)

**Cross-Module Aggregators** → `app/Http/Controllers/Admin/`
- Controllers that aggregate data from multiple modules for admin views
- Example: `app/Http/Controllers/Admin/Client360Controller.php` (shows client data from CRM, PIB, AssetManagement)
- Must use dynamic class checking (see below)

### 3.2 Admin Aggregator Pattern

**Problem:** Admin interfaces often need to display data from multiple modules in a single view.

❌ **Bad: Hard Dependencies (Core Blindness Violation)**
```php
namespace App\Http\Controllers\Admin;

use Modules\PIB\Models\Invoice;  // ❌ Core depends on feature module
use Modules\AssetManagement\Entities\Asset;  // ❌ Core depends on feature module

class Client360Controller extends Controller {
    public function show($id) {
        $invoices = Invoice::where('client_id', $id)->get();  // ❌ Breaks if PIB disabled
        $assets = Asset::where('client_id', $id)->get();  // ❌ Breaks if AssetMgmt disabled
        return view('admin.clients.show', compact('invoices', 'assets'));
    }
}
```

✅ **Good: Dynamic Class Checking with Graceful Degradation**
```php
namespace App\Http\Controllers\Admin;

use Modules\Crm\Models\Client;  // ✅ CRM is core module

class Client360Controller extends Controller {
    public function show($id) {
        $client = Client::findOrFail($id);
        
        // Initialize empty collections
        $invoices = collect();
        $assets = collect();
        
        // Dynamic loading: PIB module (if installed)
        if (class_exists('\\Modules\\PIB\\Models\\Invoice')) {
            $invoiceClass = '\\Modules\\PIB\\Models\\Invoice';
            $invoices = $invoiceClass::where('client_id', $id)->get();
        }
        
        // Dynamic loading: AssetManagement module (if installed)
        if (class_exists('\\Modules\\AssetManagement\\Entities\\Asset')) {
            $assetClass = '\\Modules\\AssetManagement\\Entities\\Asset';
            $assets = $assetClass::where('client_id', $id)->get();
        }
        
        return view('admin.clients.show', compact('client', 'invoices', 'assets'));
    }
}
```

**Benefits:**
- ✅ Modules can be enabled/disabled independently
- ✅ Views gracefully degrade (show empty state when module missing)
- ✅ No runtime errors from missing dependencies
- ✅ Zero core blindness violations

### 3.3 Cross-Module Service Access Pattern

**Use Case:** Module needs to access another module's service (e.g., ClientPortal accessing PIB's credit service)

✅ **Pattern: Dynamic Service Resolution**
```php
namespace Modules\ClientPortal\Http\Controllers;

class PortalController extends Controller {
    protected function getClientSummary(Client $client): array {
        // Get credit balance from PIB module if available
        $creditBalance = 0.0;
        if (class_exists('\\Modules\\PIB\\Services\\ClientCreditService')) {
            try {
                $creditService = app(\Modules\PIB\Services\ClientCreditService::class);
                $creditBalance = $creditService->getBalance($client->id);
            } catch (\Exception $e) {
                // PIB module not installed or client has no credit record
                $creditBalance = 0.0;
            }
        }
        
        return [
            'name' => $client->name,
            'credit_balance' => $creditBalance,
        ];
    }
}
```

**Key Points:**
- Always wrap in `class_exists()` check
- Use try-catch for service calls (module might be disabled mid-request)
- Provide sensible defaults for missing data
- Never import the service class directly

---

## 4. The ExtensibleModel Pattern

### 3.1 The Problem

In a modular application, we face a dilemma:
- **Core Module (e.g., CRM)** owns a central concept like `Company`
- **Feature Modules (e.g., Payment)** need to store additional data on that entity (e.g., `account_balance`, `billing_mode`)

We want to avoid:
- **Polluting Core:** The CRM module shouldn't know about `billing_mode`
- **Silent Failures:** `Company::create(['billing_mode' => 'auto'])` discards unknown attributes
- **Overengineering:** `PaymentCompany extends Company` creates development friction

### 3.2 The Solution: Trait-Based Extension

We use the `App\Traits\ExtensibleModel` trait to dynamically register fields from external modules at runtime.

### 3.3 Components

**1. Base Model (`Modules\Crm\Models\Company`)**
```php
class Company extends Model {
    use ExtensibleModel;
    
    protected $fillable = ['name', 'email']; // Core fields only
}
```

**2. Extending Module Provider**
```php
// Modules/Payment/Providers/PaymentServiceProvider.php
public function boot()
{
    if (class_exists(\Modules\Crm\Models\Company::class)) {
        \Modules\Crm\Models\Company::addGlobalFillables([
            'billing_mode',
            'account_balance'
        ]);
        
        \Modules\Crm\Models\Company::addGlobalCasts([
            'account_balance' => 'decimal:2'
        ]);
    }
}
```

**3. Usage**
```php
// Works everywhere, regardless of which module wrote the code
Company::create([
    'name' => 'Acme Inc',
    'billing_mode' => 'auto' // Handled correctly if Payment is active
]);
```

### 3.4 How It Works

The `ExtensibleModel` trait:
- Maintains a static `$externalFillables` registry
- Merges external fields into model instances during initialization
- Allows modules to extend core models without modifying them

### 3.5 Best Practices

1. **Migration Safety:** Check before adding columns
   ```php
   if (!Schema::hasColumn('companies', 'billing_mode')) {
       $table->string('billing_mode')->nullable();
   }
   ```

2. **Dependency Checks:** Always wrap registration in existence checks
   ```php
   if (class_exists(\Modules\Crm\Models\Company::class)) {
       // Safe to extend
   }
   ```

3. **Naming Conflicts:** Use module-specific prefixes
   - ❌ `status` (generic, conflicts likely)
   - ✅ `payment_status` (specific, safe)

4. **IDE Support:** Add `@property` tags for autocomplete
   ```php
   /**
    * @property string $billing_mode
    * @property float $account_balance
    */
   class Company extends Model { ... }
   ```

---

## 5. Dynamic Relationships

Do not edit Core models to add relationships to your module's tables. Use Laravel's `resolveRelationUsing` method in your ServiceProvider.

### Example

```php
// In Modules\AssetManagement\Providers\AssetManagementServiceProvider.php
public function boot()
{
    if (class_exists(\Modules\Crm\Models\Company::class)) {
        \Modules\Crm\Models\Company::resolveRelationUsing('assets', function ($companyModel) {
            return $companyModel->hasMany(\Modules\AssetManagement\Models\Asset::class);
        });
    }
}
```

Now `$company->assets` works seamlessly, but CRM module remains unaware of the AssetManagement module.

---

## 6. Migration Best Practices

Migrations in modules must be re-runnable and crash-resistant.

### 6.1 Table Naming Convention

Use a consistent prefix strategy based on table purpose:

**Module-Prefixed Tables (Domain Objects)**

Tables that represent module-specific domain entities use the module prefix:

```php
Schema::create('pib_invoices', ...);      // PIB's core domain
Schema::create('cm_quotes', ...);          // ContractManager's core domain
Schema::create('am_assets', ...);          // AssetManagement's core domain
```

**Unprefixed Tables (Shared Infrastructure)**

Tables that act as integration points or shared primitives remain unprefixed:

```php
Schema::create('client_credits', ...);     // Financial primitive
Schema::create('service_usage', ...);      // Cross-module billable activity
Schema::create('client_user_counters', ...); // Billing calculation input
```

**Guideline:** If another module might reasonably query this table, consider leaving it unprefixed.

> 📖 See [ADR-002: PIB Table Naming Convention](adr/ADR-002-pib-table-naming-convention.md) for detailed rationale.

### 6.2 Monetary Storage

Use a hybrid approach based on operation type:

**Integer Cents (for atomic operations)**

```php
$table->integer('balance_cents')->default(0);  // ✅ Atomic increment/decrement safe
```

Use integer cents when:
- Values are subject to concurrent modification
- You need `UPDATE ... SET col = col + ?` atomic operations
- Column name should include `_cents` suffix

**Decimal Dollars (for display/audit values)**

```php
$table->decimal('total_amount', 10, 2);  // ✅ Human-readable, no concurrent writes
```

Use decimal when:
- Values are calculated once and stored (e.g., invoice totals)
- Primary use is display or reporting
- Records are immutable after creation (audit trails)

> 📖 See [ADR-001: Monetary Storage Strategy](adr/ADR-001-monetary-storage-strategy.md) for detailed rationale.

### 6.3 Column Checks
```php
if (!Schema::hasColumn('companies', 'billing_mode')) {
    Schema::table('companies', function (Blueprint $table) {
        $table->string('billing_mode')->nullable();
    });
}
```

### Table Checks
```php
if (Schema::hasTable('companies')) {
    Schema::table('companies', function (Blueprint $table) {
        // Safe modifications
    });
}
```

### Foreign Key Standards
Use `unsignedBigInteger` for foreign keys to match Laravel 12 defaults:

```php
$table->foreignId('company_id')->constrained()->cascadeOnDelete();
// Or
$table->unsignedBigInteger('company_id');
$table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
```

---

## 7. Testing & Compliance

### 6.1 Unit Tests

Tests should treat dependencies as swappable interfaces where possible.

```php
public function test_can_create_company_with_payment_data()
{
    if (!class_exists(\Modules\Payment\Models\Invoice::class)) {
        $this->markTestSkipped('Payment module not available');
    }
    
    // Test logic
}
```

### 6.2 Module Toggle Tests

Ensure your module degrades gracefully if a dependency is missing:

```php
// In ServiceProvider boot()
if (!Module::find('Crm')?->isActive()) {
    Log::error('Payment module requires CRM module to be active');
    return;
}
```

### 6.3 Validation

Validation logic lives in FormRequests. Extending modules should:

1. Create their own validation rules
2. Merge rules via Events or custom validation
3. Never modify core FormRequests

```php
// In Module ServiceProvider
Event::listen('validation.company.creating', function ($validator) {
    $validator->addRules([
        'billing_mode' => 'required|in:auto,manual',
    ]);
});
```

---

## 9. Quick Reference

### ✅ Do
- Declare all module dependencies in `module.json`
- Use `ExtensibleModel` to add fields to core models
- Use `resolveRelationUsing` for dynamic relationships
- Check for class/table existence before extending
- Write crash-resistant migrations
- Test module toggle scenarios
- Place module-specific controllers in module directories
- Use dynamic class checking for cross-module data access

### ❌ Don't
- Import Feature module classes in Core modules
- Modify Core model classes directly
- Assume dependencies are always available
- Use generic column names without prefixes
- Skip migration safety checks
- Hard-code references to optional modules
- Put module-specific controllers in core app/ directory
- Use direct imports in admin aggregator controllers

---

## 9. UI Implementation Standards

Every enabled module MUST have at least one UI view (Minimum UI Standard), even if it's just an audit/admin view to inspect data.

### 9.1 Minimum UI Requirements
1.  **Controller**: `Modules/{ModuleName}/Http/Controllers/{ModuleName}Controller.php`
2.  **Route**: `/modules/{module_name}` (standardized in `routes/web.php` or module routes)
3.  **View**: `Modules/{ModuleName}/Resources/views/index.blade.php`
4.  **Navigation**: Registered in `NavigationService`

### 9.2 View Standards
- Use `layouts.app`
- Implement standard "Pilot's Cockpit" headers
- Use `x-card` components for data display
- Follow [UX_STYLE_GUIDE.md](../UX_STYLE_GUIDE.md)

---

## 10. Troubleshooting

**Problem:** "Class not found" errors  
**Solution:** Add dependency checks in ServiceProvider boot()

**Problem:** Fillable attributes silently ignored  
**Solution:** Ensure ExtensibleModel trait is used and fields are registered

**Problem:** Relationships not working  
**Solution:** Verify resolveRelationUsing is called in boot(), not register()

**Problem:** Migration failures in production  
**Solution:** Add Schema::hasColumn/hasTable checks

**Problem:** Module won't load after dependency disabled  
**Solution:** Add module existence checks using Module::find()

---

## Additional Resources

- **[ARCHITECTURE_OVERVIEW.md](ARCHITECTURE_OVERVIEW.md)** - Current system architecture (recommended starting point)
- **[SYSTEM_ARCHITECTURE.md](SYSTEM_ARCHITECTURE.md)** - Complete design specification
- [Module Installer Documentation](../MODULE_INSTALLER_README.md)
- [UX Style Guide](../UX_STYLE_GUIDE.md)
- [Laravel Module Package Documentation](https://nwidart.com/laravel-modules)

---

## Appendix A: Module Modernization Guide (Legacy Upgrade)

This appendix outlines the steps to modernize an older FreeScout module for compatibility with the Laravel 12 Foundation.

### A.1 Namespace & Model References
Old modules often reference models in the root `App\` namespace. These must be updated to `App\Models\`.

**Search & Replace:**
- `use App\Conversation;` -> `use App\Models\Conversation;`
- `use App\Customer;` -> `use App\Models\Customer;`
- `use App\User;` -> `use App\Models\User;`
- `use App\Mailbox;` -> `use App\Models\Mailbox;`
- `use App\Thread;` -> `use App\Models\Thread;`
- `use App\Attachment;` -> `use App\Models\Attachment;`

### A.2 Route Definitions
Update `Http/routes.php` to use the modern tuple syntax `[Controller::class, 'method']` instead of string-based routing.

**Example:**
```php
// Before
Route::group(['namespace' => 'Modules\Foo\Http\Controllers'], function() {
    Route::get('/foo', 'FooController@index');
});

// After
use Modules\Foo\Http\Controllers\FooController;

Route::group([], function() {
    Route::get('/foo', [FooController::class, 'index']);
});
```

### A.3 Database Migrations
Ensure migrations use `bigIncrements` (or `id()`) and `unsignedBigInteger` for foreign keys to match Laravel 12 defaults.

**Changes:**
- `$table->increments('id');` -> `$table->id();`
- `$table->integer('user_id')->unsigned();` -> `$table->unsignedBigInteger('user_id');`

### A.4 Controller Inheritance
Controllers should extend `App\Http\Controllers\Controller` to inherit middleware and shared logic.

### A.5 Service Providers
- Remove calls to `registerFactories()` if using the legacy factory system.
- Ensure `loadMigrationsFrom` points to the correct directory.

### A.6 Validation
- Ensure `Validator` is imported via `use Illuminate\Support\Facades\Validator;`.

### A.7 PHP 8.2+ Type Safety
The platform requires PHP 8.2+ and enforces strict type safety.
- Add types to properties: `public string $name;`
- Add return types to methods: `public function index(): View`
- Use `readonly` classes for DTOs where appropriate.
