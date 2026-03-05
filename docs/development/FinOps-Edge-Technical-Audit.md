# FinOps MSP App — Data Edge Technical Audit

> **Auditor Role:** Senior Laravel Architect & FinOps Consultant  
> **Source File:** `docs/development/WIP/FinOps Data Edges.json`  
> **Audit Date:** 2026-03-02  
> **Scope:** 24 data edges across 4 edge groups — Integration (INT), Resolution (RES), Business Logic (BIZ), Financial (FIN), View (VW), and Action/Write-back (ACT).

---

## Legend

| Status | Meaning |
|--------|---------|
| ✅ **Implemented** | All required artifacts exist and the data chain is complete. |
| ⚠️ **Partial** | Core artifacts exist but a specific service, resolver, or test is missing. |
| ❌ **Gap** | The edge is architecturally implied but no concrete artifacts exist for it. |

---

## High-Level Summary Table

| Edge ID | Source | Target | Label | Transport Method | Status | Key Missing Artifact |
|---------|--------|--------|-------|-----------------|--------|----------------------|
| INT_01 | Action1 (RMM) | UserEventConflictTable | User Accounts Sync | Webhook → Job → Event | ⚠️ Partial | `UserIdentityConflictResolver` service |
| INT_02 | Action1 (RMM) | AssetEventConflictTable | Win/Lin Assets Sync | Webhook → Job → Event → Listener | ✅ Implemented | — |
| INT_03 | Action1 (RMM) | SoftwareEventConflictTable | Software Inventory Sync | Event → Listener | ✅ Implemented | — |
| INT_04 | GoogleAdmin | UserEventConflictTable | Google Accounts Sync | Push Webhook → Job → Event | ⚠️ Partial | `UserIdentityConflictResolver` service |
| INT_05 | GoogleAdmin | AssetEventConflictTable | CrOS Assets Sync | Job → Event → Listener | ✅ Implemented | — |
| INT_06 | GoogleAdmin | SoftwareEventConflictTable | Software Sync | Event → Listener | ✅ Implemented | — |
| RES_01 | UserEventConflictTable | CRM | Resolved Identity Data | Service Class + Staging Model | ⚠️ Partial | `CrmStagingResolverService` (explicit conflict vote/merge logic) |
| RES_02 | AssetEventConflictTable | InventoryManager | Resolved Asset Data | Job → Service | ✅ Implemented | — |
| RES_03 | SoftwareEventConflictTable | SoftwareManager | Resolved Software Data | Listener → Service | ✅ Implemented | — |
| BIZ_01 | CRM | PIB | Active Users by Company | Eloquent Relationship | ⚠️ Partial | No `UserEntitlementCountProvider`; count sourced ad-hoc |
| BIZ_02 | InventoryManager | PIB | Active Assets by User/Company | Eloquent + Counter Table | ✅ Implemented | — |
| BIZ_03 | SoftwareManager | PIB | Billable Software by User/Company | Resolver + Counter Service | ✅ Implemented | — |
| BIZ_04 | ContractManager | PIB | Scheduled Invoice Event | Event → Listener → Job | ✅ Implemented | — |
| FIN_01 | PIB | InvoiceManager | Resolved Invoice | Service Class → Job | ✅ Implemented | — |
| FIN_02 | InvoiceManager | Payments | Invoice for Processing | Job → Service → Gateway | ✅ Implemented | — |
| VW_01 | InventoryManager | vwAssets | Display Assets | Eloquent → Controller → Blade | ✅ Implemented | — |
| VW_02 | CRM | vwUsers | Display Users | Eloquent → Controller → Blade | ✅ Implemented | — |
| VW_03 | ContractManager | vwContracts | Display Contracts | Eloquent → Controller → Blade | ✅ Implemented | — |
| VW_04 | InvoiceManager | vwInvoices | Display Invoices | Eloquent → Controller → Blade | ✅ Implemented | — |
| VW_05 | Payments | vwPayments | Display Payments | View Component → Blade | ✅ Implemented | — |
| ACT_01 | xUsers | GoogleAdmin | Provision/Deprovision | Controller → Service → External API | ⚠️ Partial | `UserProvisioningController` in Portal; write-back Action class |
| ACT_02 | xContracts | ContractManager | Approve Contract | Controller → Event → Listener | ✅ Implemented | — |
| ACT_03 | xPayments | Payments | Process Payment | Controller → Job → Gateway | ✅ Implemented | — |
| ACT_04 | xInvoices | InvoiceManager | Dispute Invoice | Controller → State Change | ⚠️ Partial | Explicit `DisputeInvoice` state transition + dedicated test |

---

## Technical Implementation Detail

---

### INT_01 — Action1 (RMM) → UserEventConflictTable

**Data Transport Method:**  
`Action1WebhookController` receives an inbound HTTP POST from the Action1 cloud. It dispatches `SyncAction1DevicesJob` onto the queue. The job calls `Action1Service` to fetch users/agents, then fires `Action1DeviceDiscovered` events. No listener currently writes these user-identity records to a staging table (`CrmStagingRecord`).

**Conflict Table Reconciliation Logic Required:**  
The `crm_staging_records` table (created by migration `2026_02_15_000001_create_crm_staging_records_table`) must hold a `source` column (`action1`|`google`). A `UserIdentityConflictResolver` service needs to:
1. Upsert a row keyed on `email` + `company_id`.
2. Set `source_action1 = true` and record Action1-specific fields (endpoint ID, device name).
3. Flag `conflict = true` if the same email already has a Google-sourced record with a different display name.

**Laravel Artifacts:**

| File | Status |
|------|--------|
| `Modules/Action1/Http/Controllers/Action1WebhookController.php` | ✅ Exists |
| `Modules/Action1/Jobs/SyncAction1DevicesJob.php` | ✅ Exists |
| `Modules/Action1/Events/Action1DeviceDiscovered.php` | ✅ Exists |
| `Modules/Crm/Models/CrmStagingRecord.php` | ✅ Exists |
| `Modules/Crm/Listeners/Action1UserDiscoveredListener.php` | ❌ Missing |
| `Modules/Crm/Services/UserIdentityConflictResolver.php` | ❌ Missing |

**Verification Step:**
```bash
php artisan test --filter=Action1UserStagingTest
# Expected: CrmStagingRecord is created with source='action1'
# when Action1DeviceDiscovered is dispatched with a user payload.
```

---

### INT_02 — Action1 (RMM) → AssetEventConflictTable

**Data Transport Method:**  
`Action1WebhookController` → `SyncAction1DevicesJob` → fires `Action1DeviceDiscovered` event → `Action1DeviceDiscoveredListener` (in AssetManagement) → writes to `asset_staging_records` table.

**Conflict Table Reconciliation Logic:**  
`AssetStagingRecord` holds `source` (`action1`|`google`|`manual`), `serial_number`, and `provider_id`. `ReconcileAssetsJob` applies a merge strategy: prefer `action1` for OS/hardware fields and `google` for Chrome device ownership. Duplicate detection is keyed on `serial_number`.

**Laravel Artifacts:**

| File | Status |
|------|--------|
| `Modules/AssetManagement/Listeners/Action1DeviceDiscoveredListener.php` | ✅ Exists |
| `Modules/AssetManagement/Entities/AssetStagingRecord.php` | ✅ Exists |
| `Modules/AssetManagement/Jobs/ReconcileAssetsJob.php` | ✅ Exists |
| `Modules/AssetManagement/Services/AssetStatusService.php` | ✅ Exists |
| `Modules/AssetManagement/Tests/Feature/GoogleChromebookDiscoveredListenerPestTest.php` | ✅ Exists |

**Verification Step:**
```bash
php artisan test --filter=Action1DeviceDiscoveredListenerPestTest
# Also verify staging record upsert:
php artisan tinker --execute="Modules\AssetManagement\Entities\AssetStagingRecord::where('source','action1')->count();"
```

---

### INT_03 — Action1 (RMM) → SoftwareEventConflictTable

**Data Transport Method:**  
`Action1SoftwareDiscovered` event (fired by `SyncAction1DevicesJob`) → `ReconcileAction1SoftwareDiscovery` listener → writes to `software_discoveries` table (the SoftwareEventConflictTable), using `Action1SoftwareDiscoveredData` DTO.

**Conflict Table Reconciliation Logic:**  
`SoftwareDiscovery` model holds `source`, `product_name`, `publisher`, `version`, `device_id`. `SoftwareReconciliationService::reconcileFromDiscovery()` applies a fuzzy name-match against `software_products` to link the discovery to a known `SoftwareProduct`, or flags it as `unrecognized` (emitting `UnrecognizedSoftwareDetected`).

**Laravel Artifacts:**

| File | Status |
|------|--------|
| `Modules/Action1/Events/Action1SoftwareDiscovered.php` | ✅ Exists |
| `Modules/Action1/DataTransferObjects/Action1SoftwareDiscoveredData.php` | ✅ Exists |
| `Modules/SoftwareSubscriptions/Listeners/ReconcileAction1SoftwareDiscovery.php` | ✅ Exists |
| `Modules/SoftwareSubscriptions/Models/SoftwareDiscovery.php` | ✅ Exists |
| `Modules/SoftwareSubscriptions/Services/SoftwareReconciliationService.php` | ✅ Exists |
| `Modules/SoftwareSubscriptions/Tests/Feature/ReconcileAction1SoftwareDiscoveryPestTest.php` | ✅ Exists |

**Verification Step:**
```bash
php artisan test --filter=ReconcileAction1SoftwareDiscoveryPestTest
```

---

### INT_04 — GoogleAdmin → UserEventConflictTable

**Data Transport Method:**  
`GoogleDirectoryWebhookController` (Google Push Notification) → `SyncGoogleUsersJob` → `GoogleWorkspaceService::syncUsers()` → fires `GoogleUserSynced` event → `GoogleUserSyncedListener` in CRM → should write to `crm_staging_records`.

**Conflict Table Reconciliation Logic:**  
Same resolver required as INT_01. The `UserIdentityConflictResolver` must accept both `action1` and `google` sources and detect conflicts when the same email maps to conflicting company domains (using `CompanyDomain` model).

**Laravel Artifacts:**

| File | Status |
|------|--------|
| `Modules/GoogleAdmin/Http/Controllers/GoogleDirectoryWebhookController.php` | ✅ Exists |
| `Modules/GoogleAdmin/Jobs/SyncGoogleUsersJob.php` | ✅ Exists |
| `Modules/GoogleAdmin/Events/GoogleUserSynced.php` | ✅ Exists |
| `Modules/GoogleAdmin/Services/GoogleWorkspaceService.php` | ✅ Exists |
| `Modules/Crm/Listeners/GoogleUserSyncedListener.php` | ✅ Exists |
| `Modules/Crm/Models/CrmStagingRecord.php` | ✅ Exists |
| `Modules/Crm/Services/UserIdentityConflictResolver.php` | ❌ Missing |
| `Modules/Crm/Tests/Feature/GoogleUserSyncTest.php` | ✅ Exists (verify staging write) |

**Verification Step:**
```bash
php artisan test --filter=GoogleUserSyncTest
# The test should assert CrmStagingRecord::where('source','google')->exists()
```

---

### INT_05 — GoogleAdmin → AssetEventConflictTable

**Data Transport Method:**  
`SyncGoogleChromebooksJob` → `GoogleWorkspaceService::syncChromebooks()` → fires `GoogleChromebookDiscovered` event → `GoogleChromebookDiscoveredListener` (AssetManagement) → upserts `AssetStagingRecord` with `source = 'google'`.

**Conflict Table Reconciliation Logic:**  
Same `ReconcileAssetsJob` as INT_02. Chrome devices are uniquely identified by Google's `deviceId`. If a serial number collision exists with an Action1-sourced record, the merge keeps the Action1 hardware spec but adds the Google `org_unit` and `annotated_user`.

**Laravel Artifacts:**

| File | Status |
|------|--------|
| `Modules/GoogleAdmin/Jobs/SyncGoogleChromebooksJob.php` | ✅ Exists |
| `Modules/GoogleAdmin/Events/GoogleChromebookDiscovered.php` | ✅ Exists |
| `Modules/AssetManagement/Listeners/GoogleChromebookDiscoveredListener.php` | ✅ Exists |
| `Modules/AssetManagement/Jobs/ReconcileAssetsJob.php` | ✅ Exists |
| `Modules/AssetManagement/Tests/Feature/GoogleChromebookDiscoveredListenerPestTest.php` | ✅ Exists |

**Verification Step:**
```bash
php artisan test --filter=GoogleChromebookDiscoveredListenerPestTest
php artisan test --filter=SyncGoogleChromebooksJobPestTest
```

---

### INT_06 — GoogleAdmin → SoftwareEventConflictTable

**Data Transport Method:**  
`SyncGoogleUsersJob` fetches assigned Google Workspace licenses via the Admin SDK. For each license assignment, it fires `GoogleLicenseDiscovered` event (carrying `GoogleLicenseDiscoveredData` DTO) → `ReconcileGoogleLicenseDiscovery` listener (SoftwareSubscriptions) → inserts into `software_discoveries` with `source = 'google'`.

**Conflict Table Reconciliation Logic:**  
Google SaaS licenses are matched against `software_products` by `sku_id`. `SoftwareReconciliationService` checks if a subscription allocation already exists; if not, it emits `UnrecognizedSoftwareDetected` for admin review.

**Laravel Artifacts:**

| File | Status |
|------|--------|
| `Modules/GoogleAdmin/Events/GoogleLicenseDiscovered.php` | ✅ Exists |
| `Modules/GoogleAdmin/DataTransferObjects/GoogleLicenseDiscoveredData.php` | ✅ Exists |
| `Modules/SoftwareSubscriptions/Listeners/ReconcileGoogleLicenseDiscovery.php` | ✅ Exists |
| `Modules/SoftwareSubscriptions/Models/SoftwareDiscovery.php` | ✅ Exists |
| `Modules/SoftwareSubscriptions/Tests/Feature/ReconcileGoogleLicenseDiscoveryPestTest.php` | ✅ Exists |

**Verification Step:**
```bash
php artisan test --filter=ReconcileGoogleLicenseDiscoveryPestTest
```

---

### RES_01 — UserEventConflictTable → CRM

**Data Transport Method:**  
Admin-triggered via `CrmStagingController` (web UI at `/crm/staging`). An admin reviews flagged `CrmStagingRecord` rows and approves/merges them. `ClientService::createOrUpdateFromStaging()` then promotes the record into a canonical `Client` + `Contact`, firing `ClientCreated` or `ClientUpdated` events.

**Reconciliation Logic Required:**  
The `StagingController` exists but the service method for promotion is likely embedded in `ClientService` without a named entrypoint. A dedicated `CrmStagingResolverService` should expose:
- `resolve(CrmStagingRecord $record, string $resolution): Client` — supports `merge_into_existing`, `create_new`, `discard`.
- Auto-resolve strategy: if `confidence_score >= 0.95`, auto-promote without admin review.

**Laravel Artifacts:**

| File | Status |
|------|--------|
| `Modules/Crm/Http/Controllers/StagingController.php` | ✅ Exists |
| `Modules/Crm/Models/CrmStagingRecord.php` | ✅ Exists |
| `Modules/Crm/Services/ClientService.php` | ✅ Exists |
| `Modules/Crm/Services/CrmStagingResolverService.php` | ❌ Missing |
| `Modules/Crm/Events/ClientCreated.php` | ✅ Exists |

**Verification Step:**
```bash
php artisan test --filter=CrmStagingResolverServiceTest
# Verify: given a staged record with confidence >= 0.95,
# a Client record is auto-created and the staging row is marked 'resolved'.
```

---

### RES_02 — AssetEventConflictTable → InventoryManager

**Data Transport Method:**  
`ReconcileAssetsJob` (queued, runs on schedule via `SyncAllDevices` command) reads all `AssetStagingRecord` rows in `pending` status, calls `AssetStatusService::promoteToActive()`, creates/updates `Asset` records, fires `AssetCountChanged`, and schedules `RecordDailyAssetCountJob`.

**Reconciliation Logic:**  
Merge strategy: serial number as primary dedup key. If `source = 'action1'` and `source = 'google'` for the same serial, merge preserving Action1 OS/hardware details, appending Google `org_unit` as a metadata field. Asset is marked `active` after promotion.

**Laravel Artifacts:**

| File | Status |
|------|--------|
| `Modules/AssetManagement/Jobs/ReconcileAssetsJob.php` | ✅ Exists |
| `Modules/AssetManagement/Services/AssetStatusService.php` | ✅ Exists |
| `Modules/AssetManagement/Services/AssetCounterService.php` | ✅ Exists |
| `Modules/AssetManagement/Entities/Asset.php` | ✅ Exists |
| `Modules/AssetManagement/Events/AssetCountChanged.php` | ✅ Exists |
| `Modules/AssetManagement/Tests/Unit/AssetCounterServicePestTest.php` | ✅ Exists |

**Verification Step:**
```bash
php artisan test --filter=AssetCounterServicePestTest
php artisan test Modules/AssetManagement/Tests/Integration/ConcurrentCounterPestTest.php
```

---

### RES_03 — SoftwareEventConflictTable → SoftwareManager

**Data Transport Method:**  
`SoftwareReconciliationService::reconcileFromDiscovery()` (called by both `ReconcileAction1SoftwareDiscovery` and `ReconcileGoogleLicenseDiscovery` listeners). Maps a `SoftwareDiscovery` to a `SoftwareProduct`. Emits `SoftwareReconciled` event. `SubscriptionCounterService` updates the `ClientSoftwareSubscription` active seat count.

**Reconciliation Logic:**  
Product matching priority: (1) exact `sku_id` match, (2) publisher + normalized name match, (3) fuzzy name match with confidence threshold. Unmatched records trigger `UnrecognizedSoftwareDetected` event for manual review.

**Laravel Artifacts:**

| File | Status |
|------|--------|
| `Modules/SoftwareSubscriptions/Services/SoftwareReconciliationService.php` | ✅ Exists |
| `Modules/SoftwareSubscriptions/Services/SubscriptionCounterService.php` | ✅ Exists |
| `Modules/SoftwareSubscriptions/Events/SoftwareReconciled.php` | ✅ Exists |
| `Modules/SoftwareSubscriptions/Events/UnrecognizedSoftwareDetected.php` | ✅ Exists |
| `Modules/SoftwareSubscriptions/Resolvers/SoftwareProductEntitlementResolver.php` | ✅ Exists |
| `Modules/SoftwareSubscriptions/Tests/Feature/SoftwareReconciliationServicePestTest.php` | ✅ Exists |

**Verification Step:**
```bash
php artisan test --filter=SoftwareReconciliationServicePestTest
php artisan reconcile:software-counters --dry-run
```

---

### BIZ_01 — CRM → PIB

**Data Transport Method:**  
Eloquent relationship. `PIB\Services\EntitlementEngineService` queries `Crm\Models\Client::activeContacts()->count()` (or a scoped query on `crm_contacts`) to build the per-user entitlement count for a billing run. No event is fired; this is a synchronous read at invoice generation time.

**PIB Calculation Logic Required:**  
The `EntitlementEngineService` must call a standardized `UserEntitlementCountProvider` interface rather than querying CRM models directly, to avoid cross-module Eloquent coupling. Current risk: `GenerateInvoiceJob` may be directly joining across module table boundaries.

**Laravel Artifacts:**

| File | Status |
|------|--------|
| `Modules/PIB/Services/EntitlementEngineService.php` | ✅ Exists |
| `Modules/PIB/Services/EntitlementService.php` | ✅ Exists |
| `Modules/PIB/Models/Entitlement.php` | ✅ Exists |
| `Modules/Crm/Models/Client.php` | ✅ Exists |
| `App\Contracts\UserEntitlementCountProvider` (interface) | ❌ Missing |
| `Modules/PIB/Tests/Feature/EntitlementServiceTest.php` | ✅ Exists |

**Verification Step:**
```bash
php artisan test --filter=EntitlementServiceTest
# Verify: given 5 active CRM contacts for a company,
# EntitlementEngineService returns billable_user_count = 5.
```

---

### BIZ_02 — InventoryManager → PIB

**Data Transport Method:**  
`AssetCounterService` maintains a `client_asset_counters` table with pre-aggregated counts by `company_id` and `asset_type`. `EntitlementEngineService` reads this counter table directly (not a live query), making it fast and safe for billing runs. `AssetCountChanged` event triggers counter refresh.

**PIB Calculation Logic:**  
Counter table stores `windowed_count` (active assets in the billing period). `EntitlementEngineService::getHardwareEntitlementCount(Company $company)` reads `client_asset_counters` filtered by `asset_type` and `billing_period`. The `asset_type` column (added in migration `2026_02_17`) enables per-type billing (workstations vs Chromebooks at different rates).

**Laravel Artifacts:**

| File | Status |
|------|--------|
| `Modules/AssetManagement/Services/AssetCounterService.php` | ✅ Exists |
| `Modules/AssetManagement/Jobs/RecordDailyAssetCountJob.php` | ✅ Exists |
| `Modules/AssetManagement/Events/AssetCountChanged.php` | ✅ Exists |
| `Modules/PIB/Services/EntitlementEngineService.php` | ✅ Exists |
| `Modules/AssetManagement/Tests/Unit/AssetCounterServicePestTest.php` | ✅ Exists |

**Verification Step:**
```bash
php artisan test Modules/AssetManagement/Tests/Integration/ConcurrentCounterPestTest.php
# Verify: AssetCountChanged causes client_asset_counters row to update;
# EntitlementEngineService reads the updated count in the same billing run.
```

---

### BIZ_03 — SoftwareManager → PIB

**Data Transport Method:**  
`SoftwareCountChanged` event (fired by `SubscriptionCounterService`) → `PIB\Listeners\AdjustBillingOnSoftwareCountChange` listener → updates `ServiceUsage` record for the software line item. Additionally, `SoftwareProductEntitlementResolver` is called by `EntitlementEngineService` at invoice generation time to compute billable seat counts per subscription block.

**PIB Calculation Logic:**  
Software billing uses block-size pricing (`block_size` column on `software_products`, added in `2026_02_10` migration). `SoftwareProductEntitlementResolver::resolve()` computes `ceil(active_seats / block_size) * block_price`. One-time licenses (`is_onetime = true`) are excluded from recurring runs.

**Laravel Artifacts:**

| File | Status |
|------|--------|
| `Modules/SoftwareSubscriptions/Events/SoftwareCountChanged.php` | ✅ Exists |
| `Modules/SoftwareSubscriptions/Services/SubscriptionCounterService.php` | ✅ Exists |
| `Modules/SoftwareSubscriptions/Resolvers/SoftwareProductEntitlementResolver.php` | ✅ Exists |
| `Modules/PIB/Listeners/AdjustBillingOnSoftwareCountChange.php` | ✅ Exists |
| `Modules/ContractManager/Listeners/AdjustBillingOnSoftwareCountChange.php` | ✅ Exists |
| `Modules/SoftwareSubscriptions/Tests/Unit/SubscriptionCounterServicePestTest.php` | ✅ Exists |

**Verification Step:**
```bash
php artisan test --filter=SubscriptionCounterServicePestTest
# Also verify block-size rounding:
php artisan test --filter=SoftwareProductEntitlementResolverTest
```

---

### BIZ_04 — ContractManager → PIB

**Data Transport Method:**  
`ContractManager\Console\ProcessExpirationsCommand` (scheduled via `app/Console/Kernel.php`) fires `BillingTemplateDue` event for each `BillingTemplate` whose `next_billing_date <= today`. `PIB\Listeners\BillingTemplateDueListener` handles this event and dispatches `GenerateInvoiceJob` onto the queue.

**PIB Calculation Logic:**  
`BillingTemplateDue` carries a `BillingTemplateDueData` DTO with `company_id`, `billing_template_id`, `billing_period_start`, `billing_period_end`. `GenerateInvoiceJob` calls `InvoiceGenerator::generate()`, which assembles line items from `EntitlementEngineService` (users + assets + software) and `BillingTemplate` base line items (fixed fees, managed services).

**Laravel Artifacts:**

| File | Status |
|------|--------|
| `Modules/ContractManager/Console/ProcessExpirationsCommand.php` | ✅ Exists |
| `Modules/ContractManager/Events/BillingTemplateDue.php` | ✅ Exists |
| `Modules/ContractManager/DataTransferObjects/BillingTemplateDueData.php` | ✅ Exists |
| `Modules/PIB/Listeners/BillingTemplateDueListener.php` | ✅ Exists |
| `Modules/PIB/Jobs/GenerateInvoiceJob.php` | ✅ Exists |
| `Modules/PIB/Console/GenerateInvoicesCommand.php` | ✅ Exists |
| `Modules/ContractManager/Tests/Feature/BillingTemplateInvoicePestTest.php` | ✅ Exists |

**Verification Step:**
```bash
php artisan pib:generate-invoices --dry-run
php artisan test --filter=BillingTemplateInvoicePestTest
```

---

### FIN_01 — PIB → InvoiceManager

**Data Transport Method:**  
`InvoiceGenerator::generate()` (called from `GenerateInvoiceJob`) creates a `PIB\Models\Invoice` with status `draft`, attaches `InvoiceLineItem` records, then fires `InvoiceGenerated` event. The Invoice model IS the InvoiceManager in this architecture (PIB owns invoicing). Views in `PIB/resources/views/invoices/` serve as the InvoiceManager UI.

**Implementation Logic:**  
Draft invoices are held for an `admin_review_window` (configurable) before being published. `InvoicePublished` event triggers downstream payment eligibility. `InvoiceUnusual` event fires if any line item exceeds a configurable anomaly threshold — handled by `Alerts\Listeners\InvoiceUnusualListener`.

**Laravel Artifacts:**

| File | Status |
|------|--------|
| `Modules/PIB/Services/InvoiceGenerator.php` | ✅ Exists |
| `Modules/PIB/Jobs/GenerateInvoiceJob.php` | ✅ Exists |
| `Modules/PIB/Jobs/GenerateRecurringInvoicesJob.php` | ✅ Exists |
| `Modules/PIB/Models/Invoice.php` | ✅ Exists |
| `Modules/PIB/Models/InvoiceLineItem.php` | ✅ Exists |
| `Modules/PIB/Events/InvoiceGenerated.php` | ✅ Exists |
| `Modules/PIB/Events/InvoicePublished.php` | ✅ Exists |
| `Modules/PIB/Events/InvoiceUnusual.php` | ✅ Exists |
| `Modules/Alerts/Listeners/InvoiceUnusualListener.php` | ✅ Exists |

**Verification Step:**
```bash
php artisan test --filter=BillingPreviewTest
php artisan test --filter=EntitlementScaleTest
# Verify: GenerateInvoiceJob creates an Invoice in 'draft' status
# with correct line items for a known BillingTemplate.
```

---

### FIN_02 — InvoiceManager → Payments

**Data Transport Method:**  
`Payment\Console\ProcessDueInvoicesCommand` (scheduled) queries `published` invoices past their due date and dispatches `ProcessDueInvoices` Job. `ProcessDueInvoices` iterates eligible invoices and dispatches one `ProcessInvoicePayment` Job per invoice. `HelcimService` (implementing `PaymentGateway` contract) executes the charge against the stored `PaymentMethod`.

**Implementation Logic:**  
The `PaymentGateway` contract (`Modules/Payment/Contracts/PaymentGateway.php`) enables swapping Helcim for another provider. On success: `PaymentSucceeded` event → `PIB::InvoicePaid` listener closes the invoice. On failure: `PaymentFailed` event → `Alerts::PaymentFailedListener` notifies admin. `ClientCreditService` applies any available credit balance before charging the gateway.

**Laravel Artifacts:**

| File | Status |
|------|--------|
| `Modules/Payment/Console/ProcessDueInvoicesCommand.php` | ✅ Exists |
| `Modules/Payment/Jobs/ProcessDueInvoices.php` | ✅ Exists |
| `Modules/Payment/Jobs/ProcessInvoicePayment.php` | ✅ Exists |
| `Modules/Payment/Services/HelcimService.php` | ✅ Exists |
| `Modules/Payment/Contracts/PaymentGateway.php` | ✅ Exists |
| `Modules/Payment/Services/ClientCreditService.php` | ✅ Exists |
| `Modules/Payment/Events/PaymentSucceeded.php` | ✅ Exists |
| `Modules/Payment/Events/PaymentFailed.php` | ✅ Exists |
| `Modules/Alerts/Listeners/PaymentFailedListener.php` | ✅ Exists |
| `Modules/Payment/Tests/Feature/PaymentProcessingPestTest.php` | ✅ Exists |
| `Modules/Payment/Tests/Feature/WebhookHandlingPestTest.php` | ✅ Exists |

**Verification Step:**
```bash
php artisan test --filter=PaymentProcessingPestTest
php artisan test --filter=WebhookHandlingPestTest
# Verify: ProcessInvoicePayment with TestGateway succeeds,
# Invoice status transitions to 'paid', Payment record is created.
```

---

### VW_01 — InventoryManager → vwAssets

**Data Transport Method:**  
`AssetController::index()` returns paginated `Asset` records scoped by authenticated company. `ClientPortal` registers an assets tab using `PortalTabRegistry`. View: `Modules/AssetManagement/resources/views/portal/index.blade.php`. `ClientAssetsWidget` provides a summary count on the CRM client profile.

**Laravel Artifacts:**

| File | Status |
|------|--------|
| `Modules/AssetManagement/Http/Controllers/AssetController.php` | ✅ Exists |
| `Modules/AssetManagement/resources/views/portal/index.blade.php` | ✅ Exists |
| `Modules/AssetManagement/Widgets/ClientAssetsWidget.php` | ✅ Exists |

**Verification Step:**
```bash
php artisan test --filter=AssetManagementPortalTest
# Verify: authenticated portal client can GET /portal/assets and sees
# only assets belonging to their company.
```

---

### VW_02 — CRM → vwUsers

**Data Transport Method:**  
`CrmController` / `ContactController` renders client and contact lists. Portal-side view uses `Modules/Crm/resources/views/portal/tickets.blade.php` for self-service. The admin CRM view at `/crm/clients` uses `ClientController`.

**Laravel Artifacts:**

| File | Status |
|------|--------|
| `Modules/Crm/Http/Controllers/ClientController.php` | ✅ Exists |
| `Modules/Crm/Http/Controllers/ContactController.php` | ✅ Exists |
| `Modules/Crm/resources/views/clients/index.blade.php` | ✅ Exists |
| `Modules/Crm/resources/views/clients/show.blade.php` | ✅ Exists |

**Verification Step:**
```bash
php artisan test --filter=CrmViewsTest
```

---

### VW_03 — ContractManager → vwContracts

**Data Transport Method:**  
`ContractController` serves admin views. `ClientPortal\ApprovalController` exposes the pending-approval flow for contract signatures. `vwContracts` = `Modules/ContractManager/resources/views/contracts/` + `ClientPortal/resources/views/approvals/`.

**Laravel Artifacts:**

| File | Status |
|------|--------|
| `Modules/ContractManager/Http/Controllers/ContractController.php` | ✅ Exists |
| `Modules/ContractManager/resources/views/contracts/index.blade.php` | ✅ Exists |
| `Modules/ClientPortal/Http/Controllers/ApprovalController.php` | ✅ Exists |
| `Modules/ClientPortal/resources/views/approvals/index.blade.php` | ✅ Exists |

**Verification Step:**
```bash
php artisan test --filter=ContractManagerViewsTest
```

---

### VW_04 — InvoiceManager → vwInvoices

**Data Transport Method:**  
`PIB\Http\Controllers\PortalInvoiceController` serves portal-side invoice list and detail. Admin side uses `PIB\Http\Controllers\InvoiceController`. `InvoicesWidget` renders a summary on the CRM client profile. `ClientPortal\InvoiceController` provides the portal entry point.

**Laravel Artifacts:**

| File | Status |
|------|--------|
| `Modules/PIB/Http/Controllers/PortalInvoiceController.php` | ✅ Exists |
| `Modules/PIB/Http/Controllers/InvoiceController.php` | ✅ Exists |
| `Modules/PIB/resources/views/invoices/index.blade.php` | ✅ Exists |
| `Modules/PIB/resources/views/portal/index.blade.php` | ✅ Exists |
| `Modules/ClientPortal/Http/Controllers/InvoiceController.php` | ✅ Exists |
| `Modules/PIB/Widgets/InvoicesWidget.php` | ✅ Exists |

**Verification Step:**
```bash
php artisan test --filter=PibViewsTest
# Verify: portal client can view own invoices but not other company's.
```

---

### VW_05 — Payments → vwPayments

**Data Transport Method:**  
`Payment\View\Components\PaymentHistory` Blade component renders payment history for any company context. `ClientPortal\resources\views\tabs\payments.blade.php` includes the component. Raw payment data served by `HelcimWebhookController` (inbound) and `ClientPaymentController` (portal-side).

**Laravel Artifacts:**

| File | Status |
|------|--------|
| `Modules/Payment/View/Components/PaymentHistory.php` | ✅ Exists |
| `Modules/Payment/resources/views/components/payment-history.blade.php` | ✅ Exists |
| `Modules/ClientPortal/resources/views/tabs/payments.blade.php` | ✅ Exists |
| `Modules/ClientPortal/Http/Controllers/ClientPaymentController.php` | ✅ Exists |

**Verification Step:**
```bash
php artisan test --filter=PaymentModuleCorePestTest
```

---

### ACT_01 — xUsers → GoogleAdmin (Bidirectional Write-back)

**Data Transport Method:**  
Portal admin initiates a provision/deprovision action. Should route through a `UserProvisioningController` in `ClientPortal` → calls `GoogleWorkspaceService::provisionUser()` or `::deprovisionUser()`. `GoogleWorkspaceService` calls the Google Admin SDK. Result fires `GoogleUserChanged` or `GoogleUserDeleted`.

**Implementation Logic Required:**  
`GoogleWorkspaceService` already has sync capabilities but lacks explicit `provisionUser(string $email, string $orgUnit)` and `deprovisionUser(string $userId)` public methods that can be triggered from a portal action. A dedicated `ProvisionUserAction` class using the Actions pattern (per `app/Actions/`) would be appropriate.

**Laravel Artifacts:**

| File | Status |
|------|--------|
| `Modules/GoogleAdmin/Services/GoogleWorkspaceService.php` | ✅ Exists |
| `Modules/GoogleAdmin/Events/GoogleUserChanged.php` | ✅ Exists |
| `Modules/GoogleAdmin/Events/GoogleUserDeleted.php` | ✅ Exists |
| `Modules/ClientPortal/Http/Controllers/UserProvisioningController.php` | ❌ Missing |
| `App\Actions\ProvisionPortalUserAction.php` | ❌ Missing |
| `App\Actions\DeprovisionPortalUserAction.php` | ❌ Missing |
| `Modules/GoogleAdmin/Tests/Feature/SyncGoogleUsersJobPestTest.php` | ✅ Exists |

**Verification Step:**
```bash
# Once implemented:
php artisan test --filter=UserProvisioningControllerTest
# Verify: POST /portal/users/{user}/provision dispatches GoogleWorkspaceService::provisionUser(),
# and Google Admin SDK call is mocked and asserted.
```

---

### ACT_02 — xContracts → ContractManager (Bidirectional: Contract Approval)

**Data Transport Method:**  
Portal client signs/approves a quote via `ClientPortal\ApprovalController::approve()`. This updates `ApprovalRequest` status to `approved`, fires `ApprovalApproved` event → `ContractManager\Listeners\CreateContractFromApprovedQuote` creates a `Contract` from the parent `Quote`, fires `ContractActivated` event → `ContractManager\Listeners\LogContractActivity` records the audit trail.

**Implementation Logic:**  
Quote-to-Contract promotion: `QuoteService::promoteToContract(Quote $quote): Contract` copies line items, sets `start_date`, links back to the `Quote` as provenance. `BillingTemplate` is created from the contract terms. This is the entry point for the BIZ_04 edge.

**Laravel Artifacts:**

| File | Status |
|------|--------|
| `Modules/ClientPortal/Http/Controllers/ApprovalController.php` | ✅ Exists |
| `Modules/ClientPortal/Events/ApprovalApproved.php` | ✅ Exists |
| `Modules/ContractManager/Listeners/CreateContractFromApprovedQuote.php` | ✅ Exists |
| `Modules/ContractManager/Events/ContractActivated.php` | ✅ Exists |
| `Modules/ContractManager/Services/QuoteService.php` | ✅ Exists |
| `Modules/ContractManager/Models/Contract.php` | ✅ Exists |

**Verification Step:**
```bash
php artisan test --filter=ContractManagerViewsTest
# Verify: ApprovalRequest approval triggers Contract creation
# and BillingTemplate is seeded with correct billing_cycle.
```

---

### ACT_03 — xPayments → Payments (Bidirectional: Initiate Payment)

**Data Transport Method:**  
Portal client submits payment via `ClientPortal\ClientPaymentController::store()` → validates `PaymentMethod` ownership → dispatches `ProcessInvoicePayment` Job → `HelcimService::charge()` executes. On webhook confirmation: `HelcimWebhookController::handlePaymentSuccess()` fires `PaymentSucceeded`.

**Implementation Logic:**  
The portal pay flow (`PIB/resources/views/portal/pay.blade.php`) collects payment method selection or entry of new Helcim card token. `ClientCreditService` checks for applicable credit balance and reduces the charge amount before hitting the gateway. `PaymentDisputedData` DTO handles chargeback scenarios from Helcim webhooks.

**Laravel Artifacts:**

| File | Status |
|------|--------|
| `Modules/ClientPortal/Http/Controllers/ClientPaymentController.php` | ✅ Exists |
| `Modules/Payment/Jobs/ProcessInvoicePayment.php` | ✅ Exists |
| `Modules/Payment/Services/HelcimService.php` | ✅ Exists |
| `Modules/Payment/Http/Controllers/HelcimWebhookController.php` | ✅ Exists |
| `Modules/Payment/Services/ClientCreditService.php` | ✅ Exists |
| `Modules/Payment/Tests/Feature/PaymentProcessingPestTest.php` | ✅ Exists |

**Verification Step:**
```bash
php artisan test --filter=PaymentProcessingPestTest
# Verify: portal pay flow with TestGateway creates Payment record,
# reduces Invoice balance to zero, and fires PaymentSucceeded.
```

---

### ACT_04 — xInvoices → InvoiceManager (Bidirectional: Dispute Invoice)

**Data Transport Method:**  
Portal client clicks "Dispute" via `ClientPortal\InvoiceController::dispute()`. This should transition `Invoice.status` to `disputed`, create a `BillingAdjustment` record of type `dispute`, and optionally open a FreeScout support ticket. `PaymentDisputedListener` in PIB handles downstream holds.

**Implementation Logic Required:**  
`InvoiceController::dispute()` likely exists in `ClientPortal` but the state machine transition (`published` → `disputed`) needs an explicit guard in `InvoicePolicy` and the `Invoice` model. A `DisputeInvoiceAction` class should:
1. Enforce `InvoicePolicy::dispute()` — only the owning client can dispute.
2. Create a `BillingAdjustment` with `type = 'dispute'`.
3. Fire a `PaymentDisputed` event to halt any pending auto-pay for that invoice.
4. Notify the admin (via Alerts module).

**Laravel Artifacts:**

| File | Status |
|------|--------|
| `Modules/PIB/Http/Controllers/BillingAdjustmentController.php` | ✅ Exists |
| `Modules/PIB/Models/BillingAdjustment.php` | ✅ Exists |
| `Modules/PIB/Policies/InvoicePolicy.php` | ✅ Exists |
| `Modules/PIB/Listeners/PaymentDisputedListener.php` | ✅ Exists |
| `Modules/Payment/Events/PaymentDisputed.php` | ✅ Exists |
| `Modules/ClientPortal/Http/Controllers/InvoiceController.php` | ✅ Exists |
| `App\Actions\DisputeInvoiceAction.php` | ❌ Missing |
| `Modules/PIB/Tests/Unit/PaymentDisputedListenerPestTest.php` | ✅ Exists |
| `Modules/PIB/Tests/Feature/DisputeInvoiceActionTest.php` | ❌ Missing |

**Verification Step:**
```bash
# Once implemented:
php artisan test --filter=DisputeInvoiceActionTest
# Verify: portal client can PUT /portal/invoices/{invoice}/dispute,
# Invoice.status becomes 'disputed', BillingAdjustment is created,
# and ProcessInvoicePayment job does NOT run for the disputed invoice.
```

---

## Gap Summary & Recommended Priority

| Priority | Gap | Suggested Artifact | Blocking Edge |
|----------|-----|--------------------|---------------|
| 🔴 High | Cross-source user identity dedup | `Modules/Crm/Services/UserIdentityConflictResolver.php` | INT_01, INT_04, RES_01 |
| 🔴 High | Portal user provisioning write-back | `Modules/ClientPortal/Http/Controllers/UserProvisioningController.php` + Actions | ACT_01 |
| 🟡 Medium | Invoice dispute state machine | `App\Actions\DisputeInvoiceAction.php` + test | ACT_04 |
| 🟡 Medium | Entitlement count abstraction | `App\Contracts\UserEntitlementCountProvider` interface | BIZ_01 |
| 🟢 Low | CRM staging auto-resolve service | `Modules/Crm/Services/CrmStagingResolverService.php` | RES_01 |
