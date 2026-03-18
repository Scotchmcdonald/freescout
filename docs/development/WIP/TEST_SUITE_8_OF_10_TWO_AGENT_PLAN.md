# Two-Agent Test Suite Plan — 6/10 to 8/10

> Created: 2026-03-17
> Baseline: 568 PHP test files, 68 browser test files, 376 assertSee-style assertions
> Latest known suite state: 5710 passing, 20 failing, 2 skipped
> Target: 8/10 or better on robustness, speed, and trustworthiness
> Companion docs:
> - docs/development/WIP/TEST_SUITE_REMEDIATION.md
> - docs/testing/TEST_SUITE_STRATEGY.md

## Objective

Raise the suite from a noisy, top-heavy 6/10 to at least 8/10 by running two agents in parallel:

- Agent A owns suite stability, junk removal, duplication reduction, and CI/runtime improvements.
- Agent B owns high-value coverage, boundary testing, and module-isolation hardening.

This split is intentional. Agent A removes waste and restores signal. Agent B adds the missing tests that actually increase confidence. Either lane alone improves the suite, but both together are what gets the suite to 8/10.

## Definition Of 8/10

The suite is considered 8/10 when all of the following are true:

- Full suite passes with zero known bootstrap or factory failures.
- The test pyramid is materially improved: fewer brittle browser and page-copy checks, more unit and integration tests around domain logic.
- External integration coverage is done through boundary contracts and HTTP fakes, not live calls or over-mocked service internals.
- Module seams are tested through contracts or explicit integration paths, not ad hoc cross-module persistence in the wrong layer.
- CI duration drops materially, with the biggest gains coming from junk-test deletion, duplicate removal, and reduced DB-heavy testing.

## Current Blockers

These must be treated as active prerequisites because they undermine trust in the suite:

1. `Modules/TestModule/module.json` is missing and breaks bootstrap in multiple system tests.
2. `Modules/SoftwareSubscriptions/Tests/Integration/SubscriptionCounterServicePestTest.php` currently fails due to a factory state/type problem (`array_merge(): Argument #2 must be of type array, int given`).
3. The suite still contains a large volume of low-value UI-copy and framework tests.
4. Several modules still lean on DB-heavy or over-mocked tests where contract-level coverage is more appropriate.

## Parallel Execution Model

### Agent A — Stability And Signal

Agent A works the suite from the bottom up. The focus is to restore a dependable baseline, cut noise, and reduce runtime.

#### A1. Restore Green Baseline

- Fix the `Modules/TestModule/module.json` bootstrap failure or make module discovery tolerate incomplete test fixtures.
- Fix the SoftwareSubscriptions factory/state bug behind the `SubscriptionCounterServicePestTest` failures.
- Re-run only the affected suites first, then revalidate the full suite.

Acceptance:

- No `FileNotFoundException` for `Modules/TestModule/module.json` during test bootstrap.
- `Modules/SoftwareSubscriptions/Tests/Integration/SubscriptionCounterServicePestTest.php` passes.
- Full suite returns to zero known failures before cleanup work expands.

#### A2. Delete Junk Tests

- Remove tests that primarily verify Eloquent metadata, fillable arrays, trait presence, constant existence, or route/view copy.
- Prioritize the audit cut-list items already identified in the remediation work.
- Eliminate hollow “does not throw” tests and placeholder browser tests.

Priority targets:

- `tests/Feature/Crm/ClientMassAssignmentTest.php`
- the `$fillable` audit portion of `tests/Feature/System/DatabaseIntegrityPestTest.php`
- `tests/Integration/Models/ModelRelationshipsTest.php` low-value ORM cases
- `Modules/Crm/Tests/Integration/DynamicRelationshipPestTest.php` reflection/source-string assertions
- route/view copy smoke tests that only prove labels exist

Acceptance:

- Remove or rewrite at least 40 low-value tests in the first pass.
- Reduce `assertSee*` assertion count from 376 to below 250.
- No loss of business-logic coverage from deleted files.

#### A3. Collapse Duplicates

- Remove PHPUnit/Pest duplicates and overlapping legacy files.
- Consolidate duplicate model and listener tests into one canonical file per behavior.
- Standardize on the dominant style already used in each area.

Known duplication targets:

- `Modules/SoftwareSubscriptions/Tests/Unit/OffboardingTicketListenerPestTest.php`
- `Modules/SoftwareSubscriptions/Tests/Unit/OffboardingTicketListenerTest.php`
- `Modules/SoftwareSubscriptions/Tests/Feature/VendorCostReportPestTest.php`
- `Modules/SoftwareSubscriptions/Tests/Feature/VendorCostReportTest.php`

Acceptance:

- Remove the obvious duplicate pairs in SoftwareSubscriptions.
- Reduce total PHP test-file count from 568 toward 500 or lower without lowering meaningful coverage.

#### A4. Rebalance The Pyramid For Speed

- Move browser-only smoke checks and label assertions out of critical CI where possible.
- Convert DB-heavy “unit” tests to proper integration tests or pure unit tests, depending on behavior.
- Enforce `Tests\PureUnitTestCase` for logic that does not need the container or DB.

Acceptance:

- Browser smoke surface is trimmed to one or two high-value paths per area.
- Fewer DB-backed tests exist in Unit namespaces.
- CI wall-clock time shows a measurable reduction from the current baseline.

### Agent B — Confidence And Coverage

Agent B works the suite from the outside in. The focus is to increase real confidence in business logic, module contracts, and external boundaries.

#### B1. Build Boundary Test Layer

- Create or expand boundary-level integration tests for external APIs using `Http::fake()` and contract assertions.
- Cover Action1, GoogleAdmin, Payment, and AI/Gemini flows at the request/response boundary.
- Prefer adapter or gateway tests over over-mocking internal services.

Acceptance:

- Dedicated boundary suites exist for the major external integrations.
- No new external-integration tests use live outbound HTTP.
- Request shape, retry behavior, and failure mapping are asserted explicitly.

#### B2. Add High-Value Domain Tests

- Strengthen domain/service coverage in modules that are under-tested at the service layer.
- ContractManager is the highest-priority gap because it currently lacks meaningful Unit and Integration depth.
- Add tests for billing math, rent-to-own transitions, proration, ownership transfer, and failure-handling paths.

Acceptance:

- ContractManager gains a real Unit/Integration layer around domain services.
- At least 3 high-risk business rules per target module are covered with non-UI tests.
- These tests pass without depending on browser flows or page copy.

#### B3. Harden Module Isolation

- Replace direct cross-module persistence in the wrong layers with contract-based fixtures or allowed shared seams.
- Follow the SoftwareSubscriptions pattern where contracts abstract foreign-module lookups.
- Expand architecture checks where needed so isolation is enforced, not just implied.

Acceptance:

- New or refactored tests use contracts/service providers instead of direct foreign-model creation unless explicitly justified.
- Module-isolation guidance is encoded in tests, not only in documentation.
- Cross-module integration tests are limited to deliberate integration paths.

#### B4. Reduce Over-Mocking

- Refactor tests that mock the service under test instead of its true external boundary.
- Prioritize GoogleAdmin, Action1, and similar job/action flows where service mocks can produce false positives.
- Keep event/queue fakes where dispatch is the behavior under test, but avoid mocking away real domain logic.

Acceptance:

- High-risk job/action tests execute real orchestration code against faked HTTP or boundary contracts.
- False-positive risk is reduced in GoogleAdmin and Action1 feature/integration suites.

## Delivery Waves

### Wave 1 — Unblock And Stabilize

Runs in parallel immediately.

- Agent A: A1
- Agent B: B1 design and scaffolding

Gate to leave Wave 1:

- Suite is green again or has only intentionally quarantined failures.
- Boundary-testing pattern is agreed and proven in at least one module.

### Wave 2 — Remove Waste While Adding Confidence

Runs in parallel after Wave 1 is stable.

- Agent A: A2 and A3
- Agent B: B2 and B4

Gate to leave Wave 2:

- Duplicates removed in the highest-noise areas.
- At least one major under-tested domain area gains meaningful non-UI coverage.

### Wave 3 — Structural Hardening

Runs after the high-churn deletions and additions settle.

- Agent A: A4
- Agent B: B3

Gate to complete Wave 3:

- The pyramid is visibly healthier.
- Module seams are better protected by tests and contracts.

## Sync Points

The agents should not work fully independently. These handoff points prevent rework:

1. After A1, Agent A publishes the exact bootstrap and factory fixes so Agent B does not build on broken assumptions.
2. After B1, Agent B publishes the standard external-boundary pattern so Agent A can preserve it while deleting older low-value tests.
3. Before A3 duplicate deletion, Agent B confirms which duplicate files still contain unique coverage worth preserving.
4. Before B3 isolation hardening, Agent A confirms which legacy tests remain and which ones were deleted or moved.

## Suggested Work Breakdown By Week

### Week 1

- Agent A: Fix bootstrap and factory failures, re-establish green baseline.
- Agent B: Stand up boundary-testing pattern in one external integration.

### Week 2

- Agent A: Delete junk tests and collapse the highest-confidence duplicate pairs.
- Agent B: Expand boundary tests and add first domain-service coverage set.

### Week 3

- Agent A: Reduce browser/copy noise and shift DB-heavy tests into the right layer.
- Agent B: Harden module seams and refactor over-mocked suites.

### Week 4

- Joint validation: measure runtime, review pyramid shape, identify remaining risk gaps, and decide whether one more pass is needed.

## Score Impact

Expected score lift by workstream:

- A1 plus A2: +0.8 to +1.0
- A3 plus A4: +0.4 to +0.6
- B1 plus B2: +0.8 to +1.0
- B3 plus B4: +0.3 to +0.5

Expected outcome if both lanes complete:

- Robustness score reaches 8.0 to 8.5.

## Final Exit Criteria

Do not call the plan complete until all of the following are true:

- Full suite passes cleanly.
- Known bootstrap and factory failures are gone.
- The highest-noise junk/duplicate tests are removed.
- At least one under-covered module, especially ContractManager, has meaningful service-level tests.
- External integrations are covered with boundary tests and HTTP fakes.
- The test pyramid is visibly less top-heavy.
- The updated strategy and conventions are reflected in the living docs under `docs/`.
