# Architecture Overview
**Current State:** Production  
**Last Updated:** February 10, 2026  
**Audience:** Developers, Technical Leadership, New Team Members

---

## Purpose

This document provides a concise overview of the **current implemented architecture**. For detailed design specifications and future plans, see [SYSTEM_ARCHITECTURE.md](SYSTEM_ARCHITECTURE.md).

**Quick Navigation:**
- [Core Principles](#core-principles) - Foundational patterns we follow
- [Module Structure](#module-structure) - What modules exist and their responsibilities  
- [Data Flow](#data-flow) - How data moves through the system
- [Controller Organization](#controller-organization) - Where code lives
- [Cross-Module Integration](#cross-module-integration) - How modules interact
- [Key Patterns](#key-patterns) - Recurring solutions to common problems

---

## Core Principles

### 1. Core Blindness
**Rule:** Core modules (CRM) never depend on feature modules (PIB, AssetManagement, Payment).

```php
// ❌ WRONG: Core importing feature module
namespace Modules\Crm\Models;
use Modules\PIB\Models\Invoice;

// ✅ CORRECT: Feature module extends core
namespace Modules\PIB\Providers;
Client::resolveRelationUsing('invoices', function ($client) {
    return $client->hasMany(Invoice::class);
});
```

**Why:** Enables modules to be independently enabled/disabled without breaking core functionality.

### 2. Data Ownership
**Rule:** Data lives in the module that owns its domain.

| Data Type | Owner | Location |
|-----------|-------|----------|
| Customer info | CRM | `clients`, `contacts`, `companies` |
| Tickets/Conversations | FreeScout Core | `conversations`, `threads` |
| Client↔Ticket Links | CRM | `client_conversations` (pivot) |
| Ticket Billing Metadata | PIB | `conversation_billing_metadata` |
| Assets | AssetManagement | `assets`, `asset_staging_records` |
| Software licenses | SoftwareSubscriptions | `software_products`, `client_software_subscriptions`, `software_assignments` |
| Quotes & Proposals | ContractManager | `cm_quotes`, `cm_quote_items` |
| Contracts & Agreements | ContractManager | `cm_contracts`, `cm_contract_schedules` |
| Billing Templates | ContractManager | `cm_billing_templates` |
| Time entries & Ad-hoc | PIB | `service_usage` |
| Credit balances | PIB | `client_credits`, `client_credit_ledger` |
| Invoices | PIB | `invoices`, `invoice_line_items` |
| Payments | Payment | `transactions`, `payment_methods` |

**Anti-Pattern:** `clients.credit_balance` column (financial data in CRM) ❌  
**Correct:** `client_credits.balance_cents` in PIB module ✅

### 3. Event-Driven Communication
**Rule:** Modules communicate via Laravel Events, not direct method calls.

```php
// Module A publishes
event(new AssetStatusChanged($asset, 'active', 'retired'));

// Module B listens (registers in its own ServiceProvider)
Event::listen(AssetStatusChanged::class, UpdateBillingListener::class);
```

### 6. Queue Isolation
**Rule:** High-volume background tasks (billing, syncing) must use dedicated queues.

```php
// PIB Job dispatching to dedicated queue
GenerateInvoiceJob::dispatch($template)->onQueue('billing');
```
**Why:** Prevents bulk operations (e.g., generating 10,000 invoices) from blocking critical system notifications (password resets, ticket alerts) which run on the `default` queue.

**Implementation Status:** ⚠️ **Partially Implemented**
- ✅ Queue configuration exists (`billing`, `long-running` queues defined in config/queue.php)
- ⏳ **Action Required:** PIB jobs need to be updated to use `->onQueue('billing')` when dispatched
- 🎯 **Priority:** HIGH - Required to prevent system notification delays during bulk billing operations

### 7. Service Interfaces
**Rule:** Define contracts in Core for critical feature module services.

```php
// Core interface: app/Contracts/Billing/CreditLedgerInterface.php
interface CreditLedgerInterface {
    public function addCredit(...);
}

// Feature module implements: Modules/PIB/Services/ClientCreditService.php
class ClientCreditService implements CreditLedgerInterface { ... }

// Usage in Core/Other modules (via dependency injection)
public function __construct(CreditLedgerInterface $ledger) { ... }
```
**Why:** Allows feature modules to be swapped or mocked without changing consuming code.

---

## Module Structure

### Current Modules

```
Core Application (app/)
├── Models: User, Thread, Conversation (FreeScout core)
├── Services: AtomicCounterService, EntitlementEngine, RateLimiter, CircuitBreaker
├── Traits: ExtensibleModel, HasAtomicCounters
└── Http/Controllers/Admin: Cross-module aggregators only

Modules/
├── Crm/                    ✅ Foundation module (customer data) [REQUIRED] [CSV Import]
│   NOTE: CRM is a "foundation module" - always enabled, other modules depend on it
│   ├── Models: Client, Company, Contact, CustomField
│   ├── Services: ClientService, ClientMetricsService
│   ├── Listeners: ConversationCreated → link to client
│   └── Http/Controllers: Client360Controller (uses dynamic loading)
│
├── AssetManagement/        ✅ Asset inventory & tracking
│   ├── Entities: Asset, AssetStagingRecord
│   ├── Services: AssetStatusService, AssetCounterService, AssetReconciliationService
│   ├── Listeners: GoogleChromebookDiscoveredListener, Action1DeviceDiscoveredListener
│   └── Http/Controllers: AssetController
│
├── GoogleAdmin/            ✅ Google Workspace integration
│   ├── Services: GoogleWorkspaceService, GoogleUserProvider
│   └── Events: GoogleUserSynced, GoogleChromebookDiscovered, GoogleSyncFailed
│
├── Action1/                ✅ RMM integration (Windows/Mac/Linux)
│   ├── Services: Action1Service
│   └── Events: Action1DeviceDiscovered, Action1DeviceUpdated, Action1SoftwareDiscovered, Action1SyncFailed
│
├── ContractManager/        ✅ Quotes, contracts, billing configuration
│   ├── Models: Quote, Contract, BillingTemplate, ContractSchedule
│   ├── Services: ContractService, QuoteService
│   ├── Events: QuoteApproved, ContractActivated, BillingTemplateDue
│   └── Http/Controllers: QuoteController, ContractController
│
├── PIB/                    ✅ Billing execution engine
│   ├── Models: Invoice, InvoiceLineItem, EntitlementSnapshot, ClientCredit, ServiceUsage
│   ├── Services: ClientCreditService, BillingService, InvoiceGenerator, TimeEntryService, BillingAnalysisService
│   ├── Resolvers: SilverPlanEntitlementResolver, RentToOwnEntitlementResolver
│   └── Http/Controllers: BillingController, ServiceUsageController
│   Note: ProrationService lives in app/Services (shared infrastructure)
│
├── SoftwareSubscriptions/  ✅ Subscription lifecycle & license tracking [CSV Import]
│   ├── Models: SoftwareProduct, ClientSoftwareSubscription, SoftwareAssignment
│   ├── Services: SubscriptionCounterService, LicenseDeploymentService, SoftwareReconciliationService, VendorReconciliationService
│   ├── Resolvers: SoftwareProductEntitlementResolver
│   ├── Events: SoftwareAssignmentAdded, SoftwareCountChanged, SoftwareComplianceAlert
│   └── Listeners: ContactCreated, ContactDeactivated, AssetCreated, AssetRetired, Action1SoftwareDiscovered, GoogleLicenseDiscovered
│
├── Payment/                ✅ Helcim payment processing
│   ├── Models: PaymentMethod, Transaction
│   └── Services: HelcimService
│
├── ClientPortal/           ✅ Client-facing portal
│   ├── Services: PortalTabRegistry
│   └── Http/Controllers: PortalController (uses dynamic loading)
│
├── DevFeedback/            ✅ Developer feedback collection
│   ├── Purpose: In-app bug/feature feedback submission
│   ├── Controllers: DevFeedbackController, SettingsController
│   ├── Mail: DevFeedbackSubmitted
│   └── Dependencies: None (standalone utility)
│
├── EmailMigration/         ✅ Enterprise email migration platform
│   ├── Purpose: IMAP-to-IMAP email migration with Flight Deck UI
│   ├── Models: MigrationProject, MigrationBatch, MigrationMapping, MigrationMessage
│   ├── Services: ImapMigrationService, DiscoveryService, VerificationService
│   ├── Features: 4-stage workflow (Discovery→Mapping→Verification→Execution)
│   └── Dependencies: None (standalone tool)
│
├── Alerts/                 ✅ Centralized notification system [IMPLEMENTED]
│   ├── Purpose: Alert subscription, routing, throttling, and digest support
│   ├── Services: AlertService, AlertSubscriptionService
│   ├── Models: AlertType, AlertSubscription, AlertDeliveryLog, AlertThrottle
│   ├── Listeners: PaymentFailed, InvoiceUnusual, GoogleSyncFailed, Action1SyncFailed
│   └── Dependencies: None (infrastructure layer)
│
├── WidgetRegistry/         ✅ UI composition infrastructure [IMPLEMENTED]
│   ├── Purpose: Dynamic widget registration for cross-module views
│   ├── Services: WidgetRegistryService
│   └── Dependencies: None (infrastructure module)
│
└── KnowledgeBase/          ✅ Help article management [IMPLEMENTED]
    ├── Purpose: Internal knowledge base and documentation
    └── Dependencies: None (standalone utility)
```

### Module Dependencies

```
CRM (core) ←─── PIB, AssetManagement, Payment, ClientPortal
                    (feature modules extend CRM)

AssetManagement ←─── GoogleAdmin, Action1
                      (integrations feed asset data)

PIB ←─── Payment
         (payment processes invoices)
```

**Dependency Rule:** Dependencies only flow upward (feature → core), never downward (core → feature).

---

## Data Flow

### 1. Asset Discovery Flow

```
External API (Google/Action1)
    ↓
Sync Service (polling/webhook)
    ↓
Event: DeviceDiscovered
    ↓
AssetManagement Listener
    ↓
Asset created/updated
    ↓
Event: AssetCountChanged
    ↓
PIB Listener (updates billing)
```

### 2. Contract & Billing Flow

```
Quote Created (ContractManager)
    ↓
Client Approves Quote
    ↓
Event: QuoteApproved
    ↓
Contract Created (ContractManager)
    ↓
BillingTemplate Created (ContractManager)
    ↓
Event: BillingTemplateDue (scheduled)
    ↓
PIB: EntitlementEngine.resolve() → Calculate amount
    ↓
Invoice Generated (PIB)
    ↓
Event: InvoicePublished
    ↓
Payment auto-processes (if enrolled)
```

**Module Boundary:**
- **ContractManager**: "What did the client agree to pay?" (quotes, contracts, billing config)
- **PIB**: "Execute billing and generate invoices" (invoice generation, credits, proration)

### 3. Credit System Flow

```
Payment received
    ↓
PIB: ClientCreditService.addCredit()
    ↓
Atomic update to client_credits.balance_cents
    ↓
Ledger entry in client_credit_ledger
    ↓
Asset purchased
    ↓
PIB: ClientCreditService.deductCredit()
    ↓
Atomic decrement with balance validation
```

### 4. Software Subscription Flow

```
Software subscription created for client
    ↓
SoftwareSubscriptions: ClientSoftwareSubscription created
    ↓
User/Asset added to client
    ↓
Event: ContactCreated / AssetCreated
    ↓
SoftwareSubscriptions Listener (suggest assignment)
    ↓
Admin assigns software to user/device
    ↓
Event: SoftwareAssignmentAdded
    ↓
SoftwareSubscriptions: Update assignment count
    ↓
Event: SoftwareCountChanged
    ↓
PIB Listener (recalculate subscription cost)
    ↓
Monthly: SoftwareProductEntitlementResolver
    ↓
Invoice line item generated
```

### 4a. Software Discovery & Reconciliation Flow

**Purpose:** Automatically discover installed software from RMM tools and Google Workspace, reconcile with expected assignments, and provide verified counts to PIB for accurate billing.

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    SOFTWARE DISCOVERY SOURCES                           │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   Action1 Agent                    GoogleAdmin                          │
│   (installed on endpoints)         (Workspace API)                      │
│         │                                │                              │
│         ▼                                ▼                              │
│   Reports installed                Fetches license                      │
│   software inventory               assignments                          │
│         │                                │                              │
│         ▼                                ▼                              │
│   Event: Action1SoftwareDiscovered Event: GoogleLicenseDiscovered       │
│         │                                │                              │
│         └────────────────┬───────────────┘                              │
│                          ▼                                              │
│              SoftwareSubscriptions Listener                             │
│                          │                                              │
│         ┌────────────────┼────────────────┐                             │
│         ▼                ▼                ▼                             │
│   Create/Update    Reconcile with    Flag discrepancies                 │
│   discovery        expected          (over-deployed,                    │
│   records          assignments       under-licensed)                    │
│         │                                │                              │
│         └────────────────┬───────────────┘                              │
│                          ▼                                              │
│              Event: SoftwareReconciled                                  │
│                          │                                              │
│         ┌────────────────┼────────────────┐                             │
│         ▼                ▼                ▼                             │
│   Update atomic     PIB Listener:    Alert if                           │
│   counters          recalculate      compliance                         │
│   (verified)        billing          issues                             │
│                          │                                              │
│                          ▼                                              │
│              Monthly: SoftwareProductEntitlementResolver                │
│                          │                                              │
│                          ▼                                              │
│              Invoice line item (verified count)                         │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

**Action1 Software Discovery:**
```
Action1 Agent (endpoint)
    ↓
Reports installed software list via webhook
    ↓
Event: Action1SoftwareDiscovered {
    asset_id, software_name, version, install_date
}
    ↓
SoftwareSubscriptions Listener:
    1. Match software_name to known SoftwareProduct
    2. Find Asset → Client → ClientSoftwareSubscription
    3. Create/update SoftwareDiscovery record
    4. Compare discovered vs expected assignments
    5. Flag discrepancies for admin review
```

**Google Workspace License Sync:**
```
Scheduled Job: GoogleLicenseSyncJob (daily)
    ↓
GoogleAdmin: Fetch all license assignments
    (Admin SDK Directory API)
    ↓
For each license assignment:
    ↓
Event: GoogleLicenseDiscovered {
    user_email, product_name, sku_name, assigned_at
}
    ↓
SoftwareSubscriptions Listener:
    1. Match user_email to CRM Contact
    2. Match product_name to SoftwareProduct (e.g., "Google Workspace Business Plus")
    3. Find Contact → Client → ClientSoftwareSubscription
    4. Verify assignment exists in software_assignments
    5. Auto-create assignment if missing (with discovery source)
    6. Update assignment count
```

**Reconciliation States:**
| State | Meaning | Action |
|-------|---------|--------|
| `verified` | Discovered software matches expected assignment | Count for billing |
| `over_deployed` | Software found but no license assigned | Alert admin, may bill |
| `under_utilized` | License assigned but software not installed | Suggest removal |
| `unrecognized` | Software found, not in catalog | Suggest adding to catalog |

**PIB Integration:**
- SoftwareSubscriptions provides **verified counts** (discovered + assigned) to PIB
- Over-deployed software can be auto-billed at passthrough rates
- Under-utilized licenses flagged for cost optimization recommendations

### 5. Ticket & Service Metrics Flow

**Purpose:** Track support tickets per client to:
- Count ad-hoc/billable service tickets for PIB billing
- Track ticket lifecycle (opened, assigned, closed) for reporting
- Provide clients with visibility into their tickets via ClientPortal
- Measure response times and wait times

```
Ticket Created (FreeScout Core)
    ↓
Event: ConversationCreated
    ↓
CRM: Link conversation to client (via contact email or manual)
    ├──→ Event: ConversationLinkedToClient (includes clientConversationId)
    │         ├──→ PIB: Create conversation_billing_metadata (determines billing category)
    │         └──→ ClientPortal: Show ticket in client dashboard
    ↓
CRM: Record lifecycle event (ticket_lifecycle_events)
    ↓
Technician assigned
    ├──→ CRM: Record assignment event
    └──→ ClientPortal: Broadcast update to client session
    ↓
Technician works ticket, logs time
    ├──→ PIB: time_entries + service_usage record
    └──→ ClientPortal: Show time logged (transparency)
    ↓
Ticket Closed
    ├──→ CRM: Record close event with resolution time
    └──→ ClientPortal: Update ticket status
    ↓
Monthly: CRM calculates client_service_metrics
    ├──→ PIB: Informs billing decisions (ad-hoc thresholds)
    └──→ ClientPortal: Monthly summary available to clients
```

**Core Blindness in Action:**
- CRM owns the ticket↔client relationship (`client_conversations`) but knows nothing about billing
- PIB owns billing classification (`conversation_billing_metadata`) and determines billability based on client's contract
- CRM fires `ConversationLinkedToClient` event; PIB listener creates billing metadata

**Data Flow to Subscribers:**

| Data | PIB Uses For | ClientPortal Uses For |
|------|--------------|----------------------|
| `client_conversations` | Ad-hoc bucket counting | Ticket list view |
| `ticket_lifecycle_events` | Emergency billing flags | Ticket timeline |
| `time_entries` | Billable hour invoicing | Work transparency |
| `client_service_metrics` | Billing threshold decisions | Monthly summary dashboard |

**Data Model:**

| Component | Owner | Purpose |
|-----------|-------|---------|
| `conversations` | FreeScout Core | Raw ticket data |
| `client_conversations` | CRM | Links tickets to clients (no billing fields) |
| `conversation_billing_metadata` | PIB | Billing classification, billable time, invoice link |
| `ticket_lifecycle_events` | CRM | Audit trail: opens, assigns, closes |
| `client_service_metrics` | CRM | Monthly aggregated metrics per client |
| `service_usage` | PIB | Time entries, billable labor |
| `time_entries` | PIB | Granular time tracking per ticket |

**Key Metrics Tracked:**
- **Ticket Count:** Opened/Closed per client (monthly/quarterly/annual)
- **Time to First Response:** Average wait time before technician engagement
- **Time to Resolution:** Average time from open to close
- **Wait Time in Queue:** Time tickets spend unassigned
- **Technician Assignment:** Who assigned, worked on, and closed each ticket
- **Ticket Status Transitions:** Full lifecycle audit trail

**Module Responsibilities:**
- **FreeScout Core:** Owns ticket lifecycle (create, update, close)
- **CRM:** Links tickets to clients, records lifecycle events, calculates monthly metrics (no billing logic)
- **PIB:** Owns `conversation_billing_metadata`, listens to ticket events to determine billing category, tracks billable time, generates ad-hoc invoices
- **ClientPortal:** Displays ticket list, lifecycle timeline, time logs, and monthly summaries to clients

**Note:** Profit margin and technician cost allocation are calculated elsewhere (financial reporting) as they require cross-module data aggregation.

---

## Controller Organization

### Placement Rules

| Controller Type | Location | Example |
|----------------|----------|---------|
| Module-specific admin | `Modules/{Name}/Http/Controllers/` | `PIB/Http/Controllers/BillingController.php` |
| Cross-module aggregator | `app/Http/Controllers/Admin/` | `Admin/Client360Controller.php` |
| Client-facing | `Modules/ClientPortal/Http/Controllers/` | `ClientPortal/Http/Controllers/PortalController.php` |

### Admin Aggregator Pattern

**Use Case:** Admin dashboard showing client data from multiple modules

```php
namespace App\Http\Controllers\Admin;

class Client360Controller extends Controller {
    public function show($id) {
        $client = Client::findOrFail($id);
        
        // Dynamic loading: PIB module (if enabled)
        $invoices = collect();
        if (class_exists('\Modules\PIB\Models\Invoice')) {
            $invoiceClass = '\Modules\PIB\Models\Invoice';
            $invoices = $invoiceClass::where('client_id', $id)->get();
        }
        
        // Dynamic loading: AssetManagement (if enabled)
        $assets = collect();
        if (class_exists('\Modules\AssetManagement\Entities\Asset')) {
            $assetClass = '\Modules\AssetManagement\Entities\Asset';
            $assets = $assetClass::where('client_id', $id)->get();
        }
        
        return view('admin.clients.show', compact('client', 'invoices', 'assets'));
    }
}
```

**Benefits:**
- View works even if PIB or AssetManagement disabled
- No hard dependencies = no runtime errors
- Empty collections shown for missing modules

---

## Cross-Module Integration

### Pattern Selection Guide

> **When to use which pattern?** This table clarifies the recommended approach for different scenarios.

| Scenario | Recommended Pattern | Why |
|----------|-------------------|-----|
| Admin UI aggregating module data | **WidgetRegistry** | Modules register UI widgets; core renders hooks without knowing module internals |
| Controller needs data from optional module | **Dynamic class checking** (`class_exists()`) | Safe fallback when module disabled |
| Model needs relationship to another module's model | **Dynamic Relationships** | Feature module registers relation on core model |
| Module needs to react to another module's events | **Event Listeners** | Loose coupling via Laravel Events |
| Adding fields to core models | **ExtensibleModel trait** | Dynamic field registration without migrations |

### Pattern 0: Widget Registry (Preferred for UI)

**Use Case:** Admin dashboard sections that aggregate data from multiple modules.

**Why Preferred:** Zero coupling - core knows nothing about module classes.

```php
// Module registers widget during boot
// Modules/PIB/Providers/PIBServiceProvider.php
public function boot() {
    $registry = app(\App\Services\Ui\WidgetRegistry::class);
    $registry->register('admin.client.show.financials', function($client) {
        return view('pib::widgets.financials', compact('client'))->render();
    });
}

// Core controller renders registered widgets
// app/Http/Controllers/Admin/Client360Controller.php
public function show($id, WidgetRegistry $widgetRegistry) {
    $client = Client::findOrFail($id);
    $financialWidgets = $widgetRegistry->getWidgetsForHook('admin.client.show.financials', $client);
    return view('admin.clients.show', compact('client', 'financialWidgets'));
}
```

**Benefits:**
- ✅ Core controller has zero imports from feature modules
- ✅ Modules can be added/removed without touching core
- ✅ Testable: mock WidgetRegistry in tests

### Pattern 1: Dynamic Relationships

**Problem:** CRM Client needs `invoices` relationship from PIB module.

**Solution:** PIB registers relationship dynamically in its ServiceProvider.

```php
// Modules/PIB/Providers/PIBServiceProvider.php
public function boot() {
    if (class_exists(\Modules\Crm\Models\Client::class)) {
        \Modules\Crm\Models\Client::resolveRelationUsing('invoices', function ($client) {
            return $client->hasMany(\Modules\PIB\Models\Invoice::class);
        });
    }
}
```

### Pattern 2: Dynamic Service Access

**Problem:** ClientPortal needs to display credit balance from PIB.

**Solution:** Check for service existence, provide default if missing.

```php
// Modules/ClientPortal/Http/Controllers/PortalController.php
protected function getClientSummary(Client $client): array {
    $creditBalance = 0.0;
    
    if (class_exists('\Modules\PIB\Services\ClientCreditService')) {
        try {
            $service = app(\Modules\PIB\Services\ClientCreditService::class);
            $creditBalance = $service->getBalance($client->id);
        } catch (\Exception $e) {
            \Log::warning('PIB service unavailable', ['error' => $e->getMessage()]);
        }
    }
    
    return [
        'name' => $client->name,
        'credit_balance' => $creditBalance,
    ];
}
```

### Pattern 3: Event Listeners

**Problem:** AssetManagement needs to update billing when assets change.

**Solution:** PIB registers listener for AssetManagement events.

```php
// Modules/PIB/Providers/PIBServiceProvider.php
public function boot() {
    if (class_exists(\Modules\AssetManagement\Events\AssetCountChanged::class)) {
        Event::listen(
            \Modules\AssetManagement\Events\AssetCountChanged::class,
            \Modules\PIB\Listeners\UpdateBillingOnAssetChange::class
        );
    }
}
```

### Pattern 5: Software Assignment Integration

**Problem:** SoftwareSubscriptions needs to track which users/assets have specific software for deployment and billing.

**Solution:** Polymorphic assignments with automatic lifecycle management.

```php
// SoftwareSubscriptions: Assign software to user or asset
$assignment = SoftwareAssignment::create([
    'subscription_id' => $clientSubscription->id,
    'assignable_type' => 'contact', // or 'asset'
    'assignable_id' => $contact->id,
    'deployment_status' => 'pending',
]);

event(new SoftwareAssignmentAdded($assignment));

// PIB Listener: Update billing when software count changes
class UpdateBillingOnSoftwareChange {
    public function handle(SoftwareCountChanged $event) {
        // Recalculate subscription cost based on new assignment count
        $subscription = $event->subscription;
        $assignedCount = $subscription->assignments()->active()->count();
        
        // Update billing template snapshot
        $billingTemplate = $subscription->billingTemplate;
        $billingTemplate->updateProductConfig([
            'software_count' => $assignedCount,
            'tier' => $this->selectTier($subscription->product, $assignedCount),
        ]);
    }
}
```

**Auto-Revocation on Entity Deactivation:**
```php
// SoftwareSubscriptions: Listen for contact/asset deactivation
public function boot() {
    Event::listen(ContactDeactivated::class, function ($event) {
        // Revoke all software assignments for this contact
        SoftwareAssignment::where('assignable_type', 'contact')
            ->where('assignable_id', $event->contact->id)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
        
        // Trigger billing recalculation
        foreach ($this->getAffectedSubscriptions($event->contact) as $subscription) {
            event(new SoftwareCountChanged($subscription));
        }
    });
}
```

### Pattern 4: ExtensibleModel

**Problem:** Payment module needs to add `billing_mode` field to Company (CRM).

**Solution:** Use ExtensibleModel trait for dynamic field registration.

```php
// CRM Company model
class Company extends Model {
    use ExtensibleModel;
    protected $fillable = ['name', 'email']; // Core fields only
}

// Payment ServiceProvider
public function boot() {
    if (class_exists(\Modules\Crm\Models\Company::class)) {
        \Modules\Crm\Models\Company::registerExtension('Payment', [
            'billing_mode' => 'string',
        ]);
    }
}
```

---

## Key Patterns

### Atomic Counter Operations

**Use Case:** Prevent race conditions in financial counters (credit balances, asset counts).

```php
// PIB ClientCreditService using AtomicCounterService
public function addCredit(int $clientId, float $amount): void {
    $amountCents = (int) round($amount * 100);
    
    DB::transaction(function () use ($clientId, $amountCents) {
        $newBalance = $this->counter->increment(
            table: 'client_credits',
            where: ['client_id' => $clientId],
            column: 'balance_cents',
            amount: $amountCents
        );
        
        // Audit trail
        DB::table('client_credit_ledger')->insert([...]);
    });
}
```

**Why:** Raw SQL `UPDATE` with `WHERE` ensures atomicity even under concurrent requests.

### Module Discovery

**Use Case:** Feature module needs to know if another feature module exists.

```php
// Check if module is loaded
if (class_exists('\Modules\ModuleName\Models\Target')) {
    // Safe to integrate
}

// Alternative: Check via Laravel Modules package
if (\Nwidart\Modules\Facades\Module::find('ModuleName')) {
    // Module exists
}
```

### Graceful Degradation

**Principle:** System should work even when optional modules are disabled.

```php
// Initialize with empty defaults
$data = [
    'client' => $client,
    'invoices' => collect(),
    'assets' => collect(),
];

// Populate only if modules available
if (class_exists(...)) { $data['invoices'] = ...; }
if (class_exists(...)) { $data['assets'] = ...; }

return view('dashboard', $data);
```

**View handles empty collections:**
```blade
@if ($invoices->isNotEmpty())
    <x-invoice-list :invoices="$invoices" />
@else
    <p>No billing data available</p>
@endif
```

---

## Database Design Patterns

### Cents-Based Storage for Money

**Rule:** Store monetary values as integers (cents) for atomic operations.

```sql
-- PIB module
CREATE TABLE client_credits (
    balance_cents INT NOT NULL DEFAULT 0  -- Atomic operations
);

CREATE TABLE client_credit_ledger (
    amount DECIMAL(10,2) NOT NULL  -- Human-readable audit trail
);
```

**Why:** Integer operations are atomic at database level; decimals can have rounding issues.

### Audit Trails

**Rule:** Every financial operation gets a ledger entry.

```sql
CREATE TABLE client_credit_ledger (
    id BIGINT UNSIGNED PRIMARY KEY,
    client_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    type ENUM('credit', 'debit') NOT NULL,
    description TEXT NOT NULL,
    reference_type VARCHAR(255),  -- Invoice, Payment, AssetPurchase
    reference_id BIGINT UNSIGNED,
    balance_after DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP
);
```

### Foreign Keys to Core

**Pattern:** Feature modules can reference core tables, not vice versa.

```sql
-- ✅ CORRECT: PIB references CRM
CREATE TABLE invoices (
    client_id BIGINT UNSIGNED,
    FOREIGN KEY (client_id) REFERENCES clients(id)
);

-- ❌ WRONG: CRM references PIB
-- ALTER TABLE clients ADD COLUMN latest_invoice_id ...
```

---

## Testing Strategy

### Module Compliance Tests

Each module includes compliance verification:

```bash
$ ./Modules/Crm/verify-compliance.sh
✓ Zero feature module imports in CRM
✓ All events extend VersionedEvent
✓ All DTOs use readonly properties
✓ ExtensibleModel trait properly used
```

### Integration Tests

Test cross-module interactions:

```php
// tests/Integration/AssetToBillingTest.php
public function test_asset_count_change_updates_billing() {
    $client = Client::factory()->create();
    $template = BillingTemplate::factory()->for($client)->create();
    
    // Trigger event
    event(new AssetCountChanged($client, 'chromebook', 5, 10));
    
    // Verify PIB listener updated snapshot
    $this->assertEquals(10, $template->fresh()->product_config['asset_count']);
}
```

---

## Common Tasks

### Adding a New Module

1. Generate module: `php artisan module:make ModuleName`
2. Define `module.json` with dependencies
3. Create models/services in module directory
4. Register event listeners in ServiceProvider
5. Use dynamic class checking for cross-module access
6. Write compliance tests
7. Update this document

### Accessing Another Module's Data

```php
// ✅ DO: Dynamic class checking
if (class_exists('\Modules\Other\Models\Target')) {
    $data = \Modules\Other\Models\Target::where(...)->get();
}

// ❌ DON'T: Direct import (if Other is a feature module)
use Modules\Other\Models\Target;
$data = Target::where(...)->get();
```

### Adding Fields to Core Models

```php
// Feature module ServiceProvider
public function boot() {
    if (class_exists(\Modules\Crm\Models\Company::class)) {
        \Modules\Crm\Models\Company::registerExtension('MyModule', [
            'my_field' => 'string',
            'my_json_field' => 'json',
        ]);
    }
}
```

### Creating Admin Views

**Option A: Module-specific** → Put controller in module  
**Option B: Aggregates data from multiple modules** → Use dynamic loading in `app/Http/Controllers/Admin/`

---

## Architecture Decisions

### Why Credit Balances in PIB Instead of CRM?

**Decision:** Credits are **financial/billing data**, not customer data.

**Benefits:**
- Billing features can be disabled without breaking CRM
- Financial audit trail isolated for compliance
- Atomic operations on financial counters
- Clear separation of concerns

**Migration:** Section 14 in [SYSTEM_ARCHITECTURE.md](SYSTEM_ARCHITECTURE.md#14-case-study-credit-system-migration-crm--pib)

### Why Controllers in Module Directories?

**Decision:** Module-specific controllers belong in modules.

**Benefits:**
- Clear ownership (PIB team owns BillingController)
- Module can be packaged independently
- No merge conflicts in core `app/` directory
- Easy to see all module functionality in one place

**Exception:** Cross-module aggregators stay in core with dynamic loading.

### Why Dynamic Class Checking Instead of Dependency Injection?

**Decision:** Use `class_exists()` for optional modules.

**Why:**
- Modules can be enabled/disabled at runtime
- No container binding errors when module missing
- Explicit about optional vs required dependencies
- Graceful degradation built-in

### Why a Separate SoftwareSubscriptions Module?

**Decision:** Software license tracking is a distinct domain from both CRM (customer data) and AssetManagement (hardware inventory).

**Why a Separate Module:**
- **Distinct Domain:** Software subscriptions have their own lifecycle, pricing models, and vendor relationships
- **Complex Billing Logic:** Tiered pricing, per-user vs per-device, included vs passthrough billing modes
- **Financial Analysis:** Critical for cost accounting and margin analysis (separate from hardware procurement)
- **Deployment Tracking:** Drives operational workflows (what to install on new assets)
- **Vendor Reconciliation:** Matches internal counts to external vendor invoices

**Why NOT in AssetManagement:**
- Software licenses are not physical assets
- Per-user licenses have no asset association at all
- Different lifecycle (subscriptions renew vs. assets depreciate)
- AssetManagement would become bloated with unrelated concerns

**Why NOT in PIB:**
- PIB is a billing engine (invoice generation), not a subscription management system
- SoftwareSubscriptions owns the catalog, assignments, and counts
- PIB consumes those counts via the `SoftwareProductEntitlementResolver`

**Integration Points:**
- SoftwareSubscriptions → PIB: Provides subscription counts for billing
- SoftwareSubscriptions → AssetManagement: Links device-based software to assets
- SoftwareSubscriptions → CRM: Links user-based software to contacts

---

## Troubleshooting

### "Class not found" errors

**Cause:** Direct import of optional module class.

**Fix:** Use dynamic class checking:
```php
if (class_exists('\Modules\Module\Class')) {
    // Use class
}
```

### Relationship returns null

**Cause:** Feature module not loaded when relationship accessed.

**Fix:** Check if relationship exists:
```php
if ($client->relationLoaded('invoices')) {
    $invoices = $client->invoices;
}
```

### Migration fails in production

**Cause:** Table/column doesn't exist when migrating down.

**Fix:** Add existence checks:
```php
if (Schema::hasColumn('table', 'column')) {
    $table->dropColumn('column');
}
```

---

## Reference Documents

- **[SYSTEM_ARCHITECTURE.md](SYSTEM_ARCHITECTURE.md)** - Complete design specification (7500+ lines)
- **[MODULE_DEVELOPMENT_GUIDE.md](MODULE_DEVELOPMENT_GUIDE.md)** - Detailed patterns and examples
- **[IMPLEMENTATION_ROADMAP.md](IMPLEMENTATION_ROADMAP.md)** - Phase-by-phase implementation plan
- **[MSP_PRODUCT_DEFINITIONS.md](MSP_PRODUCT_DEFINITIONS.md)** - Billing products and rules

---

**Document Owner:** Development Team  
**Review Frequency:** After major architectural changes  
**Last Reviewed:** January 19, 2026
