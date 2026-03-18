# Test Suite Master Phase Plan: 6.5 to 10.0

> Version: 1.0 — Second-Pass Unified Audit
> Date: 2026-03-18
> Scope: Laravel monolith + Modules/* test ecosystem
> Supersedes: `TEST_SUITE_6_5_TO_8_5_PHASE_PLAN.md` and `TEST_SUITE_10_OF_10_ROADMAP.md`
> (Those documents remain for historical reference; this is the single authoritative plan.)

---

## Part 1 — Validated Baseline Assessment

### Scan Results (Executed 2026-03-18)

| Metric | Count | Source |
|---|---:|---|
| Total test files | 577 | `find tests Modules -type f \( -name '*Test.php' -o -name '*PestTest.php' \)` |
| RefreshDatabase files | 231 | `grep -RIl "RefreshDatabase"` |
| assertSee / assertSeeText occurrences | 240 | line-level grep |
| assertViewHas occurrences | 125 | line-level grep |
| makePartial() occurrences | 33 | line-level grep |
| expectNotToPerformAssertions | 0 | line-level grep (resolved in prior sprint) |
| assertInstanceOf…Relations | 26 | line-level grep |
| Cross-module model creates in unit scopes | 39 | grep in `*/Tests/Unit` |
| QuoteService / BillingAnalysisService / VendorReconciliationService / LicenseDeploymentService / BillingTemplateService — direct test refs | **0** | `grep -v "use Modules"` |
| Browser test files | 60 | `tests/Browser/` folder |
| Http::fake / Http::preventStrayRequests files | 23 | |
| Flaky-test annotations (any form) | 35 | `@flaky`/`quarantine` grep |
| Total parallel run log files on record | 347 | `reports/test-results-*.log` |
| Runs with at least one FAILED | 113 | `grep -l "FAILED"` |
| Confirmed consecutive fully-green log files | **1** | checked from the latest end |

### Per-Layer Distribution

| Module | Unit | Feature | Integration | Notes |
|---|---:|---:|---|---|
| Action1 | 2 | 5 | 2 | |
| Alerts | 0 | 1 | 2 | no unit layer |
| AssetManagement | 0 | 3 | 4 | no unit layer |
| CaseManager | 14 | 4 | 23 | heavy integration layer |
| ClientPortal | 1 | 1 | 0 | no integration layer |
| ContractManager | 1 | 12 | 4 | 1 unit file — critically under-covered at unit layer |
| Crm | 0 | 4 | 11 | no unit layer |
| DeploymentManager | 0 | 1 | 0 | minimal |
| DevFeedback | 0 | 0 | 0 | **no tests at all** |
| EmailMigration | 1 | 4 | 1 | |
| GoogleAdmin | 2 | 4 | 0 | no integration layer |
| KnowledgeBase | 1 | 7 | 0 | no integration layer |
| PIB | 1 | 13 | 4 | 1 unit file for the most complex billing module |
| Payment | 2 | 4 | 3 | |
| SoftwareSubscriptions | 11 | 4 | 6 | allowlisted RefreshDatabase debt |
| WidgetRegistry | 0 | 0 | 0 | **no tests at all** |

Core (non-module):
- `tests/Unit`: 184 files  
- `tests/Integration`: 144 files (including 3 boundary contracts, 1 cross-module workflow)  
- `tests/Feature`: 178 files  
- `tests/Browser`: 60 files  

### Critical Gaps Identified By This Audit

**Gap 1 — Zero unit-level service coverage for highest-risk financial services.**  
`QuoteService`, `BillingTemplateService`, `InvoiceGenerator`, `ProrationService`,
`BillingAnalysisService`, `VendorReconciliationService`, `LicenseDeploymentService`, and
`HelcimService` have **zero direct test references** outside of `use` imports. These services
contain the most complex branching, financial arithmetic, and transactional logic in the system.
The 6.5→8.5 plan identified them as Phase 3 targets but did not write a single test spec.

**Gap 2 — Reliability proof is missing: only 1 confirmed consecutive green run.**  
347 log files exist; 113 contain FAILED. Only 1 file appears presently green. No repeat-run
protocol is enforced by CI. The 8.5 plan targeted 3 consecutive green runs as an exit criterion
but never specified the enforcement mechanism.

**Gap 3 — Pyramid inversion in ContractManager and PIB.**  
ContractManager has 1 unit test file and 12 feature files for `QuoteService`,
`RentToOwn`, and proration logic — all of which are complex computational services
ideally validatable without a database. PIB has 1 unit file for `InvoiceGenerator`
and `BillingAnalysisService`. Both modules are pyramid-inverted (wide bottom, no top).

**Gap 4 — Module isolation guardrail allowlist is growing, not shrinking.**  
`ModuleUnitIsolationGuardTest` carries 2 full allowlisted path prefixes
(`PIB/Tests/Unit/`, `SoftwareSubscriptions/Tests/Unit/`) and 11 specific file allowlist
entries for `RefreshDatabase` in unit scope. No "shrink-by-date" protocol exists.
The guard prevents new violations but does not compel resolution of existing ones.

**Gap 5 — Architecture enforcement is symbolic, not semantic.**  
`ModuleBoundaryContractsTest` uses string-pattern regex scans (grep-style import checks).
It does not use Pest's `arch()` API, does not verify that contracts are used at injection
points rather than concrete classes, and does not enforce event dispatch protocol
across module seams. The `EnhancedArchitectureTest` is a good start but remains isolated
to a handful of manually-listed interfaces and listeners.

**Gap 6 — No mutation or property-based testing anywhere in the suite.**  
The 10/10 roadmap calls for mutation testing on ContractManager and PIB but no tooling
(`infection/infection` or equivalent) is installed and no mutation score baseline exists.

**Gap 7 — 35 flaky-test annotations but no quarantine-exit protocol.**  
Tests annotated as `@flaky` or in quarantine groups are effectively permanently hidden.
No triage cadence, exit condition, or ownership assignment is enforced.

---

## Part 2 — Critique of the Prior Plans

### What the 6.5→8.5 Plan Gets Right

- Phase sequencing (stabilize before adding volume) is correct.
- Priority targets (QuoteService, BillingTemplateService, etc.) are correctly identified.
- The cut-list approach (delete noise before adding signal) is the right order.
- Pyramid rebalance goal (RefreshDatabase in unit scopes is wrong) is correct.

### What the 6.5→8.5 Plan Gets Wrong or Underestimates

1. **Score arithmetic is optimistic.** Phase 3 claims +0.8 for "fill critical coverage" but
   zero specs are written and zero existing tests exist for those services. The real lift
   for untested financial logic is +1.2–1.5 if done with full invariant + failure-path coverage.
2. **The plan ends at 8.5.** The scorecard targets (`RefreshDatabase <= 170`,
   `assertSee <= 150`) are necessary but not sufficient for 8.5+. No mutation gate, no
   consecutive-run enforcement mechanism, no property-based tests.
3. **Phase 4 governance is too vague.** "publish testing contribution rules" is not
   a criterion. "Team-facing standards are documented and discoverable" cannot be
   automatically verified. The plan needs a concrete artifact (a file at a known path)
   that CI can validate exists and is current.

### What the 10/10 Roadmap Gets Right

- Phases 3 (architecture enforcement hardening) and 4 (mutation + property-based) are
  correctly positioned as the hardest and last milestones.
- The "10 consecutive green parallel runs" target is the right proof-of-reliability bar.
- Agent A / Agent B split is a practical parallel execution model.

### What the 10/10 Roadmap Underestimates or Omits

1. It assumes the suite is at 8.0 now. Based on evidence (1 green run, 0 critical
   service tests, 113/347 failed runs), the realistic current score is **~6.5 to 6.7**.
   The roadmap phases need to be rebased accordingly.
2. It does not specify which mutation operators are highest-value for financial arithmetic.
3. It does not specify a contribution guideline format or location.
4. Phase 4 targets "10 consecutive green parallel runs" without specifying what "parallel"
   means (process count, runner, seed, environment) — making the exit criterion ambiguous.

---

## Part 3 — Unified Phase Plan: 6.5 to 10.0

### Score Mapping

| Phase | Score Range | Theme |
|---|---|---|
| 0 | 6.5 → 6.7 | Stabilize baseline, capture metrics, enable guardrails |
| 1 | 6.7 → 7.2 | Junk elimination and brittle assertion reduction |
| 2 | 7.2 → 7.8 | Pyramid rebalance, DB usage discipline, parallel lanes |
| 3 | 7.8 → 8.6 | Deep business logic coverage for all uncovered financial services |
| 4 | 8.6 → 8.9 | Reliability proof: 3 consecutive green parallel runs |
| 5 | 8.9 → 9.2 | Signal quality: hollow-test removal, noise reduction |
| 6 | 9.2 → 9.5 | Architecture enforcement hardening: semantic not regex |
| 7 | 9.5 → 9.7 | Cross-module event-chain integration verification |
| 8 | 9.7 → 9.9 | Mutation and property-based testing for critical financial domains |
| 9 | Any | Developer experience, contribution guidelines, maintenance cadence |
| 10 | 9.9 → 10.0 | 10/10 proof: 10 consecutive green parallel runs + all dimensions green |

Phase 9 (developer experience) runs in parallel with Phases 6–8; it is not a blocking
sequential phase but must be complete before Phase 10 proof.

---

## Phase 0: Stabilize Baseline and Guardrails (6.5 → 6.7)

### Objective

Create a trusted, reproducible baseline and prevent regression while refactors begin.
This phase has already been substantially completed based on scan evidence; the tasks
are confirmatory and gap-closing.

### Work Items

1. **Record the scorecard baseline** in this document (see Part 1 — now complete).
2. **Verify and run `ModuleUnitIsolationGuardTest`** passes without manual intervention.
   File: `tests/Unit/ModuleUnitIsolationGuardTest.php`
3. **Run one full uninterrupted parallel suite** and archive the result in `reports/`.
   Command: `php artisan test` (uses built-in logger).
4. **Confirm architecture guard tests pass** in their current state:
   - `tests/Architecture/EnhancedArchitectureTest.php`
   - `tests/Architecture/ModuleBoundariesTest.php`
   - `tests/Architecture/ModuleContracts/ModuleBoundaryContractsTest.php`
5. **Label existing flaky candidates.** Do not skip; add `@group flaky-triage` and file
   one GitHub/issue tracking item per annotation group.
6. **Confirm `tests/Integration/CrossModule/WorkflowContractTest.php` passes.**

### Exit Criteria

- [ ] This scorecard table is populated with current values (complete above).
- [ ] One complete parallel run artifact exists in `reports/`.
- [ ] All architecture guard tests pass with zero failures.
- [ ] Each of the 35 flaky-annotated tests has a tracking issue linked in code comment.

---

## Phase 1: Junk Test Elimination (6.7 → 7.2)

### Objective

Delete or refactor low-value tests that verify framework mechanics, Eloquent wiring,
or fragile UI copy rather than domain behavior. Increase signal-to-noise ratio before
adding volume.

### Work Items

#### 1A — Relationship and Filler Pattern Removal

Remove tests that only assert:
- `assertInstanceOf(BelongsTo::class, ...)` or any Eloquent `Relations` type.
  - 26 occurrences identified. Target: 0.
  - These tests verify Laravel's own ORM, not application behavior.
- Only `$model->fillable` array membership.
- Only `$model->casts` array membership.

**Files to scan:** any `*ModelTest.php` or `*Model*PestTest.php` under `Modules/*/Tests/Unit`.

Replace each deleted assertion with one behavioral assertion if any real behavior
(e.g., scope, mutator, accessor, event) exists on that model. Delete the file if
no behavioral content remains.

#### 1B — Brittle UI-Copy Assertion Reduction

Target: `assertSee` / `assertSeeText` from 240 → ≤ 150.

Rules:
- Keep `assertSee` only where the copy itself is a distinct product requirement
  (error messages driven by business rules, success/failure states, security notices).
- Replace broad `assertSee` chains in feature tests with `assertStatus(200)` +
  `assertSessionHas` or response model assertions.
- Modules with the most exposure: scan per-module counts before targeting.

Command to find top offenders:  
`grep -RIn "assertSee\b" Modules/*/Tests --include='*.php' | cut -d: -f1 | sort | uniq -c | sort -rn | head -20`

#### 1C — makePartial Hotspot Reduction

Target: `makePartial()` from 33 → ≤ 10.

The 4 guarded gateway hotspots in `ModuleUnitIsolationGuardTest::$guardedGatewayHotspotPatterns`
(Action1, GoogleAdmin) must not use `makePartial` on the service under test.
Replace with Http::fake() + real service invocation or a dedicated fake implementation.

#### 1D — Explicit No-op Test Elimination

Although `expectNotToPerformAssertions` is now at 0, audit for:
- `assertTrue(true)` passthrough assertions.
- Tests with a single `assertNotNull($object)` as their only assertion.
- Tests that only assert `$object instanceof SomeClass`.

Current count from scan: 0 passthrough `assertTrue(true)` — confirm and document.

### Exit Criteria

- [ ] `assertInstanceOf…Relations` count = 0.
- [ ] `assertSee` / `assertSeeText` count ≤ 150.
- [ ] `makePartial()` count ≤ 10.
- [ ] No net loss in business-rule coverage for any modified module (verified by
  running the module's full suite before and after each batch deletion).
- [ ] Full suite still green after batch.

---

## Phase 2: Pyramid Rebalance and Performance (7.2 → 7.8)

### Objective

Shift expensive integration-backed checks into fast deterministic unit tests.
Introduce formal CI lane split. Eliminate cross-module DB persistence from unit scopes.

### Work Items

#### 2A — RefreshDatabase Discipline in Unit Scopes

Currently there are 39 cross-module model creates in `*/Tests/Unit`. Target: 0.
The 11 `SoftwareSubscriptions/Tests/Unit` allowlisted files contain `RefreshDatabase`.

For each file:
1. Determine whether the test is unit (service logic) or integration (persistence behavior).
2. If unit: replace `RefreshDatabase` + `factory()->create()` with
   `Mockery::mock()` or hand-built value objects. Move any true integration behavior
   to `*/Tests/Integration/`.
3. If genuinely integration: move to `*/Tests/Integration/` and remove from allowlist.

Shrink `$allowlistedRefreshDatabaseBaseline` in `ModuleUnitIsolationGuardTest` by
at least 6 entries before this phase closes (target: from 11 to ≤ 5).

Target: `RefreshDatabase` file count from 231 → ≤ 185.

#### 2B — CI Lane Formalisation

Extend `phpunit.xml` with parallel-execution-friendly testsuite grouping:

| Lane | Suite | Trigger | Max duration target |
|---|---|---|---|
| A (PR gate) | `Unit` + `Integration` (non-browser) | every PR | ≤ 90 seconds |
| B (module depth) | `Modules` (non-browser) | every PR + nightly | ≤ 5 minutes |
| C (browser smoke) | `browser` | nightly + release | unlimited |

Implement via `paratest` process groups or CI matrix steps. Document the lane
configuration in `docs/development/CI_LANES.md`.

#### 2C — makePartial Replacement (continuation from Phase 1C)

For the specific guarded hotspots:

- `Modules/Action1/Tests/Feature/SyncAction1DevicesJobPestTest.php`:  
  Replace `Mockery::mock(Action1SyncService::class)` with
  `Http::fake([...])` + real `Action1SyncService` injected.
- `Modules/GoogleAdmin/Tests/Feature/Sync*` and `UserProvisioningActionPestTest.php`:  
  Same pattern — `Http::fake()` + real `GoogleWorkspaceService`.

This is the highest-value makePartial change because these tests currently mock the
very service they claim to test, providing zero execution coverage of gateway retry
logic, error mapping, and event dispatch.

#### 2D — Module-Level Integration Test Bootstrapping

Modules currently lacking any integration tests (7 modules):
`Alerts`, `AssetManagement`, `ClientPortal`, `DeploymentManager`,
`GoogleAdmin`, `KnowledgeBase`, and the completely untested `DevFeedback` and
`WidgetRegistry`.

Minimum viable integration test per module:
- One happy-path test for the module's primary public-facing operation.
- One failure/edge-path test for the most common external dependency.

This phase requires at least `Alerts`, `GoogleAdmin`, and `KnowledgeBase` integration
seams to be established. `DevFeedback` and `WidgetRegistry` may be accepted as
intentionally thin if they contain no business logic.

### Exit Criteria

- [ ] `RefreshDatabase` file count ≤ 185.
- [ ] `$allowlistedRefreshDatabaseBaseline` reduced to ≤ 5 entries.
- [ ] 39 cross-module model creates in unit scopes reduced to ≤ 10.
- [ ] CI lane A documented and running as a separable test command.
- [ ] `makePartial()` guarded hotspots (Action1, GoogleAdmin) replaced with Http::fake.
- [ ] `GoogleAdmin/Tests/Integration/` folder contains at least 1 integration test file.
- [ ] Full suite still green after all 2x changes.

---

## Phase 3: Deep Business Logic Coverage (7.8 → 8.6)

### Objective

Add high-value, invariant-oriented tests for the five financial/workflow services
currently at zero coverage. These are the highest business risk areas in the codebase.

### Priority Targets (all at zero test refs as of baseline)

#### 3A — `QuoteService` (Modules/ContractManager/Services/QuoteService.php)

Methods that require coverage:

| Method | Test type | Critical invariants |
|---|---|---|
| `createQuote` | unit | client must not be archived; at least one line item required |
| `addLineItem` | unit | quantity > 0; unit_price ≥ 0; subtotal calculation |
| `reviseQuote` | unit | cannot revise an approved/rejected quote; revision creates new version |
| `sendToClient` | integration | status transitions draft→sent; email dispatched |
| `approveQuote` | integration | status=approved; `CreateContractFromApprovedQuote` listener fires |
| `rejectQuote` | unit | status=rejected; reason stored |
| `duplicateQuote` | unit | line items cloned; status reset to draft |

Minimum test file: `Modules/ContractManager/Tests/Unit/QuoteServicePestTest.php`

#### 3B — `BillingTemplateService` (Modules/PIB/Services/BillingTemplateService.php)

Methods that require coverage:

| Method | Test type | Critical invariants |
|---|---|---|
| `pauseTemplate` | unit | cannot pause an already-paused template; reason stored |
| `resumeTemplate` | unit | cannot resume an active template; `nextInvoiceDate` defaults correctly |
| `triggerBilling` | integration | creates invoice; updates `last_triggered_at` |
| `calculateBillableAmount` | unit | boundary: zero entitlements → zero amount; negative entitlements → throw |
| `getTemplatesDueOn` | unit | returns only templates where `next_invoice_date` == given date |

Minimum test file: `Modules/PIB/Tests/Unit/BillingTemplateServicePestTest.php`

#### 3C — `InvoiceGenerator` (Modules/PIB/Services/InvoiceGenerator.php)

Methods that require coverage:

| Method | Test type | Critical invariants |
|---|---|---|
| `generateFromTemplate` | integration | line items match template config; total correct |
| `publish` | integration | status transitions to published; event dispatched |
| `generateDueInvoices` | integration | only due templates processed; already-generated skipped |

Minimum test file: `Modules/PIB/Tests/Integration/InvoiceGeneratorPestTest.php`

#### 3D — `ProrationService` (Modules/PIB/Services/ProrationService.php)

Methods that require coverage (unit-only — pure arithmetic):

| Method | Critical invariants |
|---|---|
| `calculateProration` | start=end → 0; start > end → throw or 0; month boundary spanning |
| `calculateDailyRate` | 28/29/30/31 day months each produce correct rate |
| `calculateRemainderOfMonth` | exact last-day-of-month start → full proration |
| `calculateForDays` | zero days → 0; negative days → throw |

Test file already partially exists; extend at:
`Modules/ContractManager/Tests/Integration/ProrationContractRevisionTest.php` and
add pure-unit file `Modules/PIB/Tests/Unit/ProrationServiceEdgeCasesPestTest.php`.

#### 3E — `VendorReconciliationService` (Modules/SoftwareSubscriptions/Services/VendorReconciliationService.php)

Methods that require coverage:

| Method | Test type | Critical invariants |
|---|---|---|
| `reconcileSubscription` | unit | mismatch detected correctly; no side-effects in dry-run |
| `autoFixDiscrepancies(dryRun=true)` | unit | no DB writes; returns preview list |
| `autoFixDiscrepancies(dryRun=false)` | integration | writes corrections; events dispatched |
| `compareWithVendorReport` | unit | surplus/deficit logic; correct delta computation |

Minimum test files:
- `Modules/SoftwareSubscriptions/Tests/Unit/VendorReconciliationServicePestTest.php`
- `Modules/SoftwareSubscriptions/Tests/Integration/ReconciliationDryRunPestTest.php`

#### 3F — `LicenseDeploymentService` (Modules/SoftwareSubscriptions/Services/LicenseDeploymentService.php)

Methods that require coverage:

| Method | Test type | Critical invariants |
|---|---|---|
| `initiateDeployment` | integration | creates assignment record; dispatches event |
| `retryDeployment` | integration | respects max attempt limit; idempotent on duplicate call |
| `markCompleted` | unit | sets status; records details |
| `markFailed` | unit | increments attempt counter |

Minimum test file: `Modules/SoftwareSubscriptions/Tests/Integration/LicenseDeploymentServicePestTest.php`

#### 3G — `BillingAnalysisService` (Modules/PIB/Services/BillingAnalysisService.php)

Methods that require coverage:

| Method | Test type | Critical invariants |
|---|---|---|
| `getBillingVarianceReport` | unit | 0% change; >threshold flags unusual; negative change correct |

Minimum test file: `Modules/PIB/Tests/Unit/BillingAnalysisServicePestTest.php`

#### 3H — `HelcimService` — Payment Settlement (Modules/Payment/Services/HelcimService.php)

Methods that require coverage:

| Method | Test type | Critical invariants |
|---|---|---|
| `chargeVaultedCard` | boundary (Http::fake) | success path records Payment; failure path throws |
| `refundPayment` | boundary (Http::fake) | partial refund ≤ original; duplicate refund rejected |
| `verifyWebhookSignature` | unit (crypto) | correct HMAC passes; tampered payload fails |

Minimum test file: `Modules/Payment/Tests/Integration/HelcimServiceContractPestTest.php`

### Test Design Standards for All Phase 3 Tests

- Tests MUST be oriented toward **invariants and outcomes**, not implementation steps.
- Failure paths (invalid input, constraint violations, external errors) MUST each have
  their own assertion.
- No new `RefreshDatabase` in any `*/Tests/Unit` file — use factories with `make()`
  (not `create()`) or value objects.
- All external HTTP calls MUST use `Http::fake()`.

### Exit Criteria

- [ ] Each of 3A–3H has at minimum the specified test files.
- [ ] Each of `createQuote`, `calculateProration`, `reconcileSubscription`,
  `calculateBillableAmount`, `verifyWebhookSignature` has at least one failure-path test.
- [ ] No new architecture violations introduced by new tests.
- [ ] Module-level suites for ContractManager, PIB, Payment, SoftwareSubscriptions all green.

---

## Phase 4: Reliability Proof (8.6 → 8.9)

### Objective

Produce 3 consecutive fully-green parallel runs with no deterministic failures and
establish the repeatability gate as an enforced CI rule.

### Work Items

1. **Execute 3 uninterrupted parallel runs** using the standard command:
   ```
   php artisan test
   ```
   Record each log file. All 3 must appear in `reports/` with zero FAILED lines.

2. **Triage any deterministic failures** found during the run sequence:
   - If caused by test ordering: fix the test factory or state setup.
   - If caused by time/date sensitivity: freeze time with `Carbon::setTestNow()`.
   - If caused by external service: assert `Http::fake()` cover is complete.
   - **Do not skip or quarantine any deterministic failure.** Fix it.

3. **Resolve or graduate quarantined flaky tests.** For each of the 35 annotated tests:
   - Determine root cause (timing, ordering, external HTTP, shared state).
   - Fix if fixable. Delete if the test has no behavioral value.
   - Document outcome in tracking issue.
   - Target: reduce flaky annotations from 35 to ≤ 10 with documented justification.

4. **Implement the repeatability gate.** Add a CI step that:
   - Runs the full suite twice in sequence.
   - Compares pass/fail totals.
   - Fails the pipeline if the second run has a different failure set from the first.
   - Document in `.github/workflows/` or equivalent CI config.

5. **Fix or remove the 113 past-failing runs** backlog by confirming that the latest
   run and 2 subsequent runs are clean. Historical failures can be archived as
   pre-Phase-4 baseline.

### Exit Criteria

- [ ] 3 consecutive `reports/test-results-*.log` files with zero FAILED lines.
- [ ] CI repeatability gate documented and active.
- [ ] Flaky-test annotation count ≤ 10 with all remaining having a tracking issue and
  documented reason.
- [ ] `ModuleUnitIsolationGuardTest` passes without any allowlist expansion.

---

## Phase 5: Signal Quality (8.9 → 9.2)

### Objective

Remove remaining framework-noise assertions and ensure every test failure maps to a
real business behavior regression.

### Work Items

1. **Eliminate remaining `assertViewHas` view-wiring checks.**
   Target: `assertViewHas` from 125 → ≤ 40.
   Keep only those where the view variable drives business logic visible to the user.
   Replace others with `assertStatus(200)` + a model-level assertion or event assertion.

2. **Reduce `assertSee` / `assertSeeText` further.**
   Target: ≤ 100 (from 150 after Phase 1).
   Run a second targeted pass on `tests/Feature` and `Modules/*/Tests/Feature`.

3. **Audit for "plumbing tests" — tests that only verify request routing:**
   - Tests that only call `$this->get('/route')` and assert `->assertStatus(200)`.
   - Replace with integration tests asserting the business outcome of the route.

4. **Remove duplicate test scenarios.** Audit each module for near-identical tests
   differing only in parameter values. Consolidate with `it()->with()` datasets.

5. **Verify that test failures are actionable.** Run the full suite and introduce
   a deliberate seeded defect in `ProrationService::calculateDailyRate` and in
   `QuoteService::approveQuote`. Assert that at least one test in each module fails
   with an informative assertion message (not just "expected false to be true").

### Exit Criteria

- [ ] `assertViewHas` count ≤ 40.
- [ ] `assertSee` / `assertSeeText` count ≤ 100.
- [ ] No pure-routing-only test files remain in `tests/Feature`.
- [ ] Seeded defect in `ProrationService` causes at least 1 named failure.
- [ ] Seeded defect in `QuoteService::approveQuote` causes at least 1 named failure.

---

## Phase 6: Architecture Enforcement Hardening (9.2 → 9.5)

### Objective

Move architecture checks from string-pattern grep-style scanning to semantic
PHPStan and Pest `arch()` rules with low false-positive rates and actionable failure
messages.

### Work Items

#### 6A — Migrate ModuleBoundaryContractsTest to arch() Semantics

`tests/Architecture/ModuleContracts/ModuleBoundaryContractsTest.php` currently uses
custom string-pattern scan helpers. Replace the string scans with Pest `arch()` calls
where the Pest architecture plugin supports them, and isolate the remaining regex scans
to a clearly-named "legacy fallback" block pending plugin support.

#### 6B — Five New Architecture Rules

See Part 5 of this document for the complete rule definitions.

#### 6C — Allowlist Expiry Enforcement

Modify `ModuleUnitIsolationGuardTest` to embed a `@expires` date comment on every
allowlist entry. Add a test assertion that fails if any allowlist entry's expiry date
has passed and the entry still exists:

```php
// 'Modules/SoftwareSubscriptions/Tests/Unit/...' => expires: 2026-06-01
```

If the entry has not been removed by its expiry date, the guard test fails,
forcing resolution or explicit extension with updated justification.

#### 6D — Contract Interface Coverage Assertion

Add a test to `ModuleBoundaryContractsTest` that asserts:
for every interface in `app/Contracts/` that is implemented by a module service,
the module's service provider MUST bind that interface in the service container (not the
concrete class directly). This prevents modules from bypassing DI and coupling directly
to concrete implementations.

#### 6E — PHPStan Level Raise + Financial Domain Rule

Currently, `phpstan.neon` likely runs at a level that permits implicit type coercions.
For the financial services layer:
- Raise PHPStan from its current level to at least level 6 for
  `Modules/PIB/Services/`, `Modules/Payment/Services/`, and `Modules/ContractManager/Services/`.
- Add a baseline for existing violations so only new violations fail CI.

### Exit Criteria

- [ ] All 5 new architecture rules from Part 5 are active and passing.
- [ ] `ModuleBoundaryContractsTest` uses `arch()` API for at least 50% of its assertions.
- [ ] Allowlist entries in `ModuleUnitIsolationGuardTest` all carry expiry dates.
- [ ] Contract interface coverage assertion passes for ContractManager and PIB.
- [ ] PHPStan level elevated for financial service directories.

---

## Phase 7: Cross-Module Event Chain Verification (9.5 → 9.7)

### Objective

Prove that critical multi-module workflows produce the correct end-to-end business
outcomes through integration-level event-chain tests.

### Work Items

The file `tests/Integration/CrossModule/WorkflowContractTest.php` already covers:
- Chain 1: Action1 → AssetManagement → SoftwareSubscriptions
- Chain 2: GoogleAdmin → Crm (identity resolution seam)

Extend to cover:

#### 7A — ContractManager → PIB → Payment Chain

Test: approving a `Quote` creates a `Contract`, the associated `BillingTemplate`
generates an `Invoice`, and the `Invoice` is correctly payable via `HelcimService`.

Minimum assertions:
- `Quote` status = approved after `QuoteService::approveQuote`.
- `Contract` created with correct line items.
- `BillingTemplate` associated with contract exists and is active.
- When `InvoiceGenerator::generateDueInvoices()` runs, invoice created with correct total.
- `CreditManagementService::getCurrentBalanceCents()` reflects any applied credit.

File: `tests/Integration/CrossModule/ContractToBillingChainPestTest.php`

#### 7B — GoogleAdmin → Crm → CaseManager Chain

Test: provisioning a Google user creates a Crm contact, and that contact's case history
is accessible from CaseManager.

File: `tests/Integration/CrossModule/GoogleAdminToClientCaseChainPestTest.php`

#### 7C — SoftwareSubscriptions Reconciliation → PIB Adjustment

Test: a detected license discrepancy in `VendorReconciliationService` triggers a
billing adjustment that is reflected in the PIB billing variance report.

File: `tests/Integration/CrossModule/ReconciliationToBillingAdjustmentPestTest.php`

### Exit Criteria

- [ ] All 3 new chain test files exist and pass.
- [ ] Existing `WorkflowContractTest.php` chains still pass (no regression).
- [ ] Each chain test exercises at least 2 module boundaries.
- [ ] Each chain test includes one failure-path assertion (e.g., Chain interrupted at seam).

---

## Phase 8: Mutation and Property-Based Testing (9.7 → 9.9)

### Objective

Verify that the test suite can detect subtle logic defects, not only common regressions.

See Part 6 of this document for the complete mutation testing specification.

### Work Items

1. **Install and configure `infection/infection`.**
   Add to `composer.json` dev dependencies. Configure `infection.json` with the
   financial service source paths as the target directory.

2. **Establish mutation score baselines** for the 3 domains specified in Part 6.
   Run infection dry-run first to estimate score before investing in new tests.

3. **Add property-based tests using `eris` or a dataset-driven Pest equivalent**
   for arithmetic invariants:
   - `ProrationService`: for any valid date range, `calculateProration >= 0` and
     `calculateProration <= monthlyRate`.
   - `BillingTemplateService::calculateBillableAmount`: for any non-negative entitlement
     count, result is non-negative.
   - `CreditManagementService`: `getCurrentBalanceCents` after `issueCredit(X) + deductCredit(X)`
     = original balance (idempotency invariant).

4. **Integrate mutation scoring into CI** as a non-blocking informational step.
   Only block on score regression: if score drops > 5 points from recorded baseline,
   fail the pipeline.

### Exit Criteria

- [ ] `infection/infection` installed and `infection.json` configured.
- [ ] Mutation scores established and documented for ContractManager, PIB, Payment domains.
- [ ] Scores meet thresholds specified in Part 6 (70% minimum, 80% target).
- [ ] At least 3 property-based tests active (one per domain above).
- [ ] CI mutation step runs without blocking normal test execution.

---

## Phase 9: Developer Experience and Contribution Guidelines (parallel with 6–8)

### Objective

Ensure that any developer contributing to the test suite can make correct decisions
without tribal knowledge, and that the suite can be maintained with low ceremony.

See Part 7 of this document for the complete contribution guide draft.

### Work Items

1. **Publish test contribution guide** at `docs/development/TESTING_CONTRIBUTION_GUIDE.md`.
   Content: the full guide from Part 7 below.

2. **Add CI check that the guide file exists.**  
   Add to `ModuleUnitIsolationGuardTest` (or a new `docs/` guard test) an assertion
   that `docs/development/TESTING_CONTRIBUTION_GUIDE.md` exists and was last modified
   within the current calendar year. This makes "documentation is current" mechanically
   verifiable.

3. **Establish quarterly maintenance cadence** documented in `docs/development/TEST_MAINTENANCE_CADENCE.md`:
   - Q1: Stale test triage (tests not touched in 6+ months that cover no changed code).
   - Q2: Duplicate scenario scan and consolidation.
   - Q3: Scorecard refresh and allowlist expiry review.
   - Q4: Mutation score re-run and regression gate recalibration.

4. **Add module test scaffold generator.** Create an Artisan command or script at
   `scripts/scaffold-module-tests.php` that generates a correct `Tests/Unit/`,
   `Tests/Integration/`, `Tests/Feature/` skeleton for a new module, following the
   patterns established in Phase 3.

### Exit Criteria

- [ ] `docs/development/TESTING_CONTRIBUTION_GUIDE.md` exists and matches Part 7 content.
- [ ] CI asserts that guide file exists and is current-year.
- [ ] `docs/development/TEST_MAINTENANCE_CADENCE.md` exists with quarterly schedule.
- [ ] Module test scaffold script exists and generates a runnable skeleton.

---

## Phase 10: 10/10 Proof (9.9 → 10.0)

### Objective

Execute the full 10/10 acceptance criteria and document the result.

### 10/10 Definition: All of the Following Must Be True

1. **Reliability:** 10 consecutive parallel full-suite runs complete with zero
   deterministic failures and zero flakes. Parallelism means: `paratest --processes=4`,
   default seed, CI environment.
2. **Signal quality:** `expectNotToPerformAssertions` = 0, `assertSee` ≤ 100,
   `assertViewHas` ≤ 40, seeded defects cause named failures.
3. **Pyramid quality:** unit:integration:feature:browser ≈ 35:30:27:8 ratio.
   No `RefreshDatabase` in any `*/Tests/Unit/` file outside documented allowlist of ≤ 3.
4. **Boundary confidence:** all 3 boundary contract tests pass; all external HTTP calls
   in test scenarios are faked.
5. **Architecture enforcement:** all 5 Phase 6 rules active and passing; 0 allowlist
   entries expired without resolution.
6. **Coverage depth:** all Phase 3 service targets have ≥ 1 failure-path test;
   mutation scores meet Phase 8 thresholds.
7. **Maintenance:** `TESTING_CONTRIBUTION_GUIDE.md` is current; maintenance cadence
   document exists.

### Work Items

1. Run `php artisan test` 10 times sequentially. Record all 10 log file paths.
2. Verify every dimension target above is met using the scorecard in Part 4.
3. Commit the 10 log files to `reports/10x-green-proof/` as permanent evidence.
4. Update this document's status header to `COMPLETE — 10/10 ACHIEVED`.

### Exit Criteria

- [ ] 10 consecutive log files in `reports/10x-green-proof/` with zero FAILED lines.
- [ ] All dimension targets in Part 4 scorecard are green.
- [ ] Final scorecard committed to `docs/development/WIP/FINAL_SCORECARD.md`.

---

## Part 4 — Amended Scorecard Template

Track this table weekly. A dimension is "green" when it meets the 10/10 target.

### Dimension Scores (1–10)

| Dimension | Current | Phase 4 Target | Phase 7 Target | 10/10 Target |
|---|:---:|:---:|:---:|:---:|
| 1. Reliability | 3 | 7 | 9 | 10 |
| 2. Signal quality | 5 | 7 | 8 | 10 |
| 3. Pyramid balance | 5 | 7 | 8 | 10 |
| 4. Boundary confidence | 6 | 7 | 9 | 10 |
| 5. Mocking integrity | 5 | 7 | 8 | 10 |
| 6. Module isolation | 6 | 7 | 9 | 10 |
| 7. Coverage depth | 2 | 6 | 8 | 10 |
| 8. Architecture enforcement | 5 | 7 | 9 | 10 |
| 9. Maintainability | 5 | 6 | 8 | 10 |
| 10. Developer experience | 3 | 5 | 7 | 10 |
| **Composite** | **6.5** | **7.5** | **8.8** | **10** |

**Scoring rationale:**
- Reliability = 3: only 1 confirmed green run out of 347 logs.
- Coverage depth = 2: zero tests for 5 highest-risk financial services.
- Developer experience = 3: no contribution guide, no scaffold tooling.

### Metric Scorecard (Quantitative)

| Metric | Baseline (2026-03-18) | Phase 2 Target | Phase 5 Target | 10/10 Target |
|---|---:|---:|---:|---:|
| Total test files | 577 | ≤ 550 | ≤ 530 | ≤ 520 |
| RefreshDatabase files | 231 | ≤ 185 | ≤ 160 | ≤ 140 |
| assertSee / assertSeeText | 240 | ≤ 200 | ≤ 100 | ≤ 100 |
| assertViewHas | 125 | ≤ 100 | ≤ 40 | ≤ 40 |
| makePartial | 33 | ≤ 15 | ≤ 10 | ≤ 5 |
| assertInstanceOf…Relations | 26 | 0 | 0 | 0 |
| Cross-module creates in unit | 39 | ≤ 10 | ≤ 5 | 0 |
| Flaky annotations | 35 | ≤ 20 | ≤ 10 | 0 |
| Allowlist entries (RefreshDB) | 11 | ≤ 5 | ≤ 3 | ≤ 3 |
| Boundary contract tests | 3 | 3 | 6 | 6 |
| Critical service test files (Phase 3) | 0 | 4 | 8 | 8 |
| Mutation score (ContractManager) | 0% | — | — | ≥ 70% |
| Mutation score (PIB) | 0% | — | — | ≥ 70% |
| Mutation score (Payment) | 0% | — | — | ≥ 70% |
| Consecutive green parallel runs | 1 | 3 | 3 | 10 |

---

## Part 5 — New Architecture Enforcement Rules (Phase 6 Detail)

All five rules must be added before Phase 6 closes.

### Rule 1: Services Must Not Directly Instantiate Foreign Module Repositories

**File:** `tests/Architecture/ModuleContracts/ModuleBoundaryContractsTest.php`

**Pattern:** Any `new Modules\SomeOtherModule\Repositories\...` inside a service file
in a different module indicates direct cross-module repository coupling — bypassing
the contract seam.

```php
test('module services do not directly instantiate foreign module repositories', function () {
    $violations = [];
    $moduleServiceDirs = glob(base_path('Modules/*/Services'), GLOB_ONLYDIR);

    foreach ($moduleServiceDirs as $serviceDir) {
        preg_match('#Modules/([^/]+)/Services#', $serviceDir, $m);
        $ownerModule = $m[1];

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($serviceDir)) as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') continue;
            $contents = file_get_contents($file->getPathname());
            if (preg_match_all('/new Modules\\\\([A-Za-z]+)\\\\Repositories\\\\/', $contents, $hits)) {
                foreach ($hits[1] as $foreignModule) {
                    if ($foreignModule !== $ownerModule) {
                        $violations[] = $file->getPathname() . " instantiates Modules\\{$foreignModule}\\Repositories directly";
                    }
                }
            }
        }
    }

    expect($violations)->toBe([]);
});
```

### Rule 2: Event Listeners Must Not Perform HTTP Calls Without Http::fake Coverage

**File:** `tests/Architecture/EnhancedArchitectureTest.php`

**Pattern:** Any listener file that uses `Http::` or `\Illuminate\Support\Facades\Http`
must have a companion test that calls `Http::fake()`.

```php
test('listeners that make Http calls have Http::fake coverage in their tests', function () {
    $listenerDirs = glob(base_path('Modules/*/Listeners'));
    $violations = [];

    foreach ($listenerDirs as $listenerDir) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($listenerDir)) as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') continue;
            $contents = file_get_contents($file->getPathname());
            if (!str_contains($contents, 'Http::') && !str_contains($contents, 'Facades\Http')) continue;

            $listenerName = basename($file->getPathname(), '.php');
            $testFiles = glob(base_path("Modules/*/Tests/**/*{$listenerName}*Test*.php"));
            $hasFake = false;
            foreach ($testFiles as $testFile) {
                if (str_contains(file_get_contents($testFile), 'Http::fake')) {
                    $hasFake = true;
                    break;
                }
            }
            if (!$hasFake) {
                $violations[] = $file->getPathname() . ' uses Http but has no Http::fake test coverage';
            }
        }
    }

    expect($violations)->toBe([]);
});
```

### Rule 3: Financial Service Methods Must Not Return Untyped Floats in Calculation Methods

**File:** `tests/Architecture/EnhancedArchitectureTest.php`

**Pattern:** Methods in financial service classes (`ProrationService`, `InvoiceGenerator`,
`BillingTemplateService`, `CreditManagementService`, `HelcimService`) that contain the
words "calculate", "amount", "rate", "total", or "balance" in their name MUST declare
an explicit return type (`float`, `int`, or a Money value object). No return type or
`mixed` return type is a violation.

```php
test('financial service calculation methods declare explicit numeric return types', function () {
    $financialServices = [
        base_path('Modules/PIB/Services/ProrationService.php'),
        base_path('Modules/PIB/Services/InvoiceGenerator.php'),
        base_path('Modules/PIB/Services/BillingTemplateService.php'),
        base_path('Modules/Payment/Services/CreditManagementService.php'),
        base_path('Modules/Payment/Services/HelcimService.php'),
    ];

    $violations = [];
    $calcPattern = '/public function (calculate|get.*[Aa]mount|get.*[Rr]ate|get.*[Tt]otal|get.*[Bb]alance)[A-Za-z]*/';

    foreach ($financialServices as $path) {
        if (!file_exists($path)) continue;
        $reflection = new ReflectionClass(getClassFromFile($path));
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if (!preg_match($calcPattern, $method->getName())) continue;
            $returnType = $method->getReturnType();
            if ($returnType === null || $returnType->getName() === 'mixed') {
                $violations[] = get_class($reflection) . '::' . $method->getName() . ' has no explicit numeric return type';
            }
        }
    }

    expect($violations)->toBe([]);
});
```

### Rule 4: PHPStan Pest arch() — No Direct DB Facade in Service Layer

**File:** `tests/Architecture/LayerTest.php`

**Rationale:** Services that directly call `DB::statement()`, `DB::table()`, or
`DB::raw()` bypass the repository/Eloquent layer and make testing harder. Allowed only
in repository classes and migrations.

```php
arch('service classes do not call DB facade directly')
    ->expect('Modules\*\Services\*')
    ->not->toUse('Illuminate\Support\Facades\DB');
```

### Rule 5: Unit Tests Must Not Use App::make or resolve() for Cross-Module Services

**File:** `tests/Unit/ModuleUnitIsolationGuardTest.php` (new check within existing test)

**Rationale:** Using `app()->make(OtherModuleService::class)` in a unit test boots
the service container, creates implicit dependencies, and is functionally equivalent
to the cross-module DB coupling the guard already blocks.

Add to `test_module_unit_tests_do_not_use_refresh_database_or_cross_module_persistence`:

```php
// Check for app()->make() / resolve() with foreign module class arguments
$appMakePattern = '/(?:app\(\)|app\s*\(\))\s*->\s*make\s*\(\s*(?:\\\\)?Modules\\\\([A-Za-z]+)\\\\|resolve\s*\(\s*(?:\\\\)?Modules\\\\([A-Za-z]+)\\\\/';

if (str_contains($normalizedPath, '/Tests/Unit/')) {
    preg_match('#Modules/([^/]+)/#', $normalizedPath, $ownerMatch);
    $ownerModule = $ownerMatch[1] ?? null;
    if ($ownerModule && preg_match_all($appMakePattern, $contents, $makeHits)) {
        foreach (array_merge($makeHits[1], $makeHits[2]) as $resolvedModule) {
            if ($resolvedModule && $resolvedModule !== $ownerModule) {
                $crossModulePersistenceViolations[] = $normalizedPath . " resolves {$resolvedModule} via app/resolve in unit scope";
            }
        }
    }
}
```

---

## Part 6 — Mutation Testing Specification (Phase 8 Detail)

### Domain 1: ContractManager — Proration and Quote

**Class targets:**

| Class | Method | Why high-value |
|---|---|---|
| `Modules\PIB\Services\ProrationService` | `calculateProration` | Off-by-one boundary (start/end same day) |
| `Modules\PIB\Services\ProrationService` | `calculateDailyRate` | Month-length sensitivity (28/29/30/31) |
| `Modules\PIB\Services\ProrationService` | `calculateRemainderOfMonth` | Edge: first vs last day of month |
| `Modules\ContractManager\Services\QuoteService` | `approveQuote` | State machine: prevents double-approval |
| `Modules\ContractManager\Services\QuoteService` | `reviseQuote` | Guards against revision of terminal states |

**Minimum mutation score threshold:** 70% (phase entry), 80% (phase exit).

**Highest-value mutation operators for financial arithmetic:**

| Operator | Why |
|---|---|
| `ArithmeticOperatorReplacement` (AOR) | Replaces `+`, `-`, `*`, `/` — critical for all rate/total calculations |
| `RelationalOperatorReplacement` (ROR) | Replaces `>`, `>=`, `<`, `<=` — critical for boundary guards (e.g. partial month) |
| `LogicalOperatorReplacement` (LOR) | Replaces `&&`, `\|\|` — critical for multi-condition state guards in QuoteService |
| `MethodCallRemoval` (MCR) | Removes a method call — ensures side effects (event dispatch, status save) are tested |
| `NullifyReturnValue` | Forces method to return null — exposes callers that don't check return |

**Infection configuration target paths:**
```json
{
    "source": {
        "directories": [
            "Modules/PIB/Services",
            "Modules/ContractManager/Services"
        ]
    },
    "mutators": {
        "@arithmetic": true,
        "@relational": true,
        "@logical": true,
        "MethodCallRemoval": true,
        "NullifyReturnValue": true
    },
    "minMsi": 70,
    "minCoveredMsi": 80
}
```

### Domain 2: PIB — Invoice Generation

**Class targets:**

| Class | Method | Why high-value |
|---|---|---|
| `Modules\PIB\Services\InvoiceGenerator` | `generateFromTemplate` | Template→invoice line item mapping correctness |
| `Modules\PIB\Services\InvoiceGenerator` | `publish` | Status must transition; event must fire |
| `Modules\PIB\Services\BillingTemplateService` | `calculateBillableAmount` | Entitlement count × rate must not be mutable to 0 |
| `Modules\PIB\Services\BillingAnalysisService` | `getBillingVarianceReport` | Variance direction and magnitude must be accurate |

**Minimum mutation score threshold:** 70% (phase entry), 80% (phase exit).

**Additional high-value operators for PIB:**

| Operator | Why |
|---|---|
| `BooleanSubstitution` | Replaces `true`/`false` — critical for `publish` status guards |
| `OneZeroInteger` | Replaces `0` with `1` and vice versa — catches off-by-one in entitlement logic |
| `TrueValue` / `FalseValue` | Negates boolean decisions in variance thresholds |

### Domain 3: Payment — Settlement and Credit

**Class targets:**

| Class | Method | Why high-value |
|---|---|---|
| `Modules\Payment\Services\HelcimService` | `chargeVaultedCard` | Charge amount must never mutate to 0 or negative |
| `Modules\Payment\Services\HelcimService` | `refundPayment` | Partial refund must never exceed original charge |
| `Modules\Payment\Services\HelcimService` | `verifyWebhookSignature` | Security: must not be mutable to always-true |
| `Modules\Payment\Services\CreditManagementService` | `issueCredit` | Ledger entry must not be removable without a record |
| `Modules\Payment\Services\CreditManagementService` | `deductCredit` | Must not allow deduction below zero |

**Minimum mutation score threshold:** 75% (phase entry), 85% (phase exit).

> Payment settlement uses a higher threshold because financial errors are
> unrecoverable for customers.

**Additional high-value operators for Payment:**

| Operator | Why |
|---|---|
| `ArithmeticOperatorReplacement` | Core charge/refund amounts |
| `RelationalOperatorReplacement` | Zero-balance guard in `deductCredit` |
| `NullifyReturnValue` | Callers must handle null payment records |
| `UnwrapFunctionCall` | Removes `round()`, `abs()`, `max()` wrappers — exposes unguarded arithmetic |

---

## Part 7 — Test Contribution Guidelines (Phase 9 Detail)

> Save this section verbatim as `docs/development/TESTING_CONTRIBUTION_GUIDE.md`.

---

# Testing Contribution Guide

*Last updated: 2026-03-18 — tracked by CI.*

## Decision Tree: Which Test Class to Use

```
Is the behavior you are testing…

── purely about logic/computation with no DB or HTTP?
   └── UnitTestCase (tests/UnitTestCase.php)
       Modules/*/Tests/Unit/
       NO factories create(); NO RefreshDatabase; use make() or value objects.

── about persistence, model events, or Eloquent relationships?
   └── IntegrationTestCase (tests/IntegrationTestCase.php)
       Modules/*/Tests/Integration/
       RefreshDatabase is acceptable. Scope to one module.
       Do NOT call other modules' real services directly; use fakes or contracts.

── about HTTP routes, middleware, or the full request/response cycle?
   └── TestCase (tests/TestCase.php) with RefreshDatabase
       tests/Feature/ or Modules/*/Tests/Feature/
       Assert response codes + business outcomes, not view copy.
       Keep assertSee() only for copy that is a product requirement.

── about user-visible, multi-step browser workflows (a real risk)?
   └── DuskTestCase in tests/Browser/
       Use sparingly. One file per critical UX flow.
       Must justify why this cannot be covered as a feature test.
```

## Required Patterns (Templates)

### Unit Test (Service with Pure Logic)

```php
<?php

declare(strict_types=1);

namespace Modules\YourModule\Tests\Unit;

use Modules\YourModule\Services\YourService;
use Tests\UnitTestCase;

it('describes the specific invariant being tested', function () {
    $service = new YourService();

    $result = $service->calculate(/* inputs */);

    expect($result)->toBe(/* expected output */);
});

it('throws when given invalid input', function () {
    $service = new YourService();

    expect(fn () => $service->calculate(-1))->toThrow(\InvalidArgumentException::class);
});
```

### Integration Test (Service with DB side-effects)

```php
<?php

declare(strict_types=1);

namespace Modules\YourModule\Tests\Integration;

use Modules\YourModule\Models\YourModel;
use Modules\YourModule\Services\YourService;
use Tests\IntegrationTestCase;

it('persists the expected state after the operation', function () {
    $model = YourModel::factory()->create(['status' => 'draft']);

    app(YourService::class)->process($model);

    expect($model->fresh()->status)->toBe('processed');
});
```

### External API Call (Boundary Contract)

```php
<?php

declare(strict_types=1);

it('handles gateway failure gracefully', function () {
    Http::fake([
        'api.external.com/*' => Http::response(['error' => 'rate_limit'], 429),
    ]);
    Http::preventStrayRequests();

    expect(fn () => app(ExternalService::class)->call())
        ->toThrow(\App\Exceptions\GatewayException::class);
});
```

## Forbidden Patterns (Do Not Write These)

### ❌ Framework Wiring Test — Verifies Laravel, Not Your Code

```php
// BAD: This tests that BelongsTo exists in Laravel, not your model.
it('client belongs to company', function () {
    expect(new Client())
        ->client()
        ->toBeInstanceOf(BelongsTo::class); // FORBIDDEN
});
```

### ❌ makePartial on the Service Under Test

```php
// BAD: You are mocking the object you are testing.
// The internal method can do anything — you get no real coverage.
$service = Mockery::mock(QuoteService::class)->makePartial();
$service->shouldReceive('createQuote')->andReturn($fakeQuote);
// The real createQuote logic is never exercised.
```

**Correct pattern:** use a real service with a faked repository/Http.

### ❌ Hollow Assertion

```php
// BAD: This test always passes regardless of what the service does.
it('creates a quote', function () {
    $service = app(QuoteService::class);
    $quote = $service->createQuote($client, $data);
    expect($quote)->not->toBeNull(); // Every object will pass this
});
```

**Correct pattern:** assert the specific state produced: `$quote->status`, `$quote->line_items->count()`.

### ❌ UI Copy as Business Rule Proxy

```php
// BAD: Brittle to any phrasing change, tests no behavior.
$response->assertSee('Your invoice has been generated successfully.');
```

**Correct pattern:**
```php
$response->assertStatus(201);
expect(Invoice::latest()->first()->status)->toBe('published');
```

### ❌ RefreshDatabase in a Unit Test Folder

```php
// BAD: This makes your "unit" test 10x slower and couples it to the DB.
namespace Modules\YourModule\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;

class YourServiceTest extends UnitTestCase
{
    use RefreshDatabase; // FORBIDDEN in Tests/Unit/ — move to Tests/Integration/
}
```

## Anti-Pattern Detection Commands

Run before submitting a PR:

```bash
# Count your assertSee additions (target: 0 new per PR)
git diff --unified=0 | grep '^\+.*assertSee\b' | wc -l

# Count new RefreshDatabase in unit folders (target: 0)
git diff --unified=0 | grep -E '^\+.*RefreshDatabase' -- 'Modules/*/Tests/Unit/*.php' | wc -l

# Check for makePartial on live services (target: 0)
git diff --unified=0 | grep '^\+.*makePartial()' | wc -l
```

## Module Test Folder Structure (Required)

Every module MUST have:

```
Modules/YourModule/Tests/
├── Unit/           ← service logic, pure computation, no DB
├── Integration/    ← persistence, events, DB-backed behavior
└── Feature/        ← HTTP routes, forms, middleware
```

Browser tests may be added only in `Modules/YourModule/Tests/Browser/` with written
justification in the test file's docblock.

---

## Part 8 — Risks and Mitigations

| Risk | Impact | Mitigation |
|---|---|---|
| Deleting too aggressively removes valid confidence | High | Delete in bounded batches (≤ 15 files), run full suite after each batch |
| Phase 3 services are tightly coupled, making unit tests hard | Medium | Introduce repository interfaces first, then swap to fakes in tests |
| Mutation testing CI time exceeds budget | Medium | Run infection in nightly CI only; block on score regression, not absolute score |
| Allowlist entries are never cleaned up | Low | Phase 6C expiry date enforcement makes this a hard CI failure |
| Phase 4 green runs are green in CI but not locally | Medium | Specify exact process count and seed for repeatability gate |
| Cross-module chain tests become flaky under parallel execution | Medium | Each chain test must use isolated factories; no shared fixtures |
| Developer guide becomes stale | Low | Phase 9 guide-currency CI check auto-fails if not updated annually |

---

## Appendix A — Execution Model

### Recommended Phase Parallelism

```
Phase 0 ──────────────────────────────────────────────────────► [complete]
Phase 1 ──────────────────────────────────────────► [complete after Phase 0]
Phase 2 ─────────────────────────────────► [complete after Phase 1]
Phase 3 ──────────────────────────────────────────────────────► [complete after Phase 2]
              Phase 6 ──► [starts after Phase 3]
              Phase 7 ──► [starts after Phase 6]
                        Phase 9 ─────────────────────► [parallel with 6-8]
Phase 4 ──► [starts after Phase 3]
Phase 5 ──► [starts after Phase 4]
                                 Phase 8 ──► [starts after Phase 5 + Phase 7]
                                                        Phase 10 ──► [last]
```

### CI Commands Reference

```bash
# Full suite (with automatic timestamped log)
php artisan test

# Fast gate: unit + integration only
php artisan test --testsuite=Unit,Integration

# Module-specific run
php artisan test Modules/PIB/Tests
php artisan test Modules/ContractManager/Tests

# Architecture guards only
php artisan test --filter "Architecture"

# Check for FAILED in latest log
grep "FAILED" reports/test-results-latest.log

# Count consecutive green runs
grep -rL "FAILED" reports/test-results-*.log | tail -10
```

---

*End of Document — Version 1.0*
