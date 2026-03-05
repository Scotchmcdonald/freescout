# Identity Refactor Tracking Document

This document tracks the progress of the system-wide Identity, Access, and Company Model redesign.

> **Last Updated:** 2026-03-04

---

## Phase 1: Unifying Authentication (`ClientUser` → `User`)
**Status:** ✅ Completed
**Goal:** Stop the bleed of split identities and unify the login guards.

### Tasks
- [x] Analyze `users` and `client_users` schemas to map fields.
- [x] Create a database migration to merge `client_users` into the `users` table.
- [x] Create `company_user` pivot table schema.
- [x] Write data migration logic (move users, assign RBAC roles, establish company link).
- [x] Update Auth configuration (`config/auth.php`) to strip `client` and `client_users` guards.
- [x] Update `ClientPortal` controllers to use standard web guard and RBAC.
- [x] Drop the `client_users` table and `ClientUser` model. (Ready for PR/removal)

---

## Phase 2: Deprecating `Customer` & Refactoring Inbound Mail
**Status:** Pending
**Goal:** Stop polluting the database with cold inbound traffic.

### Tasks
- [ ] Modify `conversations`, `threads`, and `emails` tables (add `sender_email`, `sender_name`, `user_id`).
- [ ] Refactor `ImapService` to remove automatic `Customer::create()` and link to `user_id` instead.
- [ ] Data Migration: Move valid `Customer` records to `users` and update ticket references.
- [ ] Drop `App\Models\Customer`, `customer_channels`, and related tables.

---

## Phase 2.5: ClientPortal Controller Refactor (`$user->client` → `$user->company()`)
**Status:** ✅ Completed
**Goal:** Decouple all ClientPortal controllers from the legacy `Client` model, using `User` + `Company` exclusively.

### Summary
All ClientPortal controllers, middleware, and integration tests now use the unified `User` model
with `$user->company()` (returning a `Modules\Crm\Models\Company`) instead of `$clientUser->client`.

### Completed Tasks
- [x] Add `company()` helper method to `App\Models\User` (returns first attached Company via `company_user` pivot).
- [x] Fix `companies()` BelongsToMany pivot to only reference existing columns (`role_id`, `status`).
- [x] Refactor `InvoiceController` — all methods use `$user->company()`, comparisons use `company_id`.
- [x] Refactor `PortalController` — renamed `getClientSummary()` → `getCompanySummary()`, replaced `Client` import with `Company`.
- [x] Refactor `ClientPaymentController` — payment flows use Company directly, events pass `$company->id`.
- [x] Refactor `SupportController` — ticket queries use `$company->id` instead of `$client->id`.
- [x] Refactor `ApprovalController` — `$user->client_id` → `$user->company_id`.
- [x] Refactor `UserProvisioningController` — GoogleConfig lookups use `$user->company_id`.
- [x] Refactor `EnsureClientIsActive` middleware — uses `$user->company()->is_active`.
- [x] Update `PortalInvoiceFlowTest` — removed `Client` factory, uses `Company` + `company_user` pivot. All 11 tests pass.

### Notes
- DB columns like `approval_requests.client_id` and `google_configs.client_id` still exist — will be renamed in Phase 3.
- `pib_invoices` has both `client_id` and `company_id` — controllers now read `company_id` exclusively.
- `User::getClientIdAttribute()` is deprecated and delegates to `company_id`.

---

## Phase 2.7: Eliminate `ClientUser` from Policies, Services, and Feature Tests
**Status:** ✅ Completed (2026-03-04)
**Goal:** Remove all runtime `ClientUser` usage from policies, services, commands, and feature tests. After this phase, `ClientUser` is only referenced by: the legacy model file itself, the factory, seeders, docblock comments, and browser tests.

### Migration Fix
- [x] Fix `company_user` pivot table — RBAC migration creates it first with minimal columns; updated merge migration (`2026_03_03_234842`) to ALTER TABLE in the `else` branch, adding `is_primary`, `client_id`, `manager_id`, `is_approver`, `approval_limit` with `Schema::hasColumn()` guards.

### User Model Updates
- [x] `User::companies()` — restored full `withPivot` with all 6 columns: `role_id`, `status`, `is_primary`, `is_approver`, `approval_limit`, `manager_id`.
- [x] `User::company()` — now uses `wherePivot('is_primary', true)` with fallback to `first()`.

### Policy Refactoring (5 files)
- [x] `app/Policies/ClientUserPolicy.php` — Replaced `User|ClientUser` union types with `User`, renamed `$clientUser` params to `$targetUser`. Added deprecation docblock.
- [x] `app/Policies/ClientPolicy.php` — Removed `ClientUser` import, replaced union types, uses `$user->isClient()`, changed `$user->client_id` → `$user->company_id`.
- [x] `Modules/PIB/Policies/InvoicePolicy.php` — Full rewrite: removed `ClientUser`, uses `$user->isClient()` + `$user->company()` for ownership checks.
- [x] `Modules/AssetManagement/Policies/AssetPolicy.php` — Removed `ClientUser` union types.
- [x] `Modules/KnowledgeBase/app/Policies/ArticlePolicy.php` — Replaced `instanceof ClientUser` with `$user->isClient()`, uses `$user->company()` for Contact lookups.

### Service/Command Refactoring (4 files)
- [x] `app/Providers/AppServiceProvider.php` — Changed `Gate::policy(ClientUser::class, ...)` to `Gate::policy(User::class, ClientUserPolicy::class)`.
- [x] `app/Console/Commands/PruneDemoAccounts.php` — Replaced `ClientUser::where()` with `$company->users()->detach()` + conditional user deletion.
- [x] `app/Actions/DisputeInvoiceAction.php` — Docblocks updated (ClientUser references removed).
- [x] `Modules/KnowledgeBase/app/Services/DemoAccountService.php` — `createPortalDemoAccount()` now uses `User::create()` + `$company->users()->attach()` with `is_primary => true`.

### Additional Application Code Updates
- [x] `app/Http/Requests/StoreConversationRequest.php` — Changed `$client->users()->first()` (ClientUser) to `$client->company?->users()->first()` (User).
- [x] `Modules/KnowledgeBase/app/Http/Controllers/ArticleController.php` — Changed `instanceof \App\Models\User` to `$user->isClient()`.
- [x] `Modules/ClientPortal/Models/ApprovalRequest.php` — `scopeMyApprovals`: `$user->client_id` → `$user->company_id`.

### Observer Enhancement
- [x] `app/Observers/UserObserver.php` — Added `updated()` method that dispatches `UserStatusChanged` event when `status` field changes (migrated from `ClientUser::booted()`).

### Feature Test Refactoring (3 files)
- [x] `Modules/ClientPortal/Tests/Feature/ClientAuthenticationPestTest.php` — Full rewrite: `ClientUser::factory()` → `User::factory()` + Company pivot. Helper `createPortalUser()`. Guest redirect asserts `route('login')` (standard auth middleware). Dashboard test mocks `PortalTabRegistry` to avoid module tab views needing seeded data. **6 pass, 1 skipped.**
- [x] `Modules/PIB/Tests/Feature/DisputeInvoiceActionPestTest.php` — Replaced `ClientUser::factory()` with `User::factory()` + company pivot. **15 pass.**
- [x] `Modules/Crm/Tests/Unit/CrmEventDispatchPestTest.php` — Replaced `ClientUser::create()` with `User::factory()` + company pivot, uses `User::STATUS_INACTIVE`. **7 pass.**

### Test Verification
All 4 refactored test files pass in a single combined run:
```
Tests: 1 skipped, 39 passed (90 assertions) — Duration: 3.95s
```

---

## Remaining `ClientUser` References (as of 2026-03-04)

### Deprecation Comments Only (no runtime usage)
| File | Nature |
|------|--------|
| `app/Policies/ClientUserPolicy.php` | Class name + deprecation docblock |
| `app/Providers/AppServiceProvider.php` | Import of policy class + comment |
| `app/Console/Commands/PruneDemoAccounts.php` | Method name `cleanupClientUsers` (naming only) |
| `app/Observers/UserObserver.php` | Comment referencing migration from `ClientUser::booted()` |

### Legacy Model Files (pending Phase 3 removal)
| File | Nature |
|------|--------|
| `Modules/Crm/Models/ClientUser.php` | The deprecated model itself |
| `Modules/Crm/Database/Factories/ClientUserFactory.php` | Factory for deprecated model |

### Seeders (still create `ClientUser` instances)
| File | Refs | Action Needed |
|------|------|---------------|
| `database/seeders/ClientPortalTestSeeder.php` | 3 | Rewrite to use `User::factory()` + company pivot |
| `database/seeders/DemoUserSeeder.php` | 2 | Rewrite to use `User::create()` + company pivot |

### Routes (legacy bridge code)
| File | Refs | Nature |
|------|------|--------|
| `routes/web.php` (lines 356, 376) | 2 | Docblock + `$client->users()->first()` in legacy client-edit bridge routes |

### Browser Tests (14 files, ~42 references total)
| File | Refs |
|------|------|
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

### Migration Files (historical, do not edit)
| File | Nature |
|------|--------|
| `database/migrations/2026_03_03_234842_merge_client_users_into_users_and_create_company_user_pivot.php` | Data migration (references source table) |
| `Modules/Crm/Database/Migrations/2025_01_01_000000_create_crm_tables.php` | Original table creation |
| `Modules/ClientPortal/Database/Migrations/2026_01_15_000001_add_auth_fields_to_client_users.php` | Historical migration |

---

## Phase 3: The Great `Company` Migration (`Client` → `Company`)
**Status:** Pending
**Goal:** Rename the business entity globally without breaking foreign keys.

### Scope Summary
- 163 files import `use Modules\Crm\Models\Client;` — these will need `Client` → `Company` updates.
- `Modules\Crm\Models\Company` already exists and is in use by refactored code.
- `Client` model remains as a parallel until the rename migration is executed.

### Tasks
- [ ] Schema Migration: Rename `clients` table to `companies`.
- [ ] Cascade rename `client_id` to `company_id` across all modular tables (PIB, Contracts, Subscriptions, Assets, etc.).
- [ ] Codebase Sweep: Replace `use Modules\Crm\Models\Client` with `Company` across 163 files.
- [ ] Update API endpoints and Route parameter bindings.
- [ ] Refactor seeders: `ClientPortalTestSeeder`, `DemoUserSeeder` to use `User` + `Company`.
- [ ] Refactor browser tests: 14 files (~42 refs) using `ClientUser::factory()`.
- [ ] Update `routes/web.php` legacy client-edit bridge routes.
- [ ] Remove `Modules\Crm\Models\ClientUser` model and factory.
- [ ] Remove `Modules\Crm\Models\Client` model (replaced by `Company`).
