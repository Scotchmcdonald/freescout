# Testing Suite Refactor Checklist (Execution Tracker)

Date: 2026-03-17
Related plan: `docs/development/WIP/TESTING_SUITE_REFACTOR_PLAN_2026-03-17.md`
Owner: QA/Architecture
Status: Not started

Use this file as the operational checklist while implementing the refactor plan.

---

## 0) Session Prep

- [ ] Read full plan file before touching tests.
- [ ] Capture baseline runtime (`php artisan test`) and note timestamped report file.
- [ ] Create branch for refactor stream.
- [ ] Confirm no unrelated dirty changes will be mixed into commits.

Notes:
- Baseline report path:
- Baseline runtime summary:

---

## 1) WS-A Foundation Split (Unit vs DB-backed)

### Core files

- [ ] `tests/TestCase.php`
  - [ ] Remove global `RefreshDatabase` from base unless intentionally needed for all tests.
  - [ ] Keep shared safety setup (HTTP stray prevention, filesystem/mail fakes) where appropriate.
- [ ] `tests/UnitTestCase.php`
  - [ ] Ensure unit base is DB-free.
- [ ] `tests/IntegrationTestCase.php`
  - [ ] Ensure DB reset trait is attached here (or equivalent integration base).
- [ ] `tests/Pest.php`
  - [ ] Verify global Pest trait registration does not force DB reset into unit tests.
- [ ] `Modules/*/Tests/Pest.php` (all module-level Pest bootstrap files)
  - [ ] Verify module bootstrap does not force DB reset into unit paths.

### Validation

- [ ] `php artisan test tests/Unit`
- [ ] `php artisan test tests/Integration`
- [ ] Check latest report log for migration/reset behavior and unexpected failures.

### Exit criteria

- [ ] Unit test base confirmed DB-free.
- [ ] Integration tests still deterministic and isolated.

---

## 2) WS-B Junk Test Removal / Consolidation

### Framework-existence tests

- [ ] `tests/Feature/InterfaceSegregationTest.php`
  - [ ] Remove tests that only assert `interface_exists`/`method_exists`.
  - [ ] Keep/replace with behavior contract tests only.
- [ ] `tests/Unit/Console/Commands/KernelAndEdgeCasesTest.php`
  - [ ] Remove class/method existence and singleton container internals checks that do not validate app behavior.
- [ ] `tests/Unit/Console/KernelTest.php`
  - [ ] Remove reflection/existence-only checks; keep command behavior assertions only.

### Placeholder assertions

- [ ] `tests/Unit/Services/ImapServiceProcessMessageAdvancedTest.php`
  - [ ] Remove/replace any `assertTrue(true)` and similar pass-through assertions.
- [ ] Global sweep: `assertTrue(true)`
  - [ ] Replace with behavior assertion or delete test.

### Relationship/type internals overuse

- [ ] `tests/Integration/Models/ModelRelationshipsTest.php`
  - [ ] Trim relation-type tests that only verify Eloquent wiring.
- [ ] `tests/Integration/Models/ThreadTest.php`
  - [ ] Remove low-signal framework assertions (relation class type, timestamp non-null-only checks).

### Validation

- [ ] Run focused suites for touched files.
- [ ] Run `php artisan test` once after this batch.

### Exit criteria

- [ ] No pure framework-existence tests remain.
- [ ] No placeholder pass assertions remain.

---

## 3) WS-C Layer Correction (DB-heavy Unit -> Integration/Feature)

### High-priority root files

- [ ] `tests/Unit/Services/ImapServiceProcessMessageBasicTest.php`
  - [ ] Split pure parser/business logic vs DB-backed flow.
  - [ ] Move DB-backed portions to integration tests.
- [ ] `tests/Unit/Services/ImapServiceProcessMessageAdvancedTest.php`
  - [ ] Same split strategy; keep pure unit coverage for deterministic logic.
- [ ] `tests/Unit/Controllers/ConversationControllerTest.php`
  - [ ] Convert controller-internal tests to HTTP boundary feature tests where possible.
- [ ] `tests/Integration/ControllerCoverageTest.php`
  - [ ] Break into smaller concern-focused feature/integration files.

### High-priority module files

- [ ] `Modules/PIB/Tests/Unit/ClientCreditServicePestTest.php`
  - [ ] Split arithmetic/business logic unit tests from DB-backed persistence tests.
- [ ] `Modules/CaseManager/Tests/Unit/Services/RmmBridgeServiceTest.php`
  - [ ] Remove unit-layer DB writes; move integrated flows to integration tests.
- [ ] `Modules/*/Tests/Unit/**/*` using RefreshDatabase
  - [ ] Migrate each violating test to proper layer or remove DB dependency.

### Validation

- [ ] `rg "RefreshDatabase|DB::table\(" tests/Unit Modules/*/Tests/Unit`
- [ ] `php artisan test tests/Unit`

### Exit criteria

- [ ] Unit folders are DB-free (or only documented temporary exceptions).

---

## 4) WS-D Module Isolation Hardening

### Guard files

- [ ] `tests/Unit/ModuleUnitIsolationGuardTest.php`
  - [ ] Review allowlisted module prefixes.
  - [ ] Shrink `allowlistedRefreshDatabaseBaseline` after each migration batch.
- [ ] `tests/Unit/RefreshDatabaseUsageGuardTest.php`
  - [ ] Keep strict and aligned with current policy.

### Cross-module DB-write hotspots to remediate

- [ ] `Modules/CaseManager/Tests/Feature/DiagnosticFlowIntegrationTest.php`
- [ ] `Modules/CaseManager/Tests/Unit/Services/RmmBridgeServiceTest.php`
- [ ] `Modules/PIB/Tests/Unit/ClientCreditServicePestTest.php`
- [ ] `Modules/PIB/Tests/Feature/RecurringInvoicesJobPestTest.php`
- [ ] `Modules/PIB/Tests/Feature/RecurringInvoicesJobEdgeCasesPestTest.php`
- [ ] `Modules/PIB/Tests/Feature/ServicePlanResolversPestTest.php`
- [ ] `Modules/AssetManagement/Tests/Feature/GoogleChromebookDiscoveredListenerPestTest.php`

### Validation

- [ ] `php artisan test tests/Unit/ModuleUnitIsolationGuardTest.php`
- [ ] `php artisan test tests/Unit/RefreshDatabaseUsageGuardTest.php`

### Exit criteria

- [ ] No new module unit isolation violations introduced.
- [ ] Allowlist is smaller than baseline.

---

## 5) WS-E Mocking/Faking Integrity

### Enforcement sweep

- [ ] Scan for all fakes:
  - `Queue::fake`
  - `Bus::fake`
  - `Event::fake`
  - `Mail::fake`
  - `Notification::fake`
  - `Http::fake`
- [ ] Ensure each fake has paired assert(s) or explicit reason for unasserted safety fake.
- [ ] Remove over-mocked internals that bypass real domain behavior.

### Suggested target files from audit patterns

- [ ] `tests/Unit/Jobs/SendEmailReplyErrorTest.php`
- [ ] `tests/Integration/Misc/OAuthTest.php`
- [ ] `Modules/Action1/Tests/Feature/Action1ServicePestTest.php`
- [ ] `Modules/Payment/Tests/Feature/PaymentGatewayFailurePathsPestTest.php` (verify fake/assert quality remains strong)

### Validation

- [ ] Run fake/assert parity script or grep-based checks.
- [ ] Run focused suites per touched module.

### Exit criteria

- [ ] No critical-path fake without meaningful assertion.

---

## 6) WS-F Brittle UI Assertion Reduction

### Primary files

- [ ] `Modules/PIB/Tests/Feature/PibViewsTest.php`
- [ ] `Modules/ContractManager/Tests/Feature/ContractManagerViewsTest.php`
- [ ] Additional view-heavy tests identified during sweep.

### Refactor checklist per file

- [ ] Keep minimal smoke for route + auth + view render if needed.
- [ ] Replace static text assertions with behavior/data contract assertions.
- [ ] Assert business state changes where applicable.

### Exit criteria

- [ ] UI copy changes no longer cause frequent non-functional test failures.

---

## 7) WS-G Critical Business Invariants Coverage

### Payment invariants

- [ ] `Modules/Payment/Tests/Feature/PaymentGatewayFailurePathsPestTest.php`
  - [ ] Ensure decline/timeout/partial auth invariants are explicit.
- [ ] `Modules/Payment/Tests/Feature/WebhookHandlingPestTest.php`
  - [ ] Ensure idempotency + state transition invariants are explicit.

### PIB credit/ledger invariants

- [ ] `Modules/PIB/Tests/Unit/ClientCreditServicePestTest.php`
- [ ] New/updated integration counterpart file(s)
  - [ ] cents precision invariant
  - [ ] running balance invariant
  - [ ] insufficient credit invariant

### CaseManager diagnostic invariants

- [ ] `Modules/CaseManager/Tests/Feature/DiagnosticFlowIntegrationTest.php`
  - [ ] all diagnostics completion gate
  - [ ] timeout/failure transitions
  - [ ] duplicate callback idempotency

### Exit criteria

- [ ] Invariant-oriented tests added for each high-risk domain.

---

## 8) Per-PR Delivery Checklist

For each PR:

- [ ] Scope is one workstream slice only.
- [ ] Conventional commit message used.
- [ ] Focused tests run and passing.
- [ ] Latest report inspected.
- [ ] Runtime impact noted.
- [ ] Guard allowlist changes explained (if any).
- [ ] No unrelated files included.

---

## 9) Progress Log

Use this section to track execution in chronological order.

### Entry Template

- Date:
- Workstream:
- PR/Commit:
- Files touched:
- Tests run:
- Runtime delta:
- Risks / follow-ups:

### Entries

---

- Date: 2026-03-17
- Workstream: WS-A (Foundation Split)
- PR/Commit: `1bc623603`
- Files touched:
  - `tests/TestCase.php` — removed `use RefreshDatabase`
  - `tests/UnitTestCase.php` — added `use RefreshDatabase` (temporary, pending WS-C)
  - `tests/IntegrationTestCase.php` — new, `use RefreshDatabase` permanently
  - `tests/PureUnitTestCase.php` — new, extends PHPUnit directly (no App/DB)
  - `Modules/*/Tests/Pest.php` — Integration binding added (Action1, Crm, others)
- Tests run: 3056 Unit + 740 Integration — all pass
- Runtime delta: ~neutral
- Risks / follow-ups: UnitTestCase still has RefreshDatabase — clean up in WS-C

---

- Date: 2026-03-17
- Workstream: WS-B (Junk Test Removal) + WS-E (Mail Assertions)
- PR/Commit: `6e12e0fb3`, `58357d13f`
- Files touched:
  - `tests/Unit/Jobs/SendAlertTest.php` — removed `test_handle_method_exists`; added `Mail::assertSent(Alert::class, N)` to 5 tests + `assertNothingSent()` to no-admins test
  - `tests/Unit/Jobs/JobFailureRecoveryTest.php` — removed 15 junk tests; added `Mail::assertSent` to 3 tests
  - `tests/Feature/Integration/SendEmailReplyErrorTest.php` — 4 `Mail::assertSent` added
  - Various: `InterfaceSegregationTest`, `KernelTests`, `ThreadTest` junk removals
- Tests run: SendAlertTest 16/16, JobFailureRecoveryTest 9/9 — all pass
- Runtime delta: net −240+ lines, faster
- Risks / follow-ups: module-level fakes still unchecked (WS-E)

---

- Date: 2026-03-17
- Workstream: WS-C (Layer Correction) + WS-D (Allowlist) + WS-G (Invariants)
- PR/Commit: `49f703a35`
- Files touched:
  - `Modules/CaseManager/Tests/Unit/Services/RmmBridgeServiceTest.php` — **deleted** (15 DB tests moved)
  - `Modules/CaseManager/Tests/Integration/Services/RmmBridgeServiceTest.php` — **new** (15 tests)
  - `Modules/CaseManager/Tests/Pest.php` — Integration binding added
  - `Modules/PIB/Tests/Unit/ClientCreditServicePestTest.php` — stripped to 2 pure validation tests
  - `Modules/PIB/Tests/Integration/Services/ClientCreditServicePestTest.php` — **new** (15 DB + 3 invariant tests)
  - `Modules/PIB/Tests/Pest.php` — Integration binding added
  - `tests/Unit/ModuleUnitIsolationGuardTest.php` — removed 2 allowlist entries
- Tests run: 15 PIB Integration + 15 CaseManager Integration + 2 PIB Unit — all pass
- WS-G invariants: cents-precision ($1.99 = 199 cents), running-balance consistency, zero-floor insufficient-credit
- Risks / follow-ups: 36 allowlist entries remain; ImapService large (1672 lines)

---

- Date: 2026-03-18
- Workstream: WS-F (Brittle UI Assertion Reduction)
- PR/Commit: PIB submodule `0394d15`, ContractManager submodule `8d0dda0`, root `1ccf7b70e`
- Files touched:
  - `Modules/PIB/Tests/Feature/PibViewsTest.php` — replaced 4× `assertSee('static label')` with `assertViewHas()` data contracts
  - `Modules/ContractManager/Tests/Feature/ContractManagerViewsTest.php` — replaced 3× `assertSee('static label')` with `assertViewHas()` data contracts
- Tests run: 7/7 pass (36 assertions)
- Runtime delta: neutral
- Risks / follow-ups: Other modules with `assertSee` pattern unchecked
