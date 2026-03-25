# Track A - Reliability And Mutation Baseline

## Goal
Turn reliability from inferred confidence into measured confidence by restoring green guardrails and producing the first actionable mutation baseline.

## Why This Track Exists
- The suite currently has 1 failing governance test: `tests/Unit/FeatureWriteAssertionDepthGuardTest.php`.
- `infection.json5` exists, but `reports/infection.log` and `reports/infection-summary.log` are empty.
- Mutation scope currently covers only:
  - `Modules/PIB/Services`
  - `Modules/ContractManager/Services`
  - `Modules/Payment/Services`

## Primary Deliverables
- [x] Suite green with the shallow-write guard restored.
- [x] First mutation report generated and stored under `reports/`.
- [x] Recommendation on whether to keep the initial mutation scope or expand it by one additional namespace.

## Tasks
### A1. Fix the current ratchet failure
- [x] Open and inspect:
  - `tests/Unit/FeatureWriteAssertionDepthGuardTest.php`
  - `tests/Feature/DeploymentManager/TsdmActivationRateLimitingTest.php`
  - `tests/Feature/Webhooks/Action1ScriptCallbackRateLimitingTest.php`
  - `tests/Feature/Webhooks/GoogleChromeWebhookRateLimitingTest.php`
- [x] For each flagged file, add at least one meaningful side-effect assertion:
  - database state
  - event dispatch
  - queue behavior
  - rate limiter state
  - durable application state transition
- [x] Re-run just the guard and the 3 affected files.
- [x] Re-run the full suite after the targeted fixes pass.

### A2. Produce the first mutation baseline
- [x] Run Infection on the currently configured scope.
- [x] Save outputs to:
  - `reports/infection.log`
  - `reports/infection-summary.json`
- [x] Record these values in the PR or follow-up note:
  - MSI
  - covered MSI
  - escaped mutants by file/namespace
- [x] Identify the worst surviving mutant cluster and convert it into a concrete test task.

### A3. Decide the next mutation expansion step
- [ ] Review whether one high-value namespace should be added next:
  - `Modules/AppHealth/Services`
  - `app/Actions`
  - `Modules/SoftwareSubscriptions/Services`
- [x] Propose a bounded next-scope patch to `infection.json5`.
- [x] Document runtime impact and whether this belongs in main CI or scheduled CI.

## Acceptance Markers
- [x] `tests/Unit/FeatureWriteAssertionDepthGuardTest.php` passes without loosening expectations.
- [x] Mutation score exists as a real measured artifact, not only config.
- [x] At least 1 namespace-level mutation weakness is converted into a new test task.

## Suggested Agent Assignment
- Best for an agent focused on failing tests, assertion quality, and mutation analysis.

## Execution Notes

### Completed In This Track
- The shallow-write ratchet failure was fixed without loosening the baseline.
- The affected files now assert real side effects rather than only status codes:
  - `tests/Feature/DeploymentManager/TsdmActivationRateLimitingTest.php`
  - `tests/Feature/Webhooks/Action1ScriptCallbackRateLimitingTest.php`
  - `tests/Feature/Webhooks/GoogleChromeWebhookRateLimitingTest.php`
- Full suite result after the fix: `6219 passed, 3 skipped` in about `109.93s`.

### Mutation Baseline Outcome
- Infection was executed successfully against the configured scope using a repo-owned wrapper plus JUnit normalization step for Pest compatibility.
- Generated artifacts:
  - `reports/infection.log`
  - `reports/infection-summary.json`
  - `reports/infection-summary.log`
- Baseline metrics:
  - total mutants: `1378`
  - killed: `1143`
  - escaped: `0`
  - uncovered: `0`
  - skipped: `235`
  - MSI: `100`
  - covered MSI: `100`
- Runtime: about `28s` for Infection after coverage generation; about `21m` including the full coverage-producing Pest run.

### Mutation Compatibility Workaround
- The repository now uses a bounded compatibility layer for Infection 0.32.6 plus Pest-generated JUnit metadata.
- The workaround lives in:
  - `scripts/testing/normalize-pest-junit-for-infection.php`
  - `scripts/testing/run-infection-baseline.sh`
- The normalizer fixes three incompatibilities:
  - duplicate testcase class lookups
  - mixed plain-vs-`P\` Pest class aliases
  - non-path `file` attributes in JUnit nodes

### Concrete Follow-Up Task
- Worst mutation hotspot is `Modules/PIB/Services/InvoiceGenerator.php` with `115` skipped mutants.
- Convert this into the next test task:
  - add focused tests around invoice payload creation, default company fallback, nullable client/company handling, and period-date defaulting/coalescing in `InvoiceGenerator`
- Secondary hotspot:
  - `Modules/PIB/Services/EntitlementEngineService.php` visibility mutation on `registerResolver()`

### Recommended Next Step
- Keep the current mutation scope stable for one more pass and address `InvoiceGenerator` before broadening scope.
- After the `InvoiceGenerator` gap is reduced, the best expansion candidate remains `app/Actions` because the audit showed the largest business-logic testing gap there relative to source count.

### Scheduling Recommendation
- Mutation should still stay out of the main per-push CI lane for now.
- Use the wrapper in a scheduled or manually triggered lane first because the full baseline flow includes a long coverage-generation step.
