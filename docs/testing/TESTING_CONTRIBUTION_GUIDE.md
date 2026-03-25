# Testing Contribution Guide

This guide defines how to add or modify tests in this repository with high signal, fast feedback, and strong module boundaries.

## Purpose

Use this guide when writing or reviewing tests so contributions align with:
- docs/testing/TESTING_ROADMAP_OUTCOMES.md
- tests/testing_standards.md
- architecture and isolation guard tests

## Quick Start

1. Put the test in the right layer: Unit, Feature, Integration, or Browser.
2. Prefer behavior assertions over framework internals.
3. Use the smallest test scope needed to prove the behavior.
4. Run focused tests first, then broader suites only when needed.
5. Inspect reports/test-results-latest.log after each run.

## Layer Placement Rules

### Unit

Use Unit tests for pure logic and deterministic behavior.

Required:
- no RefreshDatabase
- no direct cross-module persistence
- no cross-module service resolution via app()->make or resolve in unit scope

### Feature

Use Feature tests for controller behavior, authorization, validation, middleware, and request flow.

### Integration

Use Integration tests when persistence, framework wiring, event chains, or external adapters are part of the contract.

### Browser

Use Browser tests only for high-value end-to-end UX journeys.

## High-Signal Assertion Patterns

Prefer:
- state change assertions
- domain event dispatch assertions
- authorization and policy assertions
- HTTP status and structured payload assertions
- side-effect assertions with explicit fakes

Avoid:
- relation-type assertions for framework internals
- broad copy matching where behavior can be asserted directly
- over-mocking the service under test

## External API and HTTP Rules

- Always isolate external HTTP calls with Http::fake or equivalent boundary fakes.
- Never hit live services in test runs.
- Verify request payloads and retry behavior where relevant.

## Mocking Rules

Use mocks only when needed for boundaries.

Prefer:
- contract-level mocks
- fake adapters
- deterministic test doubles

Avoid:
- makePartial on the primary service under test unless there is no viable alternative

## Unit Isolation Rules

These rules are enforced by tests/Unit/ModuleUnitIsolationGuardTest.php and architecture tests.

Contributors must not introduce:
- new RefreshDatabase usage in unit scope
- cross-module persistence in unit tests
- direct cross-module service resolution in unit tests

Additionally:
- new tests in tests/Unit must default to Tests\PureUnitTestCase
- adding framework-booting Unit tests requires temporary exception metadata (owner, issue, rationale, expiry), but the root guard suite is no longer exempt from pure unit scope
- all skip exceptions must be explicitly tracked in the skip-governance allowlist

Lane execution and budget checks:
- run an individual lane locally with `bash scripts/testing/run-test-lane.sh unit|feature|integration`
- if a lane budget report returns `warn` or `fail`, inspect `reports/lane-runtime-budget-<lane>-latest.md` before changing any threshold

## Feature Assertion Depth Policy

For write-endpoint Feature tests (POST/PUT/PATCH/DELETE), at least one side-effect assertion is required.

Accepted side-effect assertions include:
- assertDatabaseHas/assertDatabaseMissing/assertDatabaseCount
- Event::assertDispatched / Bus::assertDispatched / Queue::assertPushed
- Mail::assertSent / Notification::assertSentTo
- explicit persisted-state checks via model refresh/fresh assertions

Status-only assertions for write endpoints are not acceptable except approved temporary exceptions with owner, issue, and expiry.

## Migration Examples

### Unit migration: framework-booting to pure unit

Before (avoid):

```php
<?php

namespace Tests\Unit\Billing;

use Tests\UnitTestCase;

class QuoteCalculatorTest extends UnitTestCase
{
	public function test_applies_discount(): void
	{
		$service = app()->make(\App\Services\QuoteService::class);

		$result = $service->applyDiscount(100, 10);

		$this->assertSame(90, $result);
	}
}
```

After (required):

```php
<?php

namespace Tests\Unit\Billing;

use App\Services\QuoteService;
use Tests\PureUnitTestCase;

class QuoteCalculatorTest extends PureUnitTestCase
{
	public function test_applies_discount(): void
	{
		$service = new QuoteService;

		$result = $service->applyDiscount(100, 10);

		$this->assertSame(90, $result);
	}
}
```

### Feature migration: status-only write assertion to side-effect assertion

Before (avoid):

```php
test('stores ticket', function () {
	$response = $this->postJson('/tickets', ['subject' => 'Test']);

	$response->assertStatus(201);
});
```

After (required):

```php
test('stores ticket and persists side effects', function () {
	Event::fake();

	$response = $this->postJson('/tickets', ['subject' => 'Test']);

	$response->assertCreated();
	$this->assertDatabaseHas('tickets', ['subject' => 'Test']);
	Event::assertDispatched(TicketCreated::class);
});
```

## Skip Usage Policy

- New markTestSkipped usage must be added to skip governance allowlist with owner, issue, rationale, and expiry metadata.
- Long-lived skip usage is treated as test debt and must be triaged during maintenance cadence.
- Unowned or expired skips are grounds for PR rejection.

## Required Local Validation Flow

Run the narrowest relevant checks first:

```bash
php artisan test path/to/changed/test-file.php --parallel --processes=10
```

Then run broader checks as needed:

```bash
php artisan test tests/Unit/ModuleUnitIsolationGuardTest.php --parallel --processes=10
php artisan test tests/Architecture --parallel --processes=10
php artisan test --parallel --processes=10
```

Important:
- The project already writes test logs to reports/test-results-<timestamp>.log and updates reports/test-results-latest.log.
- Prefer inspecting reports/test-results-latest.log over rerunning expensive suites.

## Pull Request Checklist

- [ ] Tests are in the correct layer.
- [ ] No new unit-scope RefreshDatabase usage.
- [ ] No new framework-booting Unit tests without approved exception metadata.
- [ ] No new cross-module unit coupling.
- [ ] Write-endpoint Feature tests include side-effect assertions (not status-only).
- [ ] Assertions focus on behavior, not framework internals.
- [ ] External HTTP interactions are faked.
- [ ] New skip/quarantine entries include owner, issue, rationale, and expiry.
- [ ] Touched tests pass.
- [ ] Architecture and isolation checks remain green when applicable.
- [ ] Any temporary exception is documented with an owner and expiry date.

## Pyramid Rebalancing Follow-Up Checklist

Use this checklist when reducing integration-heavy debt while preserving signal:

Current progress snapshot (2026-03-24):
- Wave 1 completed by migrating middleware deterministic tests to unit scope:
	- `tests/Integration/Middleware/FrameGuardTest.php` -> `tests/Unit/Middleware/FrameGuardTest.php`
	- `tests/Integration/Middleware/ResponseHeadersTest.php` -> `tests/Unit/Middleware/ResponseHeadersTest.php`
- Wave 2 completed by migrating EnsureUserIsAdmin middleware coverage to unit scope:
	- `tests/Integration/Middleware/EnsureUserIsAdminTest.php` -> `tests/Unit/Middleware/EnsureUserIsAdminTest.php`
	- removed redundant aggregate suite: `tests/Integration/Middleware/MiddlewareTest.php`
- Wave 3 completed by migrating deterministic request/helper tests to unit scope:
	- `tests/Integration/Requests/LoginRequestTest.php` -> `tests/Unit/Requests/LoginRequestTest.php`
	- `tests/Integration/Misc/MailHelperGetMessageIdHashTest.php` -> `tests/Unit/Misc/MailHelperGetMessageIdHashTest.php`
- Wave 4 completed by migrating deterministic policy tests to unit scope:
	- `tests/Integration/ThreadPolicyTest.php` -> `tests/Unit/Policies/ThreadPolicyTest.php`
	- `tests/Integration/Policies/AdvancedPolicyTest.php` -> `tests/Unit/Policies/AdvancedPolicyTest.php`
- Wave 5 completed by migrating deterministic IMAP fetch tests to unit scope:
	- `tests/Integration/Services/ImapServiceAddressParsingTest.php` coverage merged into `tests/Unit/Misc/ImapServicePureLogicTest.php`
	- `tests/Integration/Services/ImapServiceEncryptionTest.php` coverage merged into `tests/Unit/Misc/ImapServicePureLogicTest.php`
- Wave 6 completed by migrating ProrationService math and Alert mailable tests to unit scope:
	- `tests/Integration/Services/ProrationServiceTest.php` -> `tests/Unit/Services/ProrationServiceTest.php` (new ProrationService() replaces app())
	- `tests/Integration/Mail/AlertTest.php` -> `tests/Unit/Mail/AlertTest.php` (ConfigRepository stub replaces app config)
- Validation: full suite green via `php artisan test --parallel --processes=10` (2 skipped, 5749 passed)
- Updated baseline snapshot: Unit=36, Integration=213 (`reports/testing-baseline-2026-03-24.md`)
- Wave 7 completed by migrating Helper/MailHelper utility tests to unit scope (large batch):
	- `tests/Integration/Misc/HelperMethodsTest.php` (49 tests) → deleted; coverage in `tests/Unit/Misc/HelperLogicTest.php`
	- `tests/Integration/Misc/HelperEdgeCasesTest.php` (43 tests) → deleted; coverage in `tests/Unit/Misc/HelperLogicTest.php`
	- `tests/Integration/Misc/HelpersTest.php` (26 tests) → deleted; coverage in `tests/Unit/Misc/HelpersTest.php`
	- New: `tests/Unit/Misc/HelperLogicTest.php` (57 tests, 104 assertions, 0.22s)
	- New: `tests/Unit/Misc/HelpersTest.php` (19 tests covering MailHelper, guzzle values, reflection, instantiation)
	- Pattern: `Facade::clearResolvedInstances()` MUST be called in setUp before `Container::setInstance()` when tests use Facade stubs; without it, stale `$resolvedInstance` cache from parallel worker causes test-order flakiness
	- Pattern: anonymous Container subclass with `runningInConsole()`, `runningUnitTests()`, `basePath()` covers all app() calls in Helper methods
- Validation: full suite green via `php artisan test --parallel --processes=10` (2 skipped, 5741 passed; 1 pre-existing Mockery alias flake)
- Updated baseline snapshot: Unit=46, Integration=210 (`reports/testing-baseline-2026-03-24.md`)
- Wave 8 completed by migrating Misc API utility tests to unit scope with collision-safe targeting:
	- Collision-safe scope (avoided active agent paths): skipped `tests/Unit/Models/**`, `tests/Unit/Services/**`, `tests/Unit/Policies/**`, `tests/Unit/EventsTest.php`, `tests/Unit/SendAutoReplyJobTest.php`
	- Impacted: `tests/Integration/Misc/WpApiServiceTest.php` (28 tests) -> `tests/Unit/Misc/WpApiServiceTest.php`
	- Impacted: `tests/Integration/Misc/OAuthTest.php` (14 tests) -> `tests/Unit/Misc/OAuthTest.php`
	- PureUnit conversion to satisfy `UnitFrameworkBootingGuardTest`: both migrated files use explicit container stubs (`config`, `http`, `log`, `url`, `redirect`) with `Facade::clearResolvedInstances()` isolation
	- Deferred intentionally: `tests/Integration/Misc/MailHelperReplaceMailVarsTest.php` and `tests/Integration/Misc/DraftTest.php`
- Validation: migrated scope green via `php artisan test --filter="WpApiServiceTest|OAuthTest" --parallel --processes=4` (42 passed)
- Validation: guard green via `php artisan test --filter="TestSuiteGuard|UnitTestGuard" --parallel --processes=4`
- Full suite status: `php artisan test --parallel --processes=10` returned 1 pre-existing unrelated Mockery alias flake in `tests/Unit/Models/UserPermissionLogicTest.php`
- Updated baseline snapshot: Unit=49, Integration=208 (`reports/testing-baseline-2026-03-24.md`)
- Wave 9 completed by migrating Http/RequestsAndNotificationsTest to unit scope:
	- Collision-safe scope: avoided Unit/Models, Unit/Services, Unit/Policies, and 4 actively-modified unit files
	- Impacted: `tests/Integration/Http/RequestsAndNotificationsTest.php` (60 tests) → `tests/Unit/Http/RequestsAndNotificationsTest.php`
	- Pattern: `Translator(ArrayLoader) + ValidationFactory` bound to PureUnit container satisfies `Validator::make()` facade with zero framework boot
	- Pattern: anonymous `PresenceVerifierInterface` stub (count=1/0) replaces single `User::factory()->create()` for `unique:users,email` tests
	- All 60 tests pass in 0.26s; guard threshold (4 framework-booting files) unchanged
- Validation: full suite 5780 passed (1 pre-existing Mockery alias flake unrelated to this wave)
- Updated baseline snapshot: Unit=52, Integration=207 (`reports/testing-baseline-2026-03-24.md`)
- Wave 10 completed by migrating SmtpService + EmailModel tests to unit scope:
        - Collision-safe scope: `Unit/Services/SmtpServiceComprehensiveTest.php` targets public `validateSettings()` (different from the other agent's `SmtpServicePureLogicTest.php` which covers protected methods); `Unit/EmailModelEnhancedTest.php` in `Tests\Unit` namespace avoids all agent collision zones
        - Impacted: `tests/Integration/Services/SmtpServiceComprehensiveTest.php` (38 tests) → `tests/Unit/Services/SmtpServiceComprehensiveTest.php`
        - Impacted: `tests/Integration/EmailModelEnhancedTest.php` (31 tests) → `tests/Unit/EmailModelEnhancedTest.php` (26 tests; 5 DB/factory tests omitted)
        - Pattern: Mockery spy on `Psr\Log\LoggerInterface` bound to container; `shouldHaveReceived()` asserts post-invocation — avoids `Log::shouldReceive()` facade-mock ceremony
        - Pattern: `forceFill(['id' => 42, ...])` replaces `Mailbox::factory()->create()` for testConnection tests; Mail facade intentionally unbound so `BindingResolutionException` exercises `catch(\Exception)` handler giving deterministic failure path
        - Pattern: Eloquent datetime casts require `getConnection()` for date format → narrowed to integer-cast assertions only in PureUnit; full datetime cast test remains in Integration suite
        - All 64 tests pass in 0.12s; guard threshold (4 framework-booting files) unchanged
- Updated baseline snapshot: Unit=54, Integration=207 (`reports/testing-baseline-2026-03-24.md`)

- [ ] Identify deterministic integration assertions that can be moved to pure unit scope.
- [ ] Migrate policy/service/value-object logic to `tests/Unit` with `PureUnitTestCase` where possible.
- [ ] Remove duplicate integration scenarios that differ only by static fixture values.
- [ ] Keep one canonical end-to-end contract test per external boundary.
- [ ] Re-run `php artisan test --parallel --processes=10` after each migration wave.
- [ ] Confirm `tests/Unit/UnitFrameworkBootingGuardTest.php` and `tests/Unit/FeatureWriteAssertionDepthGuardTest.php` remain green.
- [ ] Recalculate layer distribution and compare against target pyramid bands.

## Autonomous Agent Contribution Mode

When an LLM agent is working in this repository, autonomous execution is expected for:
- read-only inspection
- minimal focused edits
- non-destructive test runs
- report-log analysis

The agent should pause only when:
- requirements are ambiguous
- a major architecture decision is required
- a change would alter business behavior rather than test quality

## Ownership

- QA Lead: policy and cadence stewardship
- Module Owners: test quality in their modules
- Reviewers: enforce layer placement and isolation compliance

Last updated: 2026-03-23
