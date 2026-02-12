# Remediation Plan: Best Practices Architecture
**Date:** Feb 10, 2026
**Status:** Planned

This document outlines the steps required to resolve the architectural violations identified in the [Architectural Audit Report](ARCHITECTURAL_AUDIT_REPORT.md).

## 1. Core Blindness: App → PIB (Critical)

The `App` namespace (Core) currently has hard dependencies on `Modules\PIB`.

### 1.1 `InvalidateCacheOnInvoicePaid` Listener
**Problem:** `App\Listeners` imports `Modules\PIB\Events\InvoicePaid`.
**Solution:**
- Move file `app/Listeners/InvalidateCacheOnInvoicePaid.php` to `Modules/PIB/Listeners/InvalidateBillingCache.php`.
- Remove registration from `app/Providers/EventServiceProvider.php`.
- Register event/listener in `Modules/PIB/Providers/PIBServiceProvider`.

### 1.2 `TechnicianScope`
**Problem:** Hardcoded class checking `get_class($model) === 'Modules\PIB\Models\Invoice'`.
**Solution:**
- Refactor `TechnicianScope` to be model-agnostic.
- Use interface check `if ($model instanceof \App\Contracts\BelongsToClient)` or generic relationship check `if (method_exists($model, 'client'))`.
- Alternatively, move the scope logic for `Invoice` into the PIB module entirely (e.g., a Global Scope on the Invoice model itself that checks the current user).

### 1.3 `WarmCache` Command
**Problem:** Injects `Modules\PIB\Services\EntitlementService`.
**Solution:**
- Refactor `WarmCache` to fire a `SystemWarmingUp` event.
- Create `Modules\PIB\Listeners\WarmEntitlementCache` listener that responds to the event.
- Remove PIB dependency from the core command.

## 2. Core Blindness: CRM → Payment (Critical)

### 2.1 `Company` Model
**Problem:** `hasMany(PaymentMethod::class)` creates a dependency.
**Solution:**
- Remove `paymentMethods()` method from `Modules\Crm\Models\Company`.
- In `Modules\Payment\Providers\PaymentServiceProvider::boot()`:
  ```php
  \Modules\Crm\Models\Company::resolveRelationUsing('paymentMethods', function ($company) {
      return $company->hasMany(\Modules\Payment\Models\PaymentMethod::class);
  });
  ```

## 3. Queue Isolation (Implementation)

**Problem:** `GenerateInvoiceJob` and others run on default queue unless specified.
**Solution:**
- Update `Modules\PIB\Jobs\*.php`.
- Add `public $queue = 'billing';` property? 
  - *Note:* Laravel's `Dispatchable` trait and `onQueue` usage is preferred, but for forced isolation, we can define the connection/queue in the constructor or class property if the job should *always* run there.
  - Better approach: Ensure the `Dispatchable` call always includes `onQueue('billing')` OR set `public $queue = 'billing'` on the class to make it default.

## 4. Strict Types (Compliance)
- Add `declare(strict_types=1);` to `app/Http/Middleware/TrackPerformanceMetrics.php`.

## 5. Module Utility Isolation
**Problem:** `DevFeedback` uses `KnowledgeBase` seeders.
**Solution:**
- If strictly required, copy the trait to `DevFeedback` or a shared `Testing` namespace.
- For now, we may mark this as "Accepted Technical Debt" in the test if it's only test utilities, OR fix it by duplication (decoupling).
