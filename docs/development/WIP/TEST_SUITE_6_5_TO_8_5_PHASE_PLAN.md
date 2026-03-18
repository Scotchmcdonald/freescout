# Test Suite Phase Plan: 6.5 to 8.5

> Date: 2026-03-18
> Baseline score: 6.5/10
> Target score: 8.5/10
> Scope: Laravel core plus Modules/* test ecosystem

## Purpose

This plan defines the minimum set of high-leverage phases to move the suite from 6.5 to 8.5 while preserving business confidence and reducing CI cost.

Key principles:

- Increase signal before adding volume.
- Move assertions down the pyramid (unit and integration first).
- Keep browser and UI-copy tests only for true UX risk.
- Enforce module boundaries through contracts, not cross-module persistence.

## Success Definition (8.5)

The suite reaches 8.5 when all are true:

1. Reliability: 3 consecutive green parallel runs on the default CI lane.
2. Signal quality: framework-only tests reduced materially, with no critical business behavior lost.
3. Pyramid balance: high-value rules covered primarily in unit and integration layers.
4. Boundary confidence: external API behavior validated with Http fakes and contract assertions.
5. Module integrity: boundary guardrails prevent new cross-module coupling in unit tests.

## Baseline Risk Snapshot

From recent audit and repo evidence:

- 577 test files.
- 231 files using RefreshDatabase.
- 240 assertSee/assertSeeText checks and 125 assertViewHas assertions.
- 33 makePartial usages (false-confidence risk).
- Repeated low-value relationship/fillable/cast checks in model tests.

## Phase Overview

Phase 0 to 4 is the shortest practical path from 6.5 to 8.5.

| Phase | Goal | Score Lift | Duration |
|---|---|---:|---|
| 0 | Stabilize baseline and metrics | +0.2 | 1-2 days |
| 1 | Remove junk and brittle noise | +0.5 | 3-5 days |
| 2 | Rebalance pyramid and speed | +0.6 | 5-8 days |
| 3 | Fill critical business coverage gaps | +0.8 | 6-10 days |
| 4 | Lock reliability and governance | +0.4 | 3-5 days |

Projected total: +2.5 points (6.5 -> 9.0 potential), with 8.5 as the quality gate for completion.

---

## Phase 0: Baseline And Guardrails (6.5 to 6.7)

### Objective

Create a trusted baseline and prevent accidental regression while refactors begin.

### Work Items

1. Establish scorecard metrics in one place:
   - file count
   - RefreshDatabase count
   - assertSee/assertSeeText count
   - assertViewHas count
   - makePartial count
   - failing test count
2. Run a single full parallel suite and archive result reference in reports/.
3. Confirm architecture guards are green and expand existing no-fly checks if missing:
   - unit tests should not assert Eloquent relation class types
   - unit tests should not persist cross-module models
4. Label flaky candidates as triage items (do not skip by default).

### Exit Criteria

- Baseline scorecard documented.
- Full run artifacts captured and searchable.
- Guard tests are enabled and passing.

---

## Phase 1: Cut List Execution (6.7 to 7.2)

### Objective

Delete or refactor low-value tests that verify framework mechanics or fragile UI copy rather than domain behavior.

### Work Items

1. Remove framework-trivia tests:
   - relationship class assertions
   - fillable/cast-only assertions
   - model instantiation-only tests
2. Remove or rewrite brittle UI-copy checks:
   - replace broad assertSee chains with behavior/state assertions
   - keep copy assertions only where content is a product requirement
3. Consolidate duplicate files and duplicate scenarios in module suites.
4. Eliminate expectNotToPerformAssertions-style hollow tests in critical paths.

### Expected Outcome

- CI runtime drops due to less DB and browser churn.
- Fewer false failures after harmless view text changes.

### Exit Criteria

- At least 20% reduction in framework-only test volume.
- assertSee/assertSeeText reduced by at least 25% from baseline.
- No net loss in business-rule coverage for modified domains.

---

## Phase 2: Pyramid Rebalance And Performance (7.2 to 7.8)

### Objective

Shift expensive integration-heavy checks into fast deterministic unit or narrow integration tests.

### Work Items

1. Move service logic out of DB-backed unit tests:
   - replace RefreshDatabase in unit folders with pure collaborators/factories
   - keep DB only where persistence is part of the behavior under test
2. Split test lanes in CI:
   - Lane A: unit plus fast integration (PR gate)
   - Lane B: module integration and cross-module event tests
   - Lane C: browser smoke and UX-critical flows
3. Reduce over-mocking risk:
   - replace makePartial in hotspot tests with boundary fakes or full integration seams
4. Convert cross-module direct persistence to contract-level seams where possible.

### Expected Outcome

- Faster feedback loops and fewer false positives.
- Better alignment with testing pyramid principles.

### Exit Criteria

- RefreshDatabase usage reduced meaningfully in unit scopes.
- makePartial usage reduced by at least 50% in critical modules.
- PR gate lane completes with stable duration and no deterministic failures.

---

## Phase 3: Critical Business Logic Deep Coverage (7.8 to 8.6)

### Objective

Add high-value tests for services with complex financial, workflow, and cross-module behavior.

### Priority Targets

1. ContractManager service rules:
   - QuoteService state transitions (draft/sent/viewed/approved)
   - revision integrity and quote-to-contract invariants
2. BillingTemplateService:
   - pause/resume/manual trigger rules and date-rollover correctness
3. SoftwareSubscriptions:
   - VendorReconciliationService mismatch logic, dry-run safety, transactional fixes
   - LicenseDeploymentService retry, idempotency, event integrity
4. PIB analysis and billing math:
   - BillingAnalysisService unusual-threshold boundaries and percent-change math
   - retain and extend ProrationService/InvoiceGenerator invariants where needed

### Test Design Standards

- Focus on invariants, not implementation details.
- Add boundary and failure-path tests for external integration adapters.
- Validate event side effects and idempotency on retry paths.

### Exit Criteria

- Each priority target has direct service-level tests and negative-path coverage.
- High-risk financial/workflow branches have deterministic assertions.
- No new coupling violations introduced by new tests.

---

## Phase 4: Reliability Proof And Governance (8.6 to 8.5+ sustained)

### Objective

Lock in gains so suite quality stays above 8.5 as code evolves.

### Work Items

1. Execute 3 consecutive full parallel runs with no deterministic failures.
2. Add governance checks:
   - fail on new framework-trivia patterns in protected folders
   - fail on new hotspot makePartial patterns
   - fail on missing Http safety in external API-facing tests
3. Publish testing contribution rules for module teams:
   - when to use UnitTestCase vs IntegrationTestCase vs browser
   - acceptable assertion patterns and anti-pattern examples
4. Create quarterly maintenance routine:
   - stale/flaky test triage
   - duplicate test scan
   - scorecard refresh

### Exit Criteria

- 3x consecutive green full parallel runs.
- Guardrails block reintroduction of removed anti-patterns.
- Team-facing standards are documented and discoverable.

---

## Execution Model (Recommended)

Two-lane execution for speed:

- Lane A (Stability and Signal): Phase 0, 1, and Phase 4 guardrails.
- Lane B (Coverage Depth): Phase 2 and 3 service-level and boundary tests.

Merge strategy:

1. Merge Phase 0 baseline and guardrails first.
2. Merge deletions/refactors in small batches per module.
3. Merge service coverage by domain (ContractManager, Payment, PIB, SoftwareSubscriptions).
4. Run full proof sequence at the end of each phase.

## Scorecard Template

Track this table weekly until completion:

| Metric | Baseline | Current | Target |
|---|---:|---:|---:|
| Robustness score | 6.5 | - | 8.5 |
| Total test files | 577 | - | <= 540 (without business coverage loss) |
| RefreshDatabase files | 231 | - | <= 170 |
| assertSee/assertSeeText | 240 | - | <= 150 |
| assertViewHas | 125 | - | <= 80 |
| makePartial | 33 | - | <= 10 |
| Consecutive green parallel runs | 0 | - | 3 |

## Risks And Mitigations

1. Risk: deleting too aggressively removes useful confidence.
   - Mitigation: delete in bounded batches with immediate suite verification.
2. Risk: over-mocking reduction increases test complexity.
   - Mitigation: standardize boundary fake helpers and fixtures.
3. Risk: module ownership conflicts on cross-module tests.
   - Mitigation: require contracts and adapter seams for inter-module behavior.
4. Risk: CI runtime spikes during transition.
   - Mitigation: keep lane split and monitor runtime regressions each phase.

## Completion Checklist

- [ ] Phase 0 complete
- [ ] Phase 1 complete
- [ ] Phase 2 complete
- [ ] Phase 3 complete
- [ ] Phase 4 complete
- [ ] Suite score >= 8.5 with evidence attached
