# Module Connectivity Debrief
**Created:** January 16, 2026  
**Audience:** Technical Leadership, Architecture Review Board  
**Source Documents:** ARCHITECTURE_OVERVIEW.md, SYSTEM_ARCHITECTURE.md

---

## Executive Summary

This debrief provides a high-level view of how the platform's modules interact. The system follows an **event-driven, modular architecture** with strict separation of concerns enforced through the **Core Blindness Pattern**—where feature modules extend core modules but never the reverse.

**Key Finding:** The architecture successfully achieves loose coupling through Laravel Events, dynamic relationship registration, and graceful degradation patterns that allow modules to be independently enabled/disabled.

---

## Module Ecosystem at a Glance

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                              MODULE HIERARCHY                               │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│   ┌─────────────────────────────────────────────────────────────────────┐   │
│   │                         CRM (CORE MODULE)                           │   │
│   │   • Companies  • Clients  • Contacts  • Custom Fields               │   │
│   │   • No external module dependencies                                 │   │
│   └───────────────────────────────▲─────────────────────────────────────┘   │
│                                   │                                         │
│           ┌───────────────────────┼───────────────────────┐                 │
│           │                       │                       │                 │
│   ┌───────┴───────┐       ┌───────┴───────┐       ┌─────────┴─────────┐     │
│   │  GoogleAdmin  │       │    Action1    │       │  ContractManager  │     │
│   │ (Integration) │       │ (Integration) │       │ (Quotes/Contracts │     │
│   └───────┬───────┘       └───────┬───────┘       │  /BillingTemplates)│    │
│           │                       │               └─────────┬─────────┘     │
│           └───────────┬───────────┘                         │               │
│                       │                                     │               │
│               ┌───────▼───────────┐                         │               │
│               │  AssetManagement  │                         │               │
│               │  (Device Tracking)│                         │               │
│               └───────┬───────────┘                         │               │
│                       │                                     │               │
│                       │           ┌─────────────────────────┘               │
│                       │           │                                         │
│                       │   ┌───────▼───────┐                                 │
│                       └──▶│      PIB      │  Billing EXECUTION engine.      │
│                           │  (Invoices,   │  Reads BillingTemplates from    │
│                           │   Credits)    │  ContractManager, generates     │
│                           └───┬───────┬───┘  invoices.                      │
│                               │       │                                     │
│                       ┌───────▼───┐   │                                     │
│                       │  Payment  │   │                                     │
│                       │  (Helcim) │   │                                     │
│                       └─────┬─────┘   │                                     │
│                             │         │                                     │
│                             ▼         ▼                                     │
│                  ┌──────────────────────────────────────────────────────┐   │
│                  │                   ClientPortal                       │   │
│                  │                   (Aggregator)                       │   │
│                  │  Pulls from: ContractManager, PIB, Payment, Assets   │   │
│                  └──────────────────────────────────────────────────────┘   │
│                                                                             │
│   ┌─────────────────────────────────────────────────────────────────────┐   │
│   │                    Alerts (Cross-Cutting Layer)                     │   │
│   │   Notification routing - Listens to all *Unusual, *Failed events    │   │
│   └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## Module Connectivity Matrix

| Module | Publishes Events | Listens To | Data Provides | Data Consumes |
|--------|-----------------|------------|---------------|---------------|
| **CRM** | ClientCreated, ContactUpdated | — | Clients, Contacts, Companies | — |
| **GoogleAdmin** | GoogleUserSynced, GoogleChromebookDiscovered | — | User data, Device data | CRM (Clients) |
| **Action1** | Action1DeviceDiscovered, Action1DeviceUpdated | — | Device data | CRM (Clients) |
| **AssetManagement** | AssetStatusChanged, AssetCountChanged | GoogleChromebookDiscovered, Action1DeviceDiscovered | Assets, Entitlements | CRM, GoogleAdmin, Action1 |
| **ContractManager** | QuoteApproved, ContractActivated, BillingTemplateDue | — | Quotes, Contracts, BillingTemplates | CRM |
| **PIB** | InvoiceGenerated, InvoicePublished | BillingTemplateDue, AssetCountChanged, ContractRevised | Invoices, Credits, EntitlementSnapshots | CRM, ContractManager, AssetManagement |
| **Payment** | PaymentSucceeded, PaymentFailed | InvoicePublished | Transactions | CRM, PIB |
| **ClientPortal** | ClientApprovedQuote, ClientDisputedInvoice | InvoicePublished, QuoteCreated, AssetStatusChanged, PaymentSucceeded | — | ContractManager, PIB, Payment, AssetManagement (read-only) |
| **Alerts** | — | All *Unusual, *Failed, *Overdue events | — | Event metadata |

---

## Key Data Flows

### 1. Asset Discovery Flow (External → Internal)

```
┌──────────────┐     ┌──────────────┐     ┌───────────────────┐     ┌─────────┐
│   External   │───▶│  GoogleAdmin │────▶│  AssetManagement  │───▶│   PIB   │
│  APIs (RMM)  │     │   Action1    │     │                   │     │         │
└──────────────┘     └──────────────┘     └───────────────────┘     └─────────┘
      │                     │                      │                      │
      │                     │ Event:               │ Event:               │
      │   API Polling/      │ DeviceDiscovered     │ AssetCountChanged    │
      │   Webhooks          │                      │                      │
                            ▼                      ▼                      ▼
                    Create sync logs       Create/Update Asset    Update Billing
                                           Handle conflicts       Snapshots
```

**Interaction Pattern:**
- GoogleAdmin/Action1 poll external APIs or receive webhooks
- Publish `DeviceDiscovered` events
- AssetManagement listens, creates/updates assets, handles conflicts
- AssetManagement publishes `AssetCountChanged`
- PIB listens and updates entitlement snapshots for billing

---

### 2. Quote-to-Cash Flow (Sales → Billing → Payment)

```
┌───────────────┐     ┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│ContractManager│───▶│     PIB     │────▶│   Payment   │───▶│ClientPortal │
└───────────────┘     └─────────────┘     └─────────────┘     └─────────────┘
      │                   │                   │                    │
      │ Event:            │ Event:            │ Event:             │
      │ BillingTemplateDue│ InvoicePublished  │ PaymentSucceeded   │
      ▼                   ▼                   ▼                    ▼
 Quote approved     Generate Invoice     Auto-process         Real-time
 Contract created   from Template        payment if           dashboard
 Template scheduled                      enrolled             update
```

**Interaction Pattern:**
- ContractManager creates quote, client approves via ClientPortal
- Quote approval creates Contract + BillingTemplates
- Scheduled job dispatches `BillingTemplateDue` events
- PIB listens and generates invoices from templates
- Scheduled jobs generate invoices from templates
- `InvoicePublished` triggers Payment module for auto-enrolled clients
- ClientPortal displays all stages via dynamic loading

---

### 3. Credit System Flow (Financial Operations)

```
┌─────────────────────────────────────────────────────────────────────────┐
│                          PIB MODULE (Data Owner)                        │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   ┌───────────────┐                   ┌──────────────────────────┐      │
│   │ client_credits│◄──────────────────│  ClientCreditService     │      │
│   │ balance_cents │    Atomic Ops     │  • addCredit()           │      │
│   └───────────────┘                   │  • deductCredit()        │      │
│          │                            │  • getBalance()          │      │
│          │                            └────────────▲─────────────┘      │
│          │                                         │                    │
│          ▼                                         │                    │
│   ┌──────────────────┐              ┌──────────────┴─────────────┐      │
│   │client_credit_    │              │  Cross-Module Access       │      │
│   │ledger (Audit)    │              │  via class_exists() guard  │      │
│   └──────────────────┘              └──────────────▲─────────────┘      │
│                                                     │                   │
└─────────────────────────────────────────────────────┼───────────────────┘
                                                      │
         ┌────────────────────┬───────────────────────┴──────────────┐
         │                    │                                      │
         ▼                    ▼                                      ▼
   ┌────────────┐      ┌───────────┐                          ┌───────────┐
   │ClientPortal│      │ Payment   │                          │  Admin    │
   │ (Balance   │      │ (Credit   │                          │ (Client   │
   │  Display)  │      │  Apply)   │                          │  360 View)│
   └────────────┘      └───────────┘                          └───────────┘
```

**Interaction Pattern:**
- Credit data is owned by PIB (not CRM) - financial data separation
- `ClientCreditService` provides atomic operations using `AtomicCounterService`
- Other modules access via dynamic service resolution with fallback defaults
- Full audit trail maintained in `client_credit_ledger`

---

## Communication Patterns

### Pattern 1: Event-Driven (Decoupled)

**Used For:** Cross-module state changes, async processing

```php
// Publisher (AssetManagement)
event(new AssetCountChanged($client, 'chromebook', 5, 10));

// Subscriber (PIB - self-registered in ServiceProvider)
Event::listen(AssetCountChanged::class, UpdateBillingOnAssetChange::class);
```

**Modules Using This:** All cross-module communication

---

### Pattern 2: Dynamic Relationships (Extended Models)

**Used For:** Feature modules adding relationships to core models

```php
// PIB extends CRM's Client model at boot time
Client::resolveRelationUsing('invoices', function ($client) {
    return $client->hasMany(Invoice::class);
});
```

**Modules Using This:** PIB → CRM, Payment → CRM, AssetManagement → CRM

---

### Pattern 3: Dynamic Service Access (Graceful Degradation)

**Used For:** Optional functionality when modules may be disabled

```php
$creditBalance = 0.0;
if (class_exists('\Modules\PIB\Services\ClientCreditService')) {
    $service = app(\Modules\PIB\Services\ClientCreditService::class);
    $creditBalance = $service->getBalance($clientId);
}
```

**Modules Using This:** ClientPortal, Admin aggregators

---

### Pattern 4: Widget Registry (UI Extension)

**Used For:** Modules injecting UI components into core views

```php
// PIB registers widget
$registry->register('admin.client.show.financials', function($client) {
    return view('pib::widgets.financials', compact('client'))->render();
});

// Core renders all registered widgets at hook point
$widgets = $registry->getWidgetsForHook('admin.client.show.financials', $client);
```

**Modules Using This:** PIB, AssetManagement, Payment → Admin views

---

## Data Ownership Summary

| Data Domain | Owning Module | Tables |
|-------------|---------------|--------|
| Customer Information | CRM | `clients`, `contacts`, `companies` |
| Custom Fields | CRM | `custom_fields`, `custom_field_values` |
| Assets & Inventory | AssetManagement | `assets`, `asset_staging_records` |
| Sync Logs | GoogleAdmin, Action1 | `google_sync_logs`, `action1_sync_logs` |
| Quotes & Proposals | ContractManager | `cm_quotes`, `cm_quote_line_items`, `cm_quote_revisions` |
| Contracts & Agreements | ContractManager | `cm_contracts`, `cm_contract_schedules` |
| Billing Templates | ContractManager | `cm_billing_templates` (what client agreed to pay) |
| Invoices & Execution | PIB | `pib_invoices`, `pib_invoice_line_items`, `pib_entitlement_snapshots` |
| Credit Balances | PIB | `client_credits`, `client_credit_ledger` |
| Payments | Payment | `payment_methods`, `transactions`, `refunds` |
| Alert Subscriptions | Alerts | `alert_subscriptions`, `alert_rules` |

**Key Architectural Decision:** BillingTemplates are owned by ContractManager, not PIB.
- **ContractManager**: "What did the client agree to pay?" (configuration)
- **PIB**: "Generate invoices and execute billing" (execution)

---

## Dependency Rules Enforced

### ✅ Allowed Dependencies

```
Feature Module → Core Module (CRM)        ✓
Feature Module → Feature Module (explicit) ✓ (with class_exists guard)
Integration → Core Module                  ✓
Aggregator → Multiple Modules              ✓ (via dynamic loading)
```

### ❌ Prohibited Dependencies

```
Core Module → Feature Module              ✗ (violates Core Blindness)
Direct class imports without guards       ✗ (causes failures if module disabled)
Circular dependencies                     ✗
```

---

## Real-Time Connectivity (WebSocket)

```
┌────────────────┐     ┌──────────────┐    ┌────────────────┐
│   Any Module   │───▶│    Reverb    │───▶│  Admin/Portal  │
│   (broadcast)  │     │  WebSocket   │    │      UI        │
└────────────────┘     └──────────────┘    └────────────────┘
```

**Used For:**
- Sync progress updates (GoogleAdmin, Action1)
- Invoice status changes (PIB → ClientPortal)
- Payment confirmations (Payment → ClientPortal)
- Asset count updates (AssetManagement → Admin dashboards)

---

## Cross-Module Integration Points Summary

| Integration | Source | Target | Mechanism |
|-------------|--------|--------|-----------|
| User Sync | GoogleAdmin | CRM | `GoogleUserSynced` event |
| Device Sync | GoogleAdmin/Action1 | AssetManagement | `DeviceDiscovered` events |
| Asset → Billing | AssetManagement | PIB | `AssetCountChanged` event |
| Quote → Contract | ContractManager | ContractManager | Internal (creates Contract + BillingTemplate) |
| Contract → Invoice | ContractManager | PIB | `BillingTemplateDue` event (PIB generates invoice) |
| Contract Revision | ContractManager | PIB | `ContractRevised` event (PIB calculates proration) |
| Invoice → Payment | PIB | Payment | `InvoicePublished` event |
| Payment → Invoice | Payment | PIB | `PaymentSucceeded` event |
| Contracts → Portal | ContractManager | ClientPortal | Dynamic loading (quotes, contracts, templates) |
| Invoices → Portal | PIB | ClientPortal | Dynamic loading (invoices, credits) |
| Payment → Portal | Payment | ClientPortal | Dynamic loading (payment history, methods) |
| Assets → Portal | AssetManagement | ClientPortal | Dynamic loading (asset inventory) |
| All → Alerts | Various | Alerts | Event listeners on *Failed/*Unusual |

---

## Architecture Health Indicators

| Metric | Status | Notes |
|--------|--------|-------|
| Core Blindness Violations | ✅ 0 | All feature dependencies removed from CRM |
| Dynamic Class Checking | ✅ Implemented | All cross-module access uses guards |
| Data Ownership Separation | ✅ Compliant | BillingTemplates in ContractManager, execution in PIB |
| Event-Driven Communication | ✅ Active | Modules self-register listeners |
| Graceful Degradation | ✅ Functional | System works with modules disabled |

---

## Recommendations

1. **Maintain Discipline:** Continue enforcing Core Blindness in code reviews
2. **Event Catalog:** Keep SYSTEM_ARCHITECTURE.md Section 6 updated with new events
3. **Integration Tests:** Add tests for each event flow path
4. **Monitor Dependencies:** Run `composer unused` to catch unintended couplings
5. ~~**ContractManager Migration:**~~ ✅ **COMPLETE** - ContractManager implemented with BillingTemplate ownership
6. **QuoteWizard Deprecation:** Legacy QuoteWizard module can be deprecated in favor of ContractManager

---

**Document Owner:** Architecture Team  
**Review Cadence:** Quarterly or after major module additions
