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
- adding framework-booting Unit tests requires temporary exception metadata (owner, issue, rationale, expiry)
- all skip exceptions must be explicitly tracked in the skip-governance allowlist

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
- Validation: full suite green via `php artisan test --parallel --processes=10` (2 skipped, 5730 passed)

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
