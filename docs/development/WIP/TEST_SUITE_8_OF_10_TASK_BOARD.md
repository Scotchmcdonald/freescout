# Test Suite 8/10 Task Board (Two-Agent Parallel)

> Date: 2026-03-17
> Source plan: docs/development/WIP/TEST_SUITE_8_OF_10_TWO_AGENT_PLAN.md
> Goal: Raise suite quality from ~6/10 to >=8/10 with two parallel lanes

## Operating Rules

- Agent A and Agent B run in parallel.
- Agent A owns stability and suite signal.
- Agent B owns high-value confidence coverage.
- Each task has a concrete output and acceptance gate.
- Merge order: Agent A Wave 1 first, then parallel merges in small batches.

## Shared Baseline Gates

- Gate S1: Full suite bootstrap must no longer fail on missing `Modules/TestModule/module.json`.
- Gate S2: `Modules/SoftwareSubscriptions/Tests/Integration/SubscriptionCounterServicePestTest.php` passes.
- Gate S3: `php artisan test --parallel` can run without known deterministic bootstrap/factory failures.

## Agent A Board (Stability + Signal)

### A-W1-01 Fix module bootstrap path failure

- Scope:
- `Modules/AssetManagement/Providers/AssetManagementServiceProvider.php`
- `Modules/ContractManager/Providers/RouteServiceProvider.php`
- `Modules/KnowledgeBase/app/Providers/RouteServiceProvider.php`
- Any shared module-loader/helper touched by the above
- Output: Safe handling for absent `Modules/TestModule/module.json` in test contexts.
- Acceptance:
- `Tests/Feature/System/SystemAccessPestTest` no longer fails with `FileNotFoundException` for TestModule.

### A-W1-02 Fix SoftwareSubscriptions factory type error

- Scope:
- `Modules/SoftwareSubscriptions/Tests/Integration/SubscriptionCounterServicePestTest.php`
- Related module factory files used by that test
- Output: Factory state callbacks return arrays consistently.
- Acceptance:
- All tests in `Modules/SoftwareSubscriptions/Tests/Integration/SubscriptionCounterServicePestTest.php` pass.

### A-W2-01 Remove low-value framework tests (batch 1)

- Scope:
- `tests/Feature/Crm/ClientMassAssignmentTest.php`
- `tests/Feature/System/DatabaseIntegrityPestTest.php` (remove fillable/schema trivia assertions only)
- `tests/Integration/Models/ModelRelationshipsTest.php` (drop ORM/framework-only cases)
- `Modules/Crm/Tests/Integration/DynamicRelationshipPestTest.php` (drop trait/reflection/source-string checks)
- Output: Junk test volume reduced while preserving business coverage.
- Acceptance:
- No business-regression failures introduced by removals.

### A-W2-02 Collapse duplicate test files (batch 1)

- Scope:
- `Modules/SoftwareSubscriptions/Tests/Unit/OffboardingTicketListenerPestTest.php`
- `Modules/SoftwareSubscriptions/Tests/Unit/OffboardingTicketListenerTest.php`
- `Modules/SoftwareSubscriptions/Tests/Feature/VendorCostReportPestTest.php`
- `Modules/SoftwareSubscriptions/Tests/Feature/VendorCostReportTest.php`
- Output: One canonical file per behavior.
- Acceptance:
- Duplicate pair count reduced for these files to zero.

### A-W3-01 Reduce brittle UI-copy assertions

- Scope:
- High-churn copy tests under `tests/Feature/**` and `tests/Browser/**`
- Module browser suites under `Modules/*/Tests/Browser/**`
- Output: Replace copy-string assertions with state/behavior assertions where feasible; remove low-signal checks.
- Acceptance:
- `assertSee*` count reduced materially from current baseline.

## Agent B Board (Coverage + Confidence)

### B-W1-01 Stand up external boundary test pattern

- Scope:
- Create `tests/Integration/Boundaries/`
- Initial suites:
- `tests/Integration/Boundaries/Action1ApiContractTest.php`
- `tests/Integration/Boundaries/GoogleWorkspaceApiContractTest.php`
- `tests/Integration/Boundaries/PaymentGatewayAdapterTest.php`
- Output: Reusable pattern for request/response contract tests with `Http::fake()`.
- Acceptance:
- At least one boundary suite merged and green.

### B-W2-01 Add domain-depth coverage for ContractManager

- Scope:
- Add `Modules/ContractManager/Tests/Unit/`
- Add `Modules/ContractManager/Tests/Integration/`
- Focus behaviors:
- Rent-to-own transitions
- Ownership transfer edge handling
- Billing/proration invariants
- Output: Service/domain tests independent of UI copy.
- Acceptance:
- New non-UI tests cover at least 3 high-risk rules.

### B-W2-02 Refactor over-mocked integration hotspots

- Scope:
- `Modules/GoogleAdmin/Tests/Feature/SyncGoogleUsersJobPestTest.php`
- `Modules/GoogleAdmin/Tests/Feature/SyncGoogleChromebooksJobPestTest.php`
- `Modules/GoogleAdmin/Tests/Feature/UserProvisioningActionPestTest.php`
- `Modules/Action1/Tests/Feature/SyncAction1DevicesJobPestTest.php`
- Output: Exercise real orchestration logic with boundary fakes instead of mocking service-under-test internals.
- Acceptance:
- Tests assert concrete outcome mapping and failure behavior, not only dispatch.

### B-W3-01 Harden module-isolation contracts

- Scope:
- Expand architecture/contract checks under `tests/Architecture/`
- Refactor cross-module tests to use contract providers where appropriate
- Output: Better seam testing, less direct foreign-model persistence in wrong layers.
- Acceptance:
- New/updated isolation checks pass and block reintroduction of known anti-patterns.

## Sync And Merge Cadence

- Sync 1 (after A-W1-01 and A-W1-02): Agent A publishes baseline fix notes and rerun evidence.
- Sync 2 (after B-W1-01): Agent B publishes boundary-test template for reuse.
- Sync 3 (after A-W2 and B-W2): Joint check for overlap before deleting/refactoring remaining tests.
- Final Sync: Full-suite run, score reassessment, and final gap list to hit >=8/10.

## Scorecard Targets

- Robustness score: >=8/10.
- Deterministic failures from known blockers: 0.
- Duplicate file pairs in prioritized set: 0.
- `assertSee*` noise reduced substantially from baseline.
- More domain/service tests in high-risk modules, especially ContractManager.
