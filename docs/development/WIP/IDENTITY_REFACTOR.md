# Identity Refactor — Remaining Work

> **Last Updated:** 2026-03-11  
> **Context:** Phases 1, 2.5, and 2.7 are complete. `ClientUser` is removed from
> all runtime logic. Auth, controllers, policies, services, and feature tests all
> use `User` + `company_user` pivot. What remains is cleaning up the data model
> and completing the rename at the schema level.

---

## Phase 2 — Deprecate `Customer` & Refactor Inbound Mail

**Goal:** Stop auto-creating `Customer` records for cold inbound traffic. Link
inbound threads to a `user_id` instead.

- [ ] Add `sender_email`, `sender_name`, `user_id` (nullable FK) to `conversations`, `threads`, and `emails` tables via migration.
- [ ] Refactor `ImapService`: remove automatic `Customer::firstOrCreate()` / `Customer::create()`. On inbound, attempt to match `sender_email` to an existing `User`; store matched `user_id` or leave null for unknown senders.
- [ ] Data migration: identify `Customer` records that correspond to real portal `User`s; link `user_id` on their threads; mark the rest as unmapped.
- [ ] Drop `App\Models\Customer`, `customer_channels` table, and all related relations once migration is confirmed clean.

---

## Phase 3 — Global `Client` → `Company` Rename

**Goal:** `Modules\Crm\Models\Company` is already in use. `Client` is a legacy
parallel. Remove the parallel.

**Scope:** ~107 files across `Modules/` and `app/` still import `use Modules\Crm\Models\Client`. `app/Models/` is already clean.

### Schema
- [ ] Migration: rename `clients` table → `companies`.
- [ ] Cascade: rename `client_id` → `company_id` on all downstream tables: `pib_invoices`, `contracts`, `subscriptions`, `assets`, `approval_requests`, `google_configs`, `asset_staging_records`, and any others containing `client_id`.
- [ ] Update all `belongsTo` / `hasMany` FK references in Eloquent models to match the renamed column.

### Codebase sweep (after schema migration)
- [ ] Replace `use Modules\Crm\Models\Client` with `Company` across ~107 files.
- [ ] Update API endpoints and route model binding that reference `{client}` parameter.
- [ ] Rewrite seeders using `ClientUser::factory()`:
  - `database/seeders/ClientPortalTestSeeder.php` (3 refs → `User::factory()` + company pivot)
  - `database/seeders/DemoUserSeeder.php` (2 refs → `User::create()` + company pivot)
- [ ] Update `routes/web.php` lines 356 and 376: replace `$client->users()->first()` legacy bridge with `$company->users()->first()`.
- [ ] Refactor 14 browser test files (~42 `ClientUser::factory()` references):

| File | Refs |
|---|---|
| `tests/Browser/Billing/AssetCreditLedgerPestTest.php` | 2 |
| `tests/Browser/Billing/PaymentProcessingPestTest.php` | 3 |
| `tests/Browser/BillingCyclePestTest.php` | 2 |
| `tests/Browser/ClientApprovalPestTest.php` | 3 |
| `tests/Browser/ClientPortal/PortalInvoiceBrowserTest.php` | 3 |
| `tests/Browser/ClientPortal/PortalPaymentMethodPestTest.php` | 1 |
| `tests/Browser/Commerce/QuoteApprovalPestTest.php` | 1 |
| `tests/Browser/ContractInvoiceGenerationPestTest.php` | 2 |
| `tests/Browser/Debug/TicketDebugPestTest.php` | 4 |
| `tests/Browser/Helpdesk/ClientTicketInteractionPestTest.php` | 5 |
| `tests/Browser/MultiUserWorkflowsPestTest.php` | 3 |
| `tests/Browser/Portal/PortalAccessPestTest.php` | 3 |
| `tests/Browser/Portal/PortalFeaturesPestTest.php` | 6 |
| `tests/Browser/RBACSecurityPestTest.php` | 4 |

### Cleanup (final step)
- [ ] Delete `Modules/Crm/Models/ClientUser.php` and `Modules/Crm/Database/Factories/ClientUserFactory.php`.
- [ ] Delete `Modules/Crm/Models/Client.php`.
- [ ] Verify `User::STATUS_ACTIVE/INACTIVE/DELETED` int constants are consistent across all modules (replace any string `is_active` boolean checks).
