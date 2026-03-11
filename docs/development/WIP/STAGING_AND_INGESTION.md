# Staging & Data Ingestion — Remaining Work

> **Last Updated:** 2026-03-11  
> **Context:** The ingestion pipeline architecture is defined (see `FinOps Data Edges.json`
> for the data-flow map). External sources (Action1 RMM, Google Admin) push identity,
> asset, and software data into staging conflict tables. Admins resolve conflicts in a
> review UI before data is promoted to CRM/Inventory. The CRM staging backend is
> complete. The pieces still missing are: routes + UI wiring for CRM staging, the full
> Asset staging schema refactor, the Google→PIB counter listeners, and lifecycle tests.

---

## 1. CRM Staging UI — Wire the Existing Controller

**Controller:** `Modules/Crm/Http/Controllers/StagingController.php` — fully implemented.  
Methods: `index()`, `list()` (paginated JSON), `resolve(int $id, string $action)` (create / update / ignore).

- [ ] Add routes in `Modules/Crm/Routes/web.php`:
  ```php
  Route::get('/crm/staging',                    [StagingController::class, 'index'])->name('crm.staging.index');
  Route::get('/crm/staging/list',               [StagingController::class, 'list'])->name('crm.staging.list');
  Route::post('/crm/staging/{id}/{action}',      [StagingController::class, 'resolve'])->name('crm.staging.resolve');
  ```
- [ ] Create `Modules/Crm/Resources/views/staging/index.blade.php`:
  - Blade shell with a `<table id="staging-table">` placeholder.
  - JS Fetch on page load: `GET /crm/staging/list` → render rows.
  - Action buttons per row: **Quick Create** (`action=create`), **Map to Existing** (`action=update` + customer search), **Merge** (`action=update` + proposed-change preview), **Ignore** (`action=ignore`).
  - On resolve: `POST /crm/staging/{id}/{action}` → refresh row.
- [ ] Add navigation link to the staging inbox — remove the inline conflict list from `Action1` and `GoogleAdmin` integration settings pages and replace with a link to `/crm/staging`.
- [ ] Add `event(new ContactCreated(...))` in `StagingController::createCustomerFromStaging()` after `$customer->save()` so the Google→PIB counter flow is triggered on staging approval.

---

## 2. Asset Staging — Schema Refactor & Alignment

**Current state:** `Modules/AssetManagement/Entities/AssetStagingRecord.php` exists and handles
conflict-staging for *known* assets (requires non-nullable `asset_id` FK). It cannot represent
"Potential New" assets discovered by external sync.

- [ ] Migration: make `asset_staging_records.asset_id` nullable (currently required FK).
- [ ] Migration: add `resolution_type` enum column (`create`, `merge`, `ignore`) — nullable until resolved.
- [ ] Migration: add nullable `target_asset_id` FK column for admin-directed merge mapping.
- [ ] Update `AssetStagingRecord` model `$fillable`, `$casts`, and docblock to match new columns.
- [ ] Refactor `AssetManagement` discovery job: on **no match**, create a `pending_review`
  staging record with `asset_id = null` (type: `Potential New`) instead of auto-creating.
- [ ] Admin UI at `/assets/staging`: reuse CRM staging UI pattern — list `pending_review` records,
  actions: **Create New Asset**, **Map to Existing Asset**, **Ignore**.

---

## 3. Google → PIB User Counter — Missing Listeners

**Gap:** When a staging record is approved and a Customer is created, the CRM fires `ContactCreated`.
PIB has no listeners registered for this event, so per-user billing counters are never incremented.
Similarly, `UserStatusChanged` (fired from `UserObserver::updated()`) has no PIB decrement handler.

- [ ] Create `Modules/PIB/Listeners/UpdateClientUserCounter.php`:
  - Listens to `ContactCreated` event.
  - Increments the company's active user counter entitlement.
  - Use `ResilientListener` trait (3 retries, 30s backoff).
- [ ] Create `Modules/PIB/Listeners/DecrementClientUserCounter.php`:
  - Listens to `UserStatusChanged` event (filter: status changed to `STATUS_INACTIVE`).
  - Decrements the company's active user counter entitlement.
  - Use `ResilientListener` trait.
- [ ] Register both listeners in `Modules/PIB/Providers/PIBServiceProvider.php` `$listen` array.
- [ ] Write a backfill command/seeder to populate existing company user counts from current active users.

---

## 4. Company Ingestion — Validation & Lifecycle

- [ ] **Company creation domain check**: when a company is created via automated ingestion
  (Google Admin sync or staging approval), validate that the domain of the user's email matches
  an existing `Company.domain`. If no match, require admin confirmation before creating a new company.
- [ ] **Promote Unknown Sender to Portal User**: build an admin action (within CRM or the staging
  inbox) that upgrades an auto-created `Customer` (inbound email sender with no portal account)
  to a full portal `User` with company pivot attachment. This bridges Phase 2 (Customer deprecation)
  with the staging resolution flow.

---

## 5. Lifecycle Tests

- [ ] `tests/Browser/Lifecycle/UserIngestionTest.php` — full E2E: Google sync → staging record created → admin resolves → counter incremented in PIB.
- [ ] `tests/Browser/Lifecycle/AssetMappingTest.php` — full E2E: RMM sync → asset staging record (Potential New) → admin maps to existing → asset updated.
