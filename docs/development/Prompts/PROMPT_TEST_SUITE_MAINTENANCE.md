# Prompt: Test Suite Quality & Maintenance Planning

**Role:** Test Quality Architect & Automation Engineer

**Objective:**
Conduct a comprehensive audit of the test suite's health across six key dimensions, generate **LLM-actionable quality metrics**, and produce a prioritized implementation plan that directly maps to the phased strategy in `TEST_SUITE_MASTER_PHASE_PLAN_6_5_TO_10.md`.

---

## Part 1 — Quality Dimensions (Six Pillars)

Your audit must evaluate the suite across these six orthogonal dimensions. Each produces **quantifiable targets** that feed directly into phase exit criteria.

### Dimension 1: Reliability
**Question:** How consistently does the suite pass when re-run?

**Metrics to Gather:**
- Count of parallel test runs in `reports/test-results-*.log` in the last 7 days.
- Of those, how many are fully green (zero FAILED)?
- Identify any tests consistently failing or marked `@group flaky-triage`.
- Document the longest continuous streak of green runs.

**Target (Phase 4 at 8.6):** 3 consecutive green parallel runs without hangs or timeouts.

**Scan Commands:**
```bash
# Count all run logs
ls -1 reports/test-results-*.log | wc -l

# Count fully green runs
grep -l "passed" reports/test-results-*.log | xargs grep -L "FAILED" | wc -l

# List all flaky-annotated tests
grep -RIn "@group flaky-triage\|@flaky\|quarantine" tests Modules --include='*.php'

# Recent FAILED tests
grep "FAILED\|Failed" reports/test-results-latest.log | head -20
```

**Assessment Output:**
```
Reliability Score: ___/10
- Green run streak: ___ (target: 3)
- Flaky tests: ___ (target: 0 untracked)
- Last green run: ___ (timestamp from log)
- Action: [Stabilize] [Triage] [Check infrastructure]
```

---

### Dimension 2: Signal Quality (Low Noise)
**Question:** What fraction of tests are testing framework internals vs. domain behavior?

**Metrics to Gather:**
1. **Junk assertion count** (framework wiring tests):
   - `assertInstanceOf` with `Relation` classes: count.
   - Tests that only assert model `fillable`, `cast`, or `appends`: count.
   - Tests with zero assertions or only `assertTrue(true)`: count.

2. **UI-copy brittleness**:
   - `assertSee` / `assertSeeText` occurrences.
   - Ratio of broad copy assertions to behavioral assertions.

3. **Mocking quality**:
   - `makePartial()` usage (false confidence indicator).

4. **Test-to-code ratio**:
   - Line count in test files vs. application code.

**Target (Phase 1 at 7.2):**
- Relations assertions: **0** (from 26)
- `assertSee/assertSeeText`: **≤150** (from 240)
- `makePartial()`: **≤10** (from 33)
- Signal-to-noise ratio: > 0.85 (where noise = framework tests, signal = domain tests)

**Scan Commands:**
```bash
# Relation assertions
grep -RIn "assertInstanceOf.*\(BelongsTo\|HasMany\|HasOne\|BelongsToMany\)" tests Modules --include='*.php' | wc -l

# assertSee/assertSeeText
grep -RIn "assertSee\b\|assertSeeText\b" tests Modules --include='*.php' | wc -l

# makePartial usage
grep -RIn "makePartial()" tests Modules --include='*.php' | wc -l

# Tests with no assertions
grep -RIn "@test\|function test_\|it(" tests Modules --include='*.php' -A 3 | grep -v "assert\|expect" | grep "^[^-]*function\|^[^-]*it(" | wc -l

# Breakdown by module (top offenders)
grep -RIn "assertSee\b" Modules/*/Tests --include='*.php' | cut -d: -f1 | sort | uniq -c | sort -rn | head -10
```

**Assessment Output:**
```
Signal Quality Score: ___/10
- Junk assertions: ___ (target: 0)
  - Relation assertions: ___
  - Fillable/cast-only tests: ___
  - No-op tests: ___
- Brittleness: assertSee = ___ (target: ≤150)
- makePartial usage: ___ (target: ≤10)
- Top offending modules: [list]
- Action: [Delete junk] [Refactor brittle] [Replace makePartial]
```

---

### Dimension 3: Pyramid Balance
**Question:** Are tests distributed correctly across Unit / Feature / Integration / Browser layers?

**Metrics to Gather:**
1. **Layer distribution**:
   - Count files in `tests/Unit`, `tests/Feature`, `tests/Integration`, `tests/Browser`.
   - Count test methods in each.
   - Rough line-of-code sum per layer.

2. **Database usage discipline**:
   - Files using `RefreshDatabase` in `Unit/` scope: count and list.
   - Cross-module model instantiation in `Unit/` tests: count.

3. **Speed by layer** (from test execution logs):
   - Average duration per test method in each layer.

4. **Module-level pyramid balance**:
   - For critical modules (ContractManager, PIB, CaseManager), count tests by layer.

**Target (Phase 2 at 7.8):**
- Unit tests: ≥55% of total.
- Feature tests: ≥25%.
- Integration tests: ≥15%.
- Browser tests: ≤5%.
- `RefreshDatabase` in unit tests: **0** (currently ~11 allowlisted, target ≤5).
- Cross-module DB access in unit tests: **0** (currently ~39).

**Scan Commands:**
```bash
# File count by layer
find tests/Unit -name '*Test.php' -o -name '*Pest*Test.php' | wc -l
find tests/Feature -name '*Test.php' -o -name '*Pest*Test.php' | wc -l
find tests/Integration -name '*Test.php' -o -name '*Pest*Test.php' | wc -l
find tests/Browser -name '*Test.php' -o -name '*Pest*Test.php' | wc -l

# RefreshDatabase in unit tests
grep -RIl "RefreshDatabase" tests/Unit --include='*.php'

# Module distribution (e.g., CaseManager)
find Modules/CaseManager/Tests/Unit -name '*Test.php' | wc -l
find Modules/CaseManager/Tests/Feature -name '*Test.php' | wc -l
find Modules/CaseManager/Tests/Integration -name '*Test.php' | wc -l

# Cross-module creates in unit tests
grep -RIn "Modules/[^/]*/Models" tests/Unit Modules/*/Tests/Unit --include='*.php' | grep -v "use Modules"
```

**Assessment Output:**
```
Pyramid Balance Score: ___/10
- Unit: ___% (target: ≥55%)
- Feature: ___% (target: ≥25%)
- Integration: ___% (target: ≥15%)
- Browser: ___% (target: ≤5%)
- RefreshDatabase in unit (allowlisted): ___ (target: ≤5)
- Cross-module DB access (unit): ___ (target: 0)
- Critical modules in pyramid inversion: [list]
- Action: [Shift to unit] [Remove DB access] [Rebalance modules]
```

---

### Dimension 4: Module Isolation (Architecture)
**Question:** Are module boundaries enforced? Are cross-module dependencies properly contracted?

**Metrics to Gather:**
1. **Guard test status**:
   - Run `ModuleUnitIsolationGuardTest` and `ModuleBoundaryContractsTest`; report green/red.
   - If red, list violations.
   - Count items in the `$allowlistedRefreshDatabaseBaseline` and `$guardedGatewayHotspots`.

2. **Cross-module imports in unit tests**:
   - Scans for direct imports of models/services from other modules in unit tests.
   - Exceptions list (allowlist).

3. **Event-driven architecture health**:
   - Count event listener tests vs. direct service call tests.
   - Identify modules that should communicate via events but use direct imports.

**Target (Phase 6+ at 9.5):**
- All guard tests pass (zero allowlist exceptions).
- 100% of inter-module communication is via events, facades, or contracts.
- No direct cross-module model instantiation in unit tests.

**Scan Commands:**
```bash
# Run guard tests
php artisan test tests/Unit/ModuleUnitIsolationGuardTest.php tests/Architecture/

# List current allowlist
grep -A 20 "allowlistedRefreshDatabaseBaseline\|guardedGatewayHotspots" tests/Unit/ModuleUnitIsolationGuardTest.php

# Cross-module event listeners
grep -RIn "implements ShouldQueue\|extends Listener" Modules/*/Listeners --include='*.php' | wc -l

# Modules with no event listeners (bad isolation)
for dir in Modules/*/; do
  count=$(find "$dir/Listeners" -name '*.php' 2>/dev/null | wc -l)
  [ "$count" -eq 0 ] && echo "$(basename "$dir"): $count listeners"
done
```

**Assessment Output:**
```
Module Isolation Score: ___/10
- Guard tests: [PASS] / [FAIL]
  - If FAIL, violations: [list]
- Allowlist size: ___ items (target: 0)
- Cross-module coupling (violations): ___ (target: 0)
- Event-driven health: ___% (target: >90%)
- Modules without proper boundaries: [list]
- Action: [Run guards] [Refactor imports] [Harden event architecture]
```

---

### Dimension 5: Coverage & Mutation (Deep Testing)
**Question:** Are tests actually catching bugs? Is critical code tested at all?

**Metrics to Gather:**
1. **Critical service test coverage**:
   - For each critical service (QuoteService, BillingAnalysisService, etc.), count unit test files directly testing it.
   - Current state: most are at 0.

2. **Mutation score** (if infection/infection is run):
   - Overall mutation score (MSI).
   - Per-service mutation score for critical financial services.
   - Ratio of survived mutants to killed mutants.

3. **Line coverage** (if coverage report exists):
   - Overall coverage %.
   - Coverage for critical services (target: ≥90%).

4. **Test-to-functionality ratio**:
   - For untested critical services, estimate effort to reach 85% coverage + >80% mutation score.

**Target (Phase 3 at 8.6):**
- All critical financial services have unit test files.
- Line coverage ≥85% for critical services.
- Mutation score ≥75% overall, ≥80% for financial arithmetic.

**Scan Commands:**
```bash
# Critical services with zero unit tests
for svc in QuoteService BillingTemplateService BillingAnalysisService \
            VendorReconciliationService LicenseDeploymentService ProrationService; do
  count=$(grep -RIl "class $svc" . --include='*.php' | xargs grep -l "Test\|@test")
  echo "$svc: $count test files"
done

# Run mutation tests on critical module
vendor/bin/infection --test-framework=pest \
  --coverage=reports/coverage \
  Modules/PIB/Tests/Unit

# Extract mutation score from report
grep "Mutation Score Indicator" reports/mutation-report.html || echo "No mutation report"

# Estimate test effort (quick method)
wc -l app/Services/QuoteService.php Modules/*/Services/*.php | sort -n | tail -10
```

**Assessment Output:**
```
Coverage & Mutation Score: ___/10
- Critical services with test files: ___/8 (target: 8/8)
- Untested critical services: [list]
- Overall line coverage: ___% (target: ≥85%)
- Mutation score (MSI): ___% (target: ≥75%)
  - Financial arithmetic services: ___% (target: ≥80%)
  - Boundary/validation logics: ___% (target: ≥75%)
- Estimated effort to reach targets: ___ person-days
- Action: [Add unit tests] [Run mutation] [Refactor brittle code]
```

---

### Dimension 6: Developer Experience (DX)
**Question:** Is it easy for developers to write and run tests correctly?

**Metrics to Gather:**
1. **Documentation clarity**:
   - Docs exist at `tests/testing_standards.md`: yes/no.
   - Docs cover unit/feature/integration distinctions: yes/no.
   - Docs mention mutation testing: yes/no.
   - Examples for common testing patterns (auth, HTTP, factories): yes/no.

2. **Test execution friction**:
   - Run time for `php artisan test` (all three lanes): measure from logs.
   - Parallel execution enabled: yes/no.
   - Clear failure messages in CI output: yes/no.

3. **Tooling and CI**:
   - CI jobs configured per lane (Unit, Module, Browser): yes/no.
   - Automated architecture guards running: yes/no.
   - Mutation test CI integration: yes/no.
   - Code coverage reports visible in CI: yes/no.

4. **Onboarding**:
   - New team members can run and write tests without asking questions: yes/no.
   - Common errors (e.g., RefreshDatabase in unit tests) are caught by guardrails: yes/no.

**Target (Phase 9 at ~9.5):**
- Comprehensive, examples-rich testing standards file.
- < 90 seconds for PR gate lane (Unit + Integration).
- All guardrails automated and enforced in CI.
- Zero friction for developers writing correct tests.

**Scan Commands:**
```bash
# Check docs
[ -f "tests/testing_standards.md" ] && echo "Docs exist" || echo "Docs missing"
grep -i "unit\|feature\|integration" tests/testing_standards.md | wc -l

# Check CI configuration
[ -f ".github/workflows/test.yml" ] && echo "GitHub Actions found" || echo "No CI config"
grep -i "parallel\|workers" .github/workflows/test.yml || echo "No parallel config"

# Test execution time
tail -5 reports/test-results-latest.log | grep -i "time"
```

**Assessment Output:**
```
Developer Experience Score: ___/10
- Documentation: [COMPLETE] / [PARTIAL] / [MISSING]
  - Mutation/coverage docs included: yes/no
  - Example patterns provided: yes/no
- Test execution speed (all lanes): ___s (target: <300s total)
- CI automation: [FULL] / [PARTIAL] / [MISSING]
  - Parallel execution enabled: yes/no
  - Architecture guards active: yes/no
  - Mutation CI integration: yes/no
- Guardrails enforced: [YES] / [PARTIAL] / [NO]
- Action: [Document] [Optimize speed] [Automate guards]
```

---

## Part 2 — Generating the Action Plan

Once you have gathered all six dimensions, synthesize a **phase-aligned action plan** that maps directly to exit criteria in the master plan.

### Action Plan Template

```
## Executive Summary
Current Test Suite Score: __.0/10
- Reliability: __/10 (Phase 0–4 focus)
- Signal Quality: __/10 (Phase 1 focus)
- Pyramid Balance: __/10 (Phase 2 focus)
- Isolation: __/10 (Phase 4–6 focus)
- Coverage & Mutation: __/10 (Phase 3 focus)
- Developer Experience: __/10 (Phase 9 focus)

**Estimated Current Score: __.0/10**
**Target Score by Q2 2026: 8.5/10**
**Target Score by Q3 2026: 10.0/10**

---

## Priority Phase Roadmap

### PHASE 0 (Stabilize) — 3–5 days
✅ Baseline documented
⬜ Reliability proof: 3 consecutive green runs
⬜ Guard tests enabled

**Tasks:**
1. [ ] Run full suite once and archive result in `reports/`.
2. [ ] Verify `ModuleUnitIsolationGuardTest` passes.
3. [ ] Document all flaky tests with GitHub issues.

---

### PHASE 1 (Junk Elimination) — 5–7 days
⬜ Relation assertions reduced to 0
⬜ assertSee reduced to ≤150
⬜ makePartial reduced to ≤10

**Priority modules (top offenders):**
[List modules with highest junk/brittleness]

**Tasks:**
1. [ ] Delete [N] relation assertion tests in [Module X].
2. [ ] Refactor [N] brittle UI-copy tests in [Module Y].
3. [ ] Replace [N] makePartial usages with Http::fake().

---

### PHASE 2 (Pyramid Rebalance) — 7–10 days
⬜ RefreshDatabase in unit scopes: ≤5 allowlisted (from 11)
⬜ Cross-module DB access: 0 (from 39)
⬜ Unit tests ≥55% of total

**Affected modules:**
[List modules with most refactoring needed]

**Tasks:**
1. [ ] Move [N] database-using tests from Unit → Integration in [Module].
2. [ ] Mock DB dependencies in [N] remaining unit tests.
3. [ ] Verify allowlist shrinks by 6 items.

---

### PHASE 3 (Critical Coverage) — 10–15 days
⬜ QuoteService: unit test file created, coverage ≥85%
⬜ BillingAnalysisService: unit test file created, coverage ≥85%
⬜ [Other critical services]

**Complexity estimate by service:**
- QuoteService: ⭐⭐⭐⭐ (high complexity, many branches)
- BillingAnalysisService: ⭐⭐⭐⭐
- ProrationService: ⭐⭐⭐ (medium)
- [Others]: [Estimate]

**Tasks:**
1. [ ] Write 20–30 tests for QuoteService::calculateQuote() logic.
2. [ ] Write 15–20 tests for BillingAnalysisService::analyzeInstalledBases().
3. [ ] Run mutation tests; achieve >80% kill rate for both.

---

### PHASE 4–6 (Hardening) — Ongoing
🔄 Reliability: 3 consecutive green runs (Phase 4)
🔄 Isolation: all guards passing, zero allowlist items (Phase 6)
🔄 Mutation CI: fully integrated (Phase 8)

---

## Resource Estimation

| Phase | Dev Days (Est.) | Blocker? | Owner |
|---|---:|:---:|---|
| 0 | 1–2 | ⏰ | QA Lead |
| 1 | 5–7 | ❌ | Backend Team |
| 2 | 7–10 | ❌ | Backend Team |
| 3 | 10–15 | ⏰ | Senior Backend + QA |
| 4–6 | 5–8 per phase | ❌ | Ongoing |
| 9 | 2–3 | ❌ | QA Lead |

**Critical Path:** Phases 0 → 1 → 2 → 3 → 4 (sequential, ~40 days).
**Parallel:** Phase 9 (DX improvements) can run during Phases 1–3.

---

## Success Metrics & Gates

### Phase 0 Exit Gate
- [ ] Baseline scorecard from Part 1 documentation is complete.
- [ ] One full parallel test run artifact in `reports/`.
- [ ] All architecture guard tests pass.

### Phase 1 Exit Gate
- [ ] Relation assertions = 0 (from 26).
- [ ] assertSee ≤ 150 (from 240).
- [ ] makePartial ≤ 10 (from 33).
- [ ] Full suite still green.

### Phase 2 Exit Gate
- [ ] RefreshDatabase allowlist ≤ 5 (from 11).
- [ ] Cross-module DB access = 0 (from 39).
- [ ] Unit tests ≥ 55% of total.

### Phase 3 Exit Gate
- [ ] All 8 critical financial services have unit test files.
- [ ] Coverage ≥ 85% for each.
- [ ] Mutation score ≥ 80% for each.

### Phase 4 Exit Gate
- [ ] 3 consecutive green parallel runs documented.
- [ ] Zero regressions in earlier phases.

---

## Risks & Mitigation

| Risk | Impact | Mitigation |
|---|:---:|---|
| Phase 3 reveals complex branching (e.g., QuoteService has 50+ branches). | ⏰ Schedule slips. | Start Phase 3 prep early (write skeleton, identify edge cases). |
| Mutation testing reveals fragile tests; many survivors emerge. | 🔴 High rework. | Incremental mutation runs; prioritize arithmetic operators first. |
| Browser test flakiness blocks reliable runs. | 🔴 Reliability unreachable. | Isolate browser tests into separate CI lane; enforce Dusk/Playwright best practices. |
| New developers add refactored database code to unit tests. | 🟡 Guard regression. | Automate `ModuleUnitIsolationGuardTest` in every CI run; fail fast. |

---

## Long-Term Cadence (Post-Phase 10)

Once the suite reaches 10.0/10, maintain quality with:

- **Weekly:** Audit newly added flaky tests; triage failures.
- **Bi-weekly:** Mutation score check on modified critical services (target: >80%).
- **Monthly:** Full scorecard update (all six dimensions); publish to team.
- **Quarterly:** Pyramid audit; roadmap next phase (e.g., property-based testing).

```

---

## Part 3 — LLM-Actionable Implementation Spec

When you receive this prompt from a senior developer, follow this execution sequence:

### Step 1: Gathering (Non-Blocking)
Run all scan commands listed above in **parallel** (on separate terminal tabs or in a background script). Do not wait for all to complete; proceed to interpretation while scans run.

### Step 2: Assessment (Interpret Results as They Arrive)
As scan results come in, fill in the assessment outputs from Part 1. If a result is missing, mark as ⏳ (pending) and re-submit the scan command at the end.

### Step 3: Synthesis (Create the Action Plan)
Based on Part 1 assessment, create the full action plan template from Part 2 with:
- **Concrete phase-by-phase tasks** (not vague guidance).
- **Specific modules and files** to refactor (not "fix brittleness in tests").
- **Estimated effort** per task (in hours or days).
- **Blocking sequence** (which tasks must complete before others).
- **Resource allocation** (assign dev names if available, or outline required roles).

### Step 4: Delivery
Output the plan in **Markdown** format, suitable for:
- Pasting into a GitHub Wiki or Confluence page.
- Importing into a project management tool (Jira, GitHub Projects).
- Sharing directly with the development team.

Include a **checkboxes** list (markdown `[ ]`) so progress can be tracked over time.

---

## Invocation

**When:** Whenever test suite quality becomes a concern, or before planning a major sprint.

**How:** Invoke this prompt with:
```
You are a Test Quality Architect. Audit the test suite and generate a phased
action plan using PROMPT_TEST_SUITE_MAINTENANCE. Gather all six dimensions,
assess current state, and output a prioritized roadmap for reaching 8.5/10
by end of Q2 2026.
```

**Output Expected:** A markdown document suitable for team sharing and Jira integration, with concrete tasks, timelines, and success gates.

---

## Relationship to Other Prompts & Plans

This prompt is **subordinate to** the master test suite strategy:
- **Master Plan:** `docs/development/WIP/TEST_SUITE_MASTER_PHASE_PLAN_6_5_TO_10.md`
- **Phase 6.5–8.5 Baseline:** `docs/development/WIP/TEST_SUITE_6_5_TO_8_5_PHASE_PLAN.md`
- **Testing Standards (Developer-facing):** `tests/testing_standards.md`
- **Maintenance Cadence:** `docs/development/TEST_MAINTENANCE_CADENCE.md`

Use this prompt to **operationalize** the master plan: turning strategic phases into actionable sprints.
