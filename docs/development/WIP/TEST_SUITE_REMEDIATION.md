# Test Suite Remediation Plan — From 5.5/10 to 8/10

> **Created:** 2026-03-17
> **Audit baseline:** 589 test files · ~86,000 lines · current score 5.5/10
> **Target score:** 8/10
> **Owner:** QA / Lead Engineer
> **Linked audit:** `docs/development/WIP/TEST_SUITE_REMEDIATION.md` (this file)

---

## How to Read This Plan

Tasks are grouped into five improvement **brackets**, ordered by risk-vs-reward.
Each bracket lists the exact files involved and the acceptance criterion that
closes the task. Complete brackets in order — later brackets depend on the
clean foundation laid by earlier ones.

Progress column key: `[ ]` not started · `[~]` in progress · `[x]` done

---

## Bracket 0 — Unblock CI (Prerequisite — Must Do First)

> **Goal:** Restore a green baseline so every subsequent change can be
> validated. Nothing else in this plan is meaningful until CI passes cleanly.

The last `php artisan test --parallel` run crashed with:

```
PHPUnit\Event\Code\NoTestCaseObjectOnCallStackException not found
```

This is a PHPUnit class-loader race in `paratest` when a parallel worker
triggers the `ErrorHandler` before the autoloader has finished loading the
test framework. It prevents any reliable test run.

### Tasks

- [x] **B0-1 · Diagnose the paratest crash**
  - Run `php artisan test` (no `--parallel`) and confirm the suite passes.
  - If it passes serially, the issue is isolated to parallel execution.
  - Files: `phpunit.xml`, `composer.json` (paratest configuration)
  - Acceptance: `php artisan test` exits 0 with no fatal PHP errors.

- [x] **B0-2 · Pin or upgrade `brianium/paratest`**
  - Check `composer.json` for the current constraint on `brianium/paratest`.
  - Run `composer show brianium/paratest` to confirm installed version.
  - If version < 7.5, upgrade: `composer require brianium/paratest:^7.5 --dev`.
  - Acceptance: `php artisan test --parallel` completes without the
    `NoTestCaseObjectOnCallStackException` crash.

- [x] **B0-3 · Delete the debug artifact committed to the test suite**
  - **File:** `tests/Debug/PolicyDebugTest.php`
  - This file contains live `dump()` calls and is actively polluting test
    output. It has no assertion value.
  - Action: `git rm tests/Debug/PolicyDebugTest.php`
  - Acceptance: File no longer exists; directory `tests/Debug/` is empty
    and can be removed.

---

## Bracket 1 — Delete Junk Tests (Zero-Value Cut List)

> **Goal:** Remove tests that test the framework or assert constants, not
> business logic. These give false confidence, hit the DB needlessly, and
> slow CI without providing any regression protection.
>
> **Expected CI time reduction:** ~15–20% (fewer DB boots, fewer assertions
> on trivia).

### 1-A · Delete Hollow / No-Assertion Files

These files call `expectNotToPerformAssertions()` as their primary assertion
mechanism, which means they unconditionally pass while the application can be
broken.

- [x] **B1-1 · Delete `AppServiceProviderTest.php`**
  - **File:** `tests/Unit/Providers/AppServiceProviderTest.php`
  - All three tests are hollow:
    - `test_register_method_executes_without_error()` — `expectNotToPerformAssertions()`
    - `test_boot_method_executes_without_error()` — `expectNotToPerformAssertions()`
    - `test_service_provider_is_loaded_or_deferred()` — asserts only that
      `class_exists(AppServiceProvider::class)` which is always true.
  - The real provider bindings are fully covered by
    `tests/Unit/Providers/ProvidersComprehensiveTest.php`.
  - Action: `git rm tests/Unit/Providers/AppServiceProviderTest.php`
  - Acceptance: No test names from this file exist in the suite.

- [x] **B1-2 · Purge no-assertion tests from `RememberUserLocaleListenerTest.php`**
  - **File:** `tests/Unit/RememberUserLocaleListenerTest.php`
  - 5 methods call `expectNotToPerformAssertions()` to assert "no exception
    thrown" without verifying the actual side effect (locale being set on
    the App).
  - Action: For each hollow method, either add an assertion on
    `app()->getLocale()` / `App::getLocale()` after the listener runs, or
    delete the method if the scenario is already covered elsewhere.
  - Acceptance: Zero calls to `expectNotToPerformAssertions()` remain in
    this file.

- [x] **B1-3 · Delete the scaffold browser example**
  - **File:** `tests/Browser/ExamplePestTest.php`
  - Contains only `assertSee('Email')` on the login page — a scaffold file
    that was never replaced.
  - Action: `git rm tests/Browser/ExamplePestTest.php`
  - Acceptance: File no longer exists.

### 1-B · Delete Tests That Test Eloquent / PHP Internals

These tests exercise Eloquent relationship mechanics and PHP constant
definitions, not application business rules.

- [x] **B1-4 · Delete `UserRelationshipsTest.php`**
  - **File:** `tests/Unit/Models/UserRelationshipsTest.php`
  - Offending tests: `test_user_eager_loads_mailboxes_relationship`,
    `test_user_eager_loads_conversations_relationship`,
    `test_user_can_be_detached_from_mailbox`,
    `test_multiple_users_can_share_same_mailbox`,
    `test_user_mailbox_relationship_is_many_to_many`.
  - All assert that Laravel's `with()`, `attach()`, and `detach()` work.
    These are Eloquent ORM guarantees.
  - The remaining two methods (`isAdmin` truthy/falsy) should be moved to
    `tests/Unit/Models/UserMethodsTest.php` if not already present.
  - Action: `git rm tests/Unit/Models/UserRelationshipsTest.php` after
    migrating the two `isAdmin` tests.
  - Acceptance: File deleted; `isAdmin` tests live in `UserMethodsTest.php`.

- [x] **B1-5 · Delete `MailboxEnhancedTest.php`**
  - **File:** `tests/Unit/Models/MailboxEnhancedTest.php`
  - All 6 tests assert relationship counts or that Eloquent casts work
    (`has_many_conversations`, `has_many_folders`, `belongs_to_many_users`,
    `has_in_server_configuration`, `has_out_server_configuration`).
  - These are already covered by `CoreModelsComprehensiveTest.php` and
    the arch rule in `tests/Architecture/ModuleBoundariesTest.php`.
  - Action: `git rm tests/Unit/Models/MailboxEnhancedTest.php`
  - Acceptance: File deleted; coverage stats unchanged.

- [x] **B1-6 · Delete constant-value blocks from `ActivityLogTest.php`**
  - **File:** `tests/Unit/Models/ActivityLogTest.php`
  - Remove the 4 methods asserting constant string values:
    `test_name_user_constant_exists`, `test_name_out_emails_constant_exists`,
    `test_name_emails_sending_constant_exists`,
    `test_name_emails_fetching_constant_exists`.
  - Keep all methods that test real behaviour (`getEventDescription`,
    `getScopes`, date scopes, etc.).
  - Acceptance: No method matching `*_constant_exists` or
    `assertEquals('users', ActivityLog::NAME_USER)` remains in this file.

- [x] **B1-7 · Delete constant-value blocks from `ActivityLogModelMethodsTest.php`**
  - **File:** `tests/Unit/Models/ActivityLogModelMethodsTest.php`
  - Remove `test_email_error_constants_exist()` (uses `assertTrue(defined(...))`)
    and `test_description_constants_have_unique_values()`.
  - Acceptance: Zero calls to `defined(` or to a method asserting constant
    uniqueness remain in this file.

- [x] **B1-8 · Delete constant-value blocks from `SubscriptionTest.php`**
  - **File:** `tests/Unit/Models/SubscriptionTest.php`
  - Remove all methods matching `test_medium_*_constant_exists` (3 methods)
    and `test_event_*_constant_exists` (6+ methods).
  - Keep: subscription-scoped query tests,
    and any test that exercises notification dispatch logic.
  - Acceptance: No method matching `*_constant_exists` remains in this file.

- [x] **B1-9 · Purge `can_be_created` / `uses_has_factory_trait` /
    `uses_correct_table` / `has_correct_fillable` test methods suite-wide**
  - **Files affected** (all under `tests/Unit/Models/`):
    - `ActivityLogTest.php`
    - `ActivityLogModelMethodsTest.php`
    - `SubscriptionTest.php`
    - `CoreModelsComprehensiveTest.php`
    - `SavedSearchModelTest.php`
    - `AdditionalModelsTest.php`
    - `SendLogTest.php`
    - `OptionTest.php`
    - `AttachmentTest.php`
    - `ConversationMethodsTest.php`
    - `ChannelTest.php`
    - `CustomerChannelTest.php`
    - `FolderMethodsTest.php`
    - `ModuleTest.php`
  - Grep target: `grep -rn "can_be_created\|uses_has_factory_trait\|uses_correct_table\|has_correct_fillable" tests/Unit/Models/`
  - There are ~107 matching method occurrences. Each should be deleted.
  - Acceptance: `grep` above returns zero results.

---

## Bracket 2 — Eliminate Duplication

> **Goal:** Consolidate overlapping test files. Each covered scenario should
> exist in exactly one file. This directly cuts CI wall time and reduces
> maintenance burden when behaviour changes.

### 2-A · Consolidate Cache Tests

- [x] **B2-1 · Merge `CachingTest.php` into `CacheInvalidationTest.php`**
  - **Files:**
    - `tests/Feature/CachingTest.php` (150 lines — DELETE after merge)
    - `tests/Feature/CacheInvalidationTest.php` (277 lines — KEEP, extend)
  - `CachingTest.php` covers: key construction, `remember()`, `forget()`,
    `get()` — all duplicated in `CacheInvalidationTest.php`.
  - Action: Copy the two unique scenarios from `CachingTest.php` (if any)
    into `CacheInvalidationTest.php`, then `git rm tests/Feature/CachingTest.php`.
  - Acceptance: `CachingTest.php` deleted; `CacheInvalidationTest.php`
    contains all `CacheService` contract coverage; no duplicate test names.

### 2-B · Consolidate Option Model Tests

- [x] **B2-2 · Deduplicate `OptionModelTest.php` and `OptionPestTest.php`**
  - **Files:**
    - `tests/Unit/OptionModelTest.php` (KEEP — pure unit, no DB)
    - `tests/Feature/Core/OptionPestTest.php` (KEEP — integration, tests
      DB upsert behaviour which requires `RefreshDatabase`)
  - Review both; remove any scenario from the Unit version that is
    identical to a `RefreshDatabase` test in the Feature version.
  - The Unit file should test pure method logic on `new Option`; the
    Feature file should test `Option::setValue()` with real DB upsert.
  - Acceptance: Zero test names appear in both files.

### 2-C · Collapse Duplicate Interface Segregation Tests

- [x] **B2-3 · Move `Feature/InterfaceSegregationTest.php` to Architecture/**
  - **File:** `tests/Feature/InterfaceSegregationTest.php`
  - This file uses `RefreshDatabase` but all assertions are structural
    (resolving from container, checking `instanceof`). None require DB rows.
  - Action: Remove `uses(RefreshDatabase::class)`. Verify the container
    resolution tests pass without DB. Move file to
    `tests/Architecture/InterfaceSegregationTest.php`, replacing or
    merging with the existing file there.
  - **Existing file at destination:**
    `tests/Architecture/InterfaceSegregationTest.php` — merge any unique
    assertions from the Feature version into the Architecture version, then
    delete the Feature version.
  - Acceptance: `tests/Feature/InterfaceSegregationTest.php` deleted;
    ISP assertions live exclusively in `tests/Architecture/`.

### 2-D · Collapse Duplicate Mailbox Tests

- [x] **B2-4 · Remove duplicated `test_created_at_is_cast_to_datetime` occurrences**
  - This method name appears 6 times across `tests/Unit/Models/`.
  - **Files to review:** `MailboxMethodsTest.php`, `CoreModelsComprehensiveTest.php`,
    `AdditionalModelsTest.php`, `ConversationMethodsTest.php`,
    `FolderMethodsTest.php`, `SendLogTest.php`.
  - Keep the assertion in the most relevant file for each model; delete
    all duplicate occurrences.
  - Acceptance: Each `test_created_at_is_cast_to_datetime` appears at
    most once per model class under test.

### 2-E · Consolidate IMAP Service Tests

- [x] **B2-5 · Merge 17 `ImapService*Test.php` files into 3**
  - **Files to DELETE** (7,262 total lines):
    - `tests/Unit/Services/ImapServiceRefactoredMethodsTest.php` (759 lines)
    - `tests/Unit/Services/ImapServiceHelpersBasicTest.php` (674 lines)
    - `tests/Unit/Services/ImapServiceHelpersAdvancedTest.php` (585 lines)
    - `tests/Unit/Services/ImapServiceHelpersEdgeCasesTest.php` (557 lines)
    - `tests/Unit/Services/ImapServiceComprehensiveTest.php` (537 lines)
    - `tests/Unit/Services/ImapServiceParseAddressesTest.php` (524 lines)
    - `tests/Unit/Services/ImapServiceGetMessageHeadersTest.php` (469 lines)
    - `tests/Unit/Services/ImapServiceTestConnectionTest.php` (413 lines)
    - `tests/Unit/Services/ImapServiceIntegrationSmokeTest.php` (368 lines)
    - `tests/Unit/Services/ImapServiceTest.php` (362 lines)
    - `tests/Unit/Services/ImapServiceCharsetRetryTest.php` (340 lines)
    - `tests/Unit/Services/ImapServiceFolderPathTest.php` (319 lines)
    - `tests/Unit/Services/ImapServiceGetFoldersTest.php` (312 lines)
    - `tests/Unit/Services/ImapServiceCreateClientTest.php` (302 lines)
    - `tests/Unit/Services/ImapServiceEdgeCasesTest.php` (276 lines)
    - `tests/Unit/Services/ImapServiceFetchEmailsBasicTest.php` (272 lines)
    - `tests/Unit/Services/ImapServiceGetEncryptionTest.php` (193 lines)
  - **Files to CREATE (replacing above):**
    - `tests/Unit/Services/ImapServiceTest.php` — public API behaviour
      (connection, folder listing, message fetching via public methods).
    - `tests/Unit/Services/ImapServiceAddressParsingTest.php` — address
      and header parsing edge cases (charset retry, encoded words, malformed).
    - `tests/Unit/Services/ImapServiceEncryptionTest.php` — encryption
      negotiation and folder path construction.
  - **Process:**
    1. Run the existing 17 files; record all passing test names.
    2. Identify unique scenarios (not duplicate method names).
    3. Rewrite into 3 files using `describe()` blocks.
    4. Confirm same unique scenarios pass in new structure.
    5. Delete the 17 old files.
  - Target line budget: ~350 lines total across 3 files.
  - **Do NOT test private methods directly.** All assertions must go through
    public method calls.
  - Acceptance: `php artisan test tests/Unit/Services/ImapService*` passes;
    total file count is 3; total lines ≤ 400.

---

## Bracket 3 — Fix Misleading / Fragile Tests

> **Goal:** Tests that currently pass but provide false confidence, or that
> will break on any cosmetic change, are corrected to assert real behaviour.

- [x] **B3-1 · Fix `EventListenersTest.php` — replace log-string assertions**
  - **File:** `tests/Feature/EventListenersTest.php`
  - **Problem:** Tests assert on exact log message strings like
    `'PIB: Contract revised, checking proration'`. Log text is an
    implementation detail; refactoring it causes a false test failure.
  - **Fix per test (completed):**
    - `contract revised event triggers proration recalculation` →
      after `$listener->handle($event)`, reload `$template` and assert
      `product_config['proration_pending'] === true`.
    - `contract revised event skips proration for inactive contracts` →
      after handle, assert `product_config` does not contain
      `proration_pending`.
    - `contract revised event skips proration when no active templates exist`
      follows the same DB-state pattern.
    - Software-count listener tests assert `product_config` state changes
      directly and no longer assert logger strings.
  - Acceptance: Zero calls to `Log::shouldHaveReceived` in this file;
    all assertions target DB state or return values.

- [x] **B3-2 · Fix `PerformancePestTest.php` — add real query-count assertions**
  - **File:** `tests/Feature/PerformancePestTest.php`
  - **Problem:** Tests named "performs efficiently" only assert HTTP 200.
    N+1 query regressions are invisible.
  - **Fix:** Wrap each route call with `DB::enableQueryLog()` /
    `DB::getQueryLog()` and assert a maximum query budget:
    ```php
    DB::enableQueryLog();
    $response = $this->actingAs($this->admin)->get(route('conversations.index', $this->mailbox));
    $count = count(DB::getQueryLog());
    DB::disableQueryLog();
    expect($count)->toBeLessThan(15, "N+1 regression: {$count} queries on inbox load");
    $response->assertOk();
    ```
  - Add a `performance` group tag so this group can be included/excluded
    independently.
  - Acceptance: Each test in the file performs a `toBeLessThan(n)` query
    count assertion; `assertOk()` alone is insufficient.

- [x] **B3-3 · Fix the "chimera mock" anti-pattern in `EventListenersTest.php`**
  - **File:** `tests/Feature/EventListenersTest.php`
  - **Problem:** Tests create a real DB row and simultaneously construct a
    `Mockery::mock(Contract::class)->makePartial()` pointing at the same ID.
    Partial mocks of Eloquent models with real DB rows can silently diverge
    from production model behaviour.
  - **Fix:** Use real model instances throughout. Replace:
    ```php
    $contract = Mockery::mock(Contract::class)->makePartial();
    $contract->id = $realContract->id;
    ```
    with:
    ```php
    $contract = $realContract;
    ```
  - Acceptance: No `Mockery::mock(Contract::class)` calls remain in this file.

- [x] **B3-4 · Fix `SentryIntegrationTest.php` — stop using `RefreshDatabase`
    for config-only assertions**
  - **File:** `tests/Feature/Observability/SentryIntegrationTest.php`
  - This class uses `RefreshDatabase` but every test method only reads
    `config('sentry.*')` values. No DB rows are touched.
  - Action (completed): Remove `use RefreshDatabase;` from
    `SentryIntegrationTest.php` and split DB-dependent middleware tests into
    `tests/Feature/Observability/SentryMiddlewareTest.php`.
  - Acceptance: `SentryIntegrationTest` runs without a DB migration cycle;
    `use RefreshDatabase` line removed.

---

## Bracket 4 — Close Critical Coverage Gaps

> **Goal:** Add tests for the high-risk production paths that currently have
> zero or near-zero coverage. These are the scenarios most likely to cause a
> production incident that the test suite would not catch.

### 4-A · Payment Gateway Resilience

- [x] **B4-1 · Add payment job idempotency tests**
  - **Target file:** `Modules/Payment/Tests/Feature/PaymentProcessingPestTest.php`
    (extend existing file)
  - **Scenarios to add:**
    1. `ProcessInvoicePayment` job dispatched twice for the same invoice ID
       → second dispatch must detect the payment already exists and exit
       without charging the gateway again. Assert:
       - `Payment::where('invoice_id', $invoice->id)->count() === 1` after
         two job executions.
       - `Http::fake()` recorded exactly one outbound POST to the gateway.
    2. Helcim returns HTTP 429 (rate limit) → job should `release()` back
       onto the queue and not mark the invoice as failed. Assert:
       - Invoice status remains `pending` after the job handles the 429.
    3. Helcim returns HTTP 503 → job enters retry backoff (does not flip
       invoice to `failed` on first attempt). Assert same as above.
    4. Helcim returns HTTP 200 with malformed JSON response body → job
       catches the parse exception, marks invoice `error`, and does not
       create a `Payment` record.
  - Acceptance: All 4 scenarios covered; `Http::fake()` used throughout;
    no real HTTP calls.

- [x] **B4-2 · Add gateway failure path tests**
  - **Target file:** `Modules/Payment/Tests/Feature/PaymentGatewayFailurePathsPestTest.php`
    (may already exist — review and extend)
  - **Scenarios to add (if not present):**
    1. `ProcessDueInvoices` job with an empty due-invoice list → exits
       cleanly with zero gateway calls.
    2. Card declined (Helcim returns `{"errors": {"code": "DECLINE"}}`) →
       invoice marked `failed`, `Payment` record created with `status = declined`.
    3. Customer not found at gateway → `HelcimException` thrown; invoice
       state unchanged; exception logged.
  - Acceptance: Each scenario is a separate `it()` / `test()` block with
    explicit state assertions.

### 4-B · Billing Cycle Concurrency

- [x] **B4-3 · Add duplicate-job guard test for `RecurringInvoicesJob`**
  - **Target file:** `Modules/PIB/Tests/Feature/RecurringInvoicesJobPestTest.php`
    (extend existing file)
  - **Scenario:** Two workers both pick up the same `RecurringInvoicesJob`
    at the same time for the same `BillingTemplate` ID.
  - Assert: Only one invoice is created (`Invoice::count() === 1`);
    the second job either skips via a unique lock or detects the
    already-generated invoice.
  - **Implementation:** Added `withoutOverlapping()` and `onOneServer()` to
    job scheduling in `routes/console.php`; job now runs at 01:00 daily
    with distributed job lock preventing concurrent execution.
  - **Test added:** `prevents duplicate invoices when job runs concurrently for same template`
    in `RecurringInvoicesJobPestTest.php` verifies duplicate guard works.
  - Acceptance: Test passes (8 tests, all passing); a single execution of
    the billing cycle for a given template ID cannot produce duplicate
    invoices regardless of parallel execution.

### 4-C · Rent-to-Own State Machine Edge Cases

- [x] **B4-4 · Add RTO off-by-one and cancellation tests**
  - **Target file:** `Modules/ContractManager/Tests/Feature/RentToOwnOwnershipTransferTest.php`
    (extended).
  - **Scenarios added:**
    1. **Final Payment Threshold:** Ownership transfer fires on exactly when
       total paid ≥ purchase_price, not before. Test creates 3-month RTO
       ($300 goal, $100/month): verifies ownership stays `pending` after
       months 1-2 ($100, $200 paid), then transfers on month 3 (final payment).
    2. **Cancellation Guard:** When contract is cancelled mid-RTO (during
       final payment month), ownership transfer is blocked. Modified listener
       `TransferOwnershipOnPayment` to skip transfer if `contract->status`
       is `'cancelled'`.
    3. **Duplicate Contracts:** Test documents system behavior when creating
       duplicate RTO contracts on same asset. Currently, no DB constraint
       prevents this (would require asset_id FK field); test marks this as
       area for future enhancement.
  - **Listener Change:** Added cancellation check in
    `Modules/ContractManager/Listeners/TransferOwnershipOnPayment.php`
    to prevent ownership transfer for cancelled contracts.
  - Acceptance: All 3 scenarios covered (7 total tests passing);
    assertions use final DB state verification.

### 4-D · AI / Diagnostic Pipeline Resilience

- [x] **B4-5 · Add Gemini malformed-response handling test**
  - **Target file:** `Modules/CaseManager/Tests/Integration/Services/GeminiClientTest.php`
    (extend)
  - **Scenarios to add:**
    1. Gemini API returns non-JSON body (`text/html` error page) →
       `GeminiClient` throws a typed exception (not a generic PHP error).
       Assert the exception class and that `DecisionEngine` catches it and
       sets the `Diagnostic` status to `failed`.
    2. Gemini returns truncated JSON (valid prefix, ends mid-token) →
       same fallback path as above.
    3. Gemini returns HTTP 429 → `GeminiClient` propagates a
       `RateLimitException`; `AiPipelineFailureHandler` schedules a retry.
  - Acceptance: 3 scenarios; assertions on `Diagnostic.status` in DB.

- [x] **B4-6 · Add `AiPipelineFailureHandler` exhaustion test**
  - **Target file:**
    `Modules/CaseManager/Tests/Integration/Traits/AiPipelineFailureHandlerTest.php`
    (extended with 3 new tests)
  - **Implementation Note:** The handler doesn't use a retry counter; instead, it
    uses state guards to prevent infinite escalation. Added tests:
    1. marks in-flight diagnostics as failed when handling api error
    2. is idempotent when called on already-errored case
    3. handles rapid successive API errors without cascading escalations
  - **Tests verify:** Once a case is in `api_error_needs_human`, subsequent errors
    don't cause further escalation; in-flight diagnostics are marked `failed` to
    prevent stale processing states; no infinite escalation loops possible.
  - Acceptance: All 14 tests passing (11 original + 3 new).

- [x] **B4-7 · Add diagnostic state-machine race condition test**
  - **Target file:**
    `Modules/CaseManager/Tests/Integration/Jobs/CheckDiagnosticTimeoutJobTest.php`
    (extended with 2 new tests)
  - **Implementation:** Added explicit race condition tests simulating concurrent
    completion and timeout:
    1. does not revert completed diagnostics to timed_out in race condition
    2. safely handles case where some diagnostics complete during timeout window
  - **Tests verify:** When diagnostics complete while timeout job is executing,
    the timeout job correctly leaves completed ones untouched and only marks
    still-pending/running ones as timed_out. No reversion of completed status.
  - Acceptance: All 7 tests passing (5 original + 2 new);

### 4-E · Cross-Module Event Contract Verification

- [x] **B4-8 · Add full event-chain dispatch test for `SoftwareCountChanged`**
  - **Target file:** New
    `tests/Feature/Integration/SoftwareCountChangedEventChainTest.php`
  - **Scenario:** Dispatch `SoftwareCountChanged` through the real
    `EventServiceProvider` (not by calling listener `handle()` directly).
    Assert that **all** registered listeners fire:
    - `AdjustBillingOnSoftwareCountChange` → billing template updated
    - `UpdateEntitlementSnapshots` → snapshot record updated
  - Use `Event::assertDispatched()` only to confirm secondary events fired
    by listeners; assert final DB state for primary listener effects.
  - Acceptance: `SoftwareCountChanged` dispatched once → both downstream
    state changes reflected in DB; no listener silently skipped.

### 4-F · Auth Rate Limiting Enforcement

- [x] **B4-9 · Strengthen `AuthRateLimitingTest.php`**
  - **File:** `tests/Feature/AuthRateLimitingTest.php`
  - Verify the file currently tests:
    - The Nth failed login returns HTTP 429 with `Retry-After` header.
    - A valid login succeeds immediately after the lockout window expires
      (use `Carbon::setTestNow()` to advance time).
    - A valid login is blocked while inside the lockout window.
  - If any of these three scenarios are missing, add them.
  - Acceptance: All 3 scenarios present and asserting HTTP status codes
    and `Retry-After` header values explicitly.

---

## Bracket 5 — Architecture and Speed Infrastructure

> **Goal:** Structural changes that make the test suite faster, more
> trustworthy, and self-enforcing for future development.

- [x] **B5-1 · Add `#[Group]` tagging strategy**
  - Annotate **all** `tests/Browser/` files with `#[Group('browser')]`
    (Pest: `->group('browser')`).
  - Annotate `tests/Feature/PerformancePestTest.php` with
    `->group('performance')`.
  - Annotate CaseManager integration tests with `->group('ai')`.
  - Update `phpunit.xml` to define named test suites:
    ```xml
    <testsuite name="fast">...</testsuite>  <!-- Unit + Architecture -->
    <testsuite name="integration">...</testsuite>  <!-- Feature + Module Integration -->
    <testsuite name="browser">...</testsuite>  <!-- Browser/ -->
    ```
  - Document in README: `php artisan test --group=fast` for < 60-second
    local feedback; full suite in CI.
  - Acceptance: `php artisan test --exclude-group=browser,performance`
    completes in under 90 seconds; documentation updated.

- [x] **B5-2 · Enforce the no-`RefreshDatabase`-in-structural-tests rule via ArchTest**
  - **File:** `tests/ArchTest.php`
  - Add an arch rule:
    ```php
    arch('architecture tests do not hit the database')
        ->expect('Tests\Architecture')
        ->not->toUse('Illuminate\Foundation\Testing\RefreshDatabase');
    ```
  - Acceptance: Rule added; `tests/Architecture/` is clean
    (follow-up from B2-3 which moves one file).

- [x] **B5-3 · Extend `ModuleUnitIsolationGuardTest` to catch `Http::fake()` omissions**
  - **File:** `tests/Unit/ModuleUnitIsolationGuardTest.php`
  - The current guard catches `RefreshDatabase` in unit tests. Extend it
    to also flag module Feature tests that contain known external API
    hostnames (Helcim, Google, Gemini, Action1) without a corresponding
    `Http::fake()` or `Http::preventStrayRequests()` call.
  - Pattern to detect: test file imports a gateway service class AND does
    not contain `Http::fake` or `Http::preventStrayRequests`.
  - Acceptance: Guard catches at least the 3 known external-API-using
    modules (Payment/Helcim, GoogleAdmin/Google, CaseManager/Gemini).

- [x] **B5-4 · Add `Http::preventStrayRequests()` to the global test bootstrap**
  - **File:** `tests/TestCase.php` (base class `setUp()`)
  - Add `Http::preventStrayRequests()` in `setUp()` so any test that
    makes a real HTTP call fails loudly rather than silently hitting a
    live endpoint.
  - Exceptions (already using `Http::fake()`): Payment tests, GoogleAdmin
    tests, Action1 tests — these already mock correctly and will be unaffected.
  - Acceptance: A test that calls a real external URL fails with
    `ConnectionException: Attempted real HTTP request` rather than
    producing a flaky pass/timeout.

- [x] **B5-5 · Add architecture rule enforcing event-listener registration**
  - **File:** `tests/Architecture/EnhancedArchitectureTest.php` (extend)
  - Guard 5 (`Event Handler Registration`) is described in the docblock
    but may not be fully implemented. Verify/implement a test that
    asserts every concrete `Listener` class in each module is registered
    in that module's `EventServiceProvider::$listen` array.
  - Acceptance: Adding a new Listener class without registering it in
    the EventServiceProvider causes an arch test failure.

---

## Completion Criteria → 8/10 Score

The score will reach 8/10 when:

| # | Criterion | Verified by |
|---|-----------|-------------|
| 1 | CI (serial + parallel) passes green with exit 0 | B0-1, B0-2 |
| 2 | Debug artifacts removed from repository | B0-3, B1-3 |
| 3 | Zero hollow `expectNotToPerformAssertions()` tests | B1-1, B1-2 |
| 4 | Zero constant-value-only tests | B1-6–B1-9 |
| 5 | IMAP battery consolidated to ≤ 3 files / ≤ 400 lines | B2-5 |
| 6 | Cache and ISP tests deduplicated | B2-1–B2-3 |
| 7 | Log-string assertions replaced with state assertions | B3-1, B3-3 |
| 8 | Performance tests assert query counts | B3-2 |
| 9 | Payment gateway idempotency + failure paths covered | B4-1, B4-2 |
| 10 | Billing concurrency guard tested | B4-3 |
| 11 | RTO edge cases covered | B4-4 |
| 12 | AI pipeline resilience covered | B4-5–B4-7 |
| 13 | Cross-module event chain verified end-to-end | B4-8 |
| 14 | `Http::preventStrayRequests()` in base TestCase | B5-4 |

Achieving 9/10 would additionally require full Bracket 5 completion and
running coverage tooling to confirm ≥ 80% branch coverage on all financial
service classes.

---

## Suggested Execution Order

```
Week 1:  B0-1 → B0-2 → B0-3 (unblock CI)
Week 1:  B1-1 → B1-3 → B1-4 → B1-5 (quick file deletions)
Week 2:  B1-6 → B1-7 → B1-8 → B1-9 (constant purge)
Week 2:  B2-1 → B2-2 → B2-3 → B2-4 (deduplication)
Week 3:  B2-5 (IMAP consolidation — time-box to 2 days)
Week 3:  B3-1 → B3-2 → B3-3 → B3-4 (fix misleading tests)
Week 4:  B4-1 → B4-2 → B4-3 (payment + billing concurrency)
Week 4:  B4-4 → B4-5 → B4-6 → B4-7 (RTO + AI pipeline)
Week 5:  B4-8 → B4-9 (event chain + auth rate limiting)
Week 5:  B5-1 → B5-2 → B5-3 → B5-4 → B5-5 (infrastructure)
```
