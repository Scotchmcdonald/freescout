# Test Suite Roadmap To 10/10

> Date: 2026-03-18
> Scope: Laravel monolith plus Modules/* test ecosystem
> Input sources: latest reports, architecture tests, recent Agent A and Agent B deliveries

## Reassessment (Current State)

### Current score

- Estimated robustness score: 8.0/10.

### Why this is now near 8/10

- External boundary suites now exist:
  - tests/Integration/Boundaries/Action1ApiContractTest.php
  - tests/Integration/Boundaries/GoogleWorkspaceApiContractTest.php
  - tests/Integration/Boundaries/PaymentGatewayAdapterTest.php
- ContractManager gained meaningful unit and integration depth:
  - Modules/ContractManager/Tests/Unit/RentToOwnInvariantsPestTest.php
  - Modules/ContractManager/Tests/Integration/RentToOwnEdgeGuardsTest.php
  - Modules/ContractManager/Tests/Integration/OwnershipTransferContractTest.php
  - Modules/ContractManager/Tests/Integration/ProrationContractRevisionTest.php
- Module contract architecture checks now exist:
  - tests/Architecture/ModuleContracts/ModuleBoundaryContractsTest.php
- Brittle copy assertions were reduced recently.

### Remaining risk debt (prevents 9-10/10)

- Parallel reliability is not yet proven as green in a full uninterrupted run.
- Suite still has substantial noise:
  - browser_files: 68
  - assertSee_count: 317
  - test_files: 573
  - expectNotToPerformAssertions count: 18
- There is still room to shift from UI-copy checks to behavior contracts.
- Architecture contract checks are present but still basic and regex-oriented.

## 10/10 Definition

The suite is 10/10 only when all of the following are true:

1. Reliability:
   - 10 consecutive parallel full-suite runs complete with zero deterministic failures and zero flakes.
2. Signal quality:
   - no hollow tests relying on expectNotToPerformAssertions as primary success criteria.
   - brittle UI-copy assertions are a minority and used only where copy itself is a requirement.
3. Pyramid quality:
   - high-value business rules are covered primarily in unit and integration layers.
   - browser tests are focused on true cross-layer UX risks.
4. Boundary confidence:
   - all external integrations use contract tests plus strict stray-request prevention.
5. Architecture enforcement:
   - module boundaries are mechanically enforced with low false positives and actionable failure output.
6. Speed and maintainability:
   - CI duration remains stable and predictable under parallel runs.

## Roadmap (8.0 to 10.0)

## Phase 1: 8.0 to 8.8 (Reliability Hardening)

Goal: eliminate non-determinism and complete full-suite confidence loops.

Tasks:

- Run and record a full parallel suite baseline in one uninterrupted execution.
- Fix any remaining bootstrap and factory edge failures from that run.
- Add a flaky-test quarantine protocol for triage-only, not permanent hiding.
- Introduce a repeatability gate: 3 consecutive green parallel runs required before merge to main.

Acceptance:

- 3 consecutive green full parallel runs.
- No known deterministic failures in latest reports.

## Phase 2: 8.8 to 9.3 (Signal Quality And Noise Reduction)

Goal: remove low-value assertions and hollow tests.

Tasks:

- Remove or refactor all remaining expectNotToPerformAssertions-heavy tests into outcome assertions.
- Reduce assertSee and assertSeeText usage where behavior assertions are available.
- Collapse additional duplicate files and near-duplicate scenarios.
- Convert remaining framework-trivia tests into architecture checks or delete them.

Targets:

- expectNotToPerformAssertions count from 18 to 0.
- assertSee_count from 317 to 180 or lower.
- test_files from 573 toward 520 or lower without reducing business coverage.

Acceptance:

- No hollow-test debt remains.
- CI failures correspond to behavior regressions, not copy churn.

## Phase 3: 9.3 to 9.7 (Architecture And Module Integrity)

Goal: move from basic boundary regex checks to enforceable architectural contracts.

Tasks:

- Expand tests/Architecture/ModuleContracts with:
  - forbidden cross-module service resolution in non-integration layers.
  - required contract-interface usage for selected cross-module seams.
  - explicit allowlist with expiry for any unavoidable exceptions.
- Add richer diagnostics in failures so developers know exact violating symbols.
- Add one integration verification per critical cross-module event chain:
  - Action1 -> AssetManagement -> SoftwareSubscriptions
  - GoogleAdmin -> Crm -> CaseManager
  - ContractManager -> PIB -> Payment

Acceptance:

- Architecture failures are actionable and low-noise.
- Cross-module seams are proven by contract and by integration behavior.

## Phase 4: 9.7 to 10.0 (Quality Engineering Excellence)

Goal: ensure tests detect subtle logic defects, not only common regressions.

Tasks:

- Introduce mutation testing on selected critical domains:
  - ContractManager rent-to-own invariants
  - Payment settlement and dispute handling
  - SoftwareSubscriptions reconciliation counters
- Add property-based tests for calculations and invariant-heavy workflows.
- Add performance budgets for test suites with regression alerts.
- Add test impact analysis workflow so changed modules run targeted high-value suites first.

Targets:

- Mutation score threshold for critical domains: 70 percent or higher initially, then 80 percent.
- 10 consecutive green parallel runs.
- Stable CI runtime variance within a narrow band release-over-release.

Acceptance:

- Mutation and property-based tests catch seeded defects.
- No recurring flaky tests for two release cycles.

## Parallel Ownership Through 10/10

### Agent A focus

- Phase 1 and Phase 2 heavy lifting:
  - reliability stabilization
  - hollow-test and brittle-assertion removal
  - duplicate consolidation
  - runtime predictability

### Agent B focus

- Phase 3 and Phase 4 heavy lifting:
  - architecture contract deepening
  - cross-module event-chain verification
  - mutation and property-based quality gates

### Joint cadence

- Weekly quality checkpoint with these reported metrics:
  - full-run pass and flake status
  - browser_files
  - assertSee_count
  - test_files
  - hollow-test count
  - mutation score for critical domains

## Immediate Next Sprint (High ROI)

1. Finish Phase 1 proof:
   - complete 3 uninterrupted parallel green runs and log outcomes.
2. Eliminate the final 18 hollow tests.
3. Reduce assertSee_count below 250 in one focused pass.
4. Expand ModuleContracts tests to enforce contract-interface usage, not only forbidden imports.

Completing this sprint should move the suite from roughly 8.0 to roughly 8.8 to 9.0.
