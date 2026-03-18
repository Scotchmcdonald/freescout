# Test Suite 10/10 Sprint 01 Board

> Date: 2026-03-18
> Horizon: 1 sprint
> Objective: move the suite from ~8.0/10 to ~8.8-9.0/10
> Source documents:
> - docs/development/WIP/TEST_SUITE_10_OF_10_ROADMAP.md
> - docs/development/WIP/TEST_SUITE_8_OF_10_TASK_BOARD.md

## Sprint Goal

This sprint is not trying to reach 10/10 outright. It is designed to clear the next hard gate:

- prove parallel reliability
- eliminate the final hollow-test cluster
- reduce brittle assertion noise significantly
- deepen architecture enforcement beyond basic forbidden-import checks

If this sprint lands cleanly, the suite should move from roughly 8.0/10 to 8.8-9.0/10.

## Sprint Metrics

Starting baseline:

- browser files: 68
- assertSee-style assertions: 317
- PHP test files: 573
- hollow tests using `expectNotToPerformAssertions(...)`: 18

Sprint exit targets:

- 3 consecutive green parallel runs
- hollow tests: 0
- assertSee-style assertions: <= 250
- architecture contract checks expanded and green

## Agent A Sprint Lane

Agent A owns reliability and signal cleanup.

### A-1 Parallel Reliability Baseline

Status: not started

Scope:

- run the full parallel suite repeatedly
- capture deterministic failure signatures
- fix only reliability blockers, bootstrap defects, and parallel execution issues

Acceptance:

- 3 consecutive green `php artisan test --parallel` runs
- latest `reports/test-results-latest.log` shows zero failures

Deliverables:

- short failure log summary if anything breaks
- exact fixes applied
- run history proving repeatability

### A-2 Hollow Test Elimination Pass

Status: not started

Primary files:

- tests/Unit/HandleNewMessageListenerTest.php
- tests/Unit/SendConversationReplyJobTest.php
- tests/Unit/Listeners/AdditionalListenersTest.php
- tests/Unit/Listeners/SendReplyToCustomerTest.php
- tests/Integration/Controllers/Auth/EmailVerificationReminderControllerTest.php

Task:

- replace `expectNotToPerformAssertions()` with concrete side-effect assertions
- where the scenario has no real assertion value, delete the scenario rather than keeping a hollow test

Acceptance:

- hollow-test count reduced from 18 to 0
- touched files have focused targeted test runs proving behavior

### A-3 Brittle Assertion Reduction Pass

Status: not started

Primary targets:

- tests/Feature/** high-copy files
- tests/Integration/ClientPortal/PortalInvoiceFlowTest.php follow-up pass if needed
- tests/Integration/PIB/InvoicePdfAndShowTest.php follow-up pass if needed
- tests/Integration/PIB/RecordPaymentFlowTest.php follow-up pass if needed
- selected module browser and feature tests where state assertions can replace copy assertions

Task:

- replace copy-string assertions with view, route, status, payload, persisted-state, and permission-contract assertions where possible

Acceptance:

- assertSee-style count reduced from 317 to <= 250
- no loss of real access-control or workflow coverage

### A-4 Duplicate And Legacy Cleanup (Only If A-1 to A-3 Finish Early)

Status: stretch

Candidates:

- remaining duplicate Pest/PHPUnit files in module suites
- placeholder smoke tests and route-copy tests that still provide near-zero value

Acceptance:

- file-count reduction without new coverage gaps

## Agent B Sprint Lane

Agent B owns architecture depth and high-trust contract coverage.

### B-1 Module Contract Enforcement Expansion

Status: complete

Primary file:

- tests/Architecture/ModuleContracts/ModuleBoundaryContractsTest.php

Task:

- expand current regex-based checks into stronger contract-oriented tests
- add required-interface assertions for key seams
- add explicit diagnostics that identify offending files and symbols

Modules in scope:

- ContractManager
- GoogleAdmin
- Action1
- SoftwareSubscriptions

Acceptance:

- at least 6 new meaningful contract assertions added
- failures are actionable and low-noise

### B-2 Cross-Module Workflow Contract Tests

Status: complete

Task:

- add one focused integration verification for each of these chains:
- Action1 -> AssetManagement -> SoftwareSubscriptions
- GoogleAdmin -> Crm -> CaseManager
- ContractManager -> PIB -> Payment

Guidelines:

- verify business outcome or persisted state
- avoid browser/UI assertions
- use fakes only at true external boundaries

Acceptance:

- 3 new high-value workflow contract tests exist and pass

### B-3 Boundary Failure Mapping Pass

Status: complete

Primary files:

- tests/Integration/Boundaries/Action1ApiContractTest.php
- tests/Integration/Boundaries/GoogleWorkspaceApiContractTest.php
- tests/Integration/Boundaries/PaymentGatewayAdapterTest.php

Task:

- extend failure-path coverage for malformed payloads, partial payloads, 429s, and exception mapping
- assert typed failure behavior where applicable

Acceptance:

- each boundary suite gains at least one stronger failure-contract case
- no live HTTP calls

### B-4 Domain Invariant Follow-Up (Only If B-1 to B-3 Finish Early)

Status: in-progress (B-1 through B-3 complete, B-4 is next)

Candidates:

- additional ContractManager proration and amendment invariants
- Payment dispute/reconciliation edge guards
- SoftwareSubscriptions assignment/reconciliation invariants

Acceptance:

- new coverage materially strengthens business-rule confidence

## Coordination Rules

### Sync Point 1

After A-1 and B-1:

- Agent A reports any architecture/reliability conflicts uncovered during repeated parallel runs
- Agent B adjusts contract checks if they create false positives against legitimate runtime wiring

### Sync Point 2

After A-2 and B-2:

- Agent A confirms hollow-test replacements do not overlap with new workflow contract tests
- Agent B confirms any new workflow tests are not reintroducing brittle assertion patterns

### Sync Point 3

Before sprint close:

- rerun counts for browser files, assertSee-style assertions, PHP test files, and hollow tests
- run 3 consecutive parallel full-suite runs
- re-score the suite

## Recommended Commands

Agent A verification:

- `php artisan test --parallel`
- `php artisan test tests/Unit/HandleNewMessageListenerTest.php tests/Unit/SendConversationReplyJobTest.php`
- `php artisan test tests/Unit/Listeners/AdditionalListenersTest.php tests/Unit/Listeners/SendReplyToCustomerTest.php`
- `php artisan test tests/Integration/Controllers/Auth/EmailVerificationReminderControllerTest.php`

Agent B verification:

- `php artisan test tests/Architecture/ModuleContracts/ModuleBoundaryContractsTest.php`
- `php artisan test tests/Integration/Boundaries`
- `php artisan test Modules/ContractManager/Tests/Integration`

Shared metric checks:

- `find tests Modules -type f \( -path 'tests/Browser/*' -o -path 'Modules/*/Tests/Browser/*' \) | wc -l`
- `grep -RInE 'assertSee\(|assertSeeText\(|assertSeeInOrder\(' tests Modules | wc -l`
- `grep -RIn "expectNotToPerformAssertions(" tests Modules | wc -l`

## Ready-To-Launch Agent Prompt: Agent A

You are Agent A for Sprint 01 of the 10/10 test suite plan. Work only from docs/development/WIP/TEST_SUITE_10_OF_10_SPRINT_01_BOARD.md.

Your priorities, in order:

1. A-1 Parallel Reliability Baseline
2. A-2 Hollow Test Elimination Pass
3. A-3 Brittle Assertion Reduction Pass
4. A-4 Duplicate And Legacy Cleanup only if time remains

Rules:

- Make atomic commits per ticket.
- Run focused tests after each ticket.
- Do not revert unrelated dirty workspace changes.
- Replace hollow tests with real assertions or delete them if they have no signal.
- Prefer state, permission, payload, and route-contract assertions over UI-copy assertions.

Required report back after each ticket:

- files changed
- tests run
- pass/fail summary
- updated counts for hollow tests or assertSee-style assertions when relevant
- next recommended ticket

## Ready-To-Launch Agent Prompt: Agent B

You are Agent B for Sprint 01 of the 10/10 test suite plan. Work only from docs/development/WIP/TEST_SUITE_10_OF_10_SPRINT_01_BOARD.md.

Your priorities, in order:

1. B-1 Module Contract Enforcement Expansion
2. B-2 Cross-Module Workflow Contract Tests
3. B-3 Boundary Failure Mapping Pass
4. B-4 Domain Invariant Follow-Up only if time remains

Rules:

- Make atomic commits per ticket.
- Run focused tests after each ticket.
- Do not use live external HTTP.
- Use integration tests for workflow seams and architecture tests for contract enforcement.
- Avoid brittle UI-copy assertions entirely in this lane.

Required report back after each ticket:

- files changed
- tests run
- pass/fail summary
- new contract or workflow guarantees added
- next recommended ticket
