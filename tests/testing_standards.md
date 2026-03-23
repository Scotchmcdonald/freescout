# Laravel Testing Standards & Best Practices

> **Scope:** These are **target standards** we are progressively implementing through the phased test suite improvement roadmap (`TEST_SUITE_MASTER_PHASE_PLAN_6_5_TO_10.md`). The current codebase does not fully comply with all standards below. Use this document as the canonical specification to guide test improvements and the LLM-driven maintenance process via `PROMPT_TEST_SUITE_MAINTENANCE.md`.
>
> **Current Baseline (as of 2026-03-18):** Score 6.5/10. See Part 1 of the master plan for detailed compliance gaps.
>
> **Roadmap Outcomes:** Use `docs/testing/TESTING_ROADMAP_OUTCOMES.md` for durable outcomes, cadence, and escalation triggers.
>
> **Operational References:**
> - `docs/testing/TESTING_CONTRIBUTION_GUIDE.md`
> - `docs/testing/TEST_MAINTENANCE_CADENCE.md`

## 1. The Core Philosophy
We test to ensure **business logic remains intact** during refactoring and to provide **living documentation** of the application's features. We do not waste resources testing the Laravel framework itself. We pursue **deep branch coverage** on critical financial and domain services, and we measure code coverage through both traditional metrics and **mutation testing**.

---

## 1.5 Current Compliance & Roadmap

**This document defines _target_ compliance levels across six dimensions:**

| Dimension | Current State | Target State | Phase |
|---|:---:|:---:|---|
| **Reliability** | ~1–2 consecutive green runs | 3+ consecutive green runs | 0–4 |
| **Signal Quality** | 240 assertSee, 26 relation assertions, 33 makePartial | ≤150 assertSee, 0 relations, ≤10 makePartial | 1 |
| **Pyramid Balance** | Skewed toward integration/browser | 55% unit, 25% feature, 15% integration, ≤5% browser | 2 |
| **Isolation** | 11 RefreshDatabase in unit tests, 39 cross-module DB creates | 0 allowlisted exceptions, zero cross-module coupling | 2–6 |
| **Coverage & Mutation** | 0 unit tests for 8 critical financial services | All critical services ≥85% coverage + ≥80% mutation score | 3 |
| **Developer Experience** | Basic standards, no mutation docs, manual guards | Comprehensive docs, automated guards, mutation CI integration | 9 |

**How to Use This Document:**
1. **As a developer:** Reference Section 9 (Test Contribution Checklist) when writing new tests.
2. **As a maintenance agent:** Run `PROMPT_TEST_SUITE_MAINTENANCE.md` to audit current state against these targets and generate actionable phase tasks.
3. **As a team lead:** Use the phase roadmap in `TEST_SUITE_MASTER_PHASE_PLAN_6_5_TO_10.md` to sequence work and track progress.

---

## 2. The Testing Hierarchy
We follow a balanced Testing Pyramid to optimize for both confidence and execution speed.

| Test Type | Frequency | Focus | Target Speed | Mutation Coverage |
| :--- | :--- | :--- | :--- | :--- |
| **Unit** | ~60% | Pure logic, helper classes, DTOs (No DB). | < 50ms | High mutation kill rate (>80%) for critical paths |
| **Feature** | ~30% | Controllers, Middleware, Form Requests. | < 200ms | Medium mutation kill rate (>60%) |
| **Integration** | ~7% | External APIs, Service Providers, Modules. | < 1s | Medium mutation kill rate (>60%) |
| **End-to-End** | ~3% | Critical "Happy Paths" (Dusk/Playwright). | Slow | Selective (edge cases only) |

---

## 3. The "No-Fly List" (Identifying Junk Tests)
To keep the suite lean, **do not** write tests for the following:
* **Eloquent Relationships:** (e.g., `User hasMany Post`) — This is framework-level logic. ❌ 0 allowed.
* **Basic CRUD:** Saving a model to the DB without custom logic/observers.
* **Native Validation:** Testing that `required` works (test custom rules only).
* **Static Config:** Verifying that a `config()` value matches a file.
* **Framework internals:** Tests that verify Laravel's internals (e.g., `assertInstanceOf(BelongsTo::class, ...)`).

**Current Targets (Phase 1—7.2):**
- `assertInstanceOf…Relations` count: **0** (from 26)
- `assertSee` / `assertSeeText` count: **≤150** (from 240)
- `makePartial()` count: **≤10** (from 33)

---

## 4. Modular Isolation Standards
In our modular architecture, tests must respect strict boundaries:
* **No Cross-Talk:** A test for `Module A` must never directly query `Module B` database tables. Unit tests in any `Tests/Unit/` scope **must not use `RefreshDatabase`**.
* **Contract Testing:** Use Interfaces/Service Providers to interact with other modules; mock these in Unit tests.
* **Scoped Factories:** Keep factories localized to the module they represent.
* **Allowlist Discipline:** The `ModuleUnitIsolationGuardTest` allowlist shrinks over time. No new exceptions without explicit tracking.

**Current Targets:**
- `RefreshDatabase` in unit scopes: **0** (currently allowlisted: 11 files, target: ≤5)
- Cross-module model instantiation in unit tests: **0** (from 39)

---

## 5. Technical Requirements

### 5.1 Database
- Use `RefreshDatabase` for Feature and Integration tests.
- Prefer `sqlite :memory:` for speed (configured in testing `.env`).
- Never use `RefreshDatabase` in Unit tests (mock the database layer instead).

### 5.2 HTTP & External APIs
- **Always** use `Http::fake()` for all external API calls.
- **Never** hit a live production/staging API in test environments.
- Implement dedicated spy/fake classesfor third-party API adapters (Stripe, Helcim, etc.).

### 5.3 Mocking & Faking
- Prefer **real implementations** over mocks whenever the database is acceptable.
- Use `Mockery::mock()` or `Pest::mock()` *only* when:
  - The dependency is external (HTTP, file I/O, clock).
  - Unit isolation is explicitly required.
  - The dependency is a cross-module contract that has integration tests elsewhere.

### 5.4 Naming
- Use descriptive, human-readable names: `it_prevents_non_admins_from_deleting_modules`.
- Group related tests in describe blocks: `describe('Authorization', fn() => { ... })`.
- For mutation-critical paths, prefix with `@mutation-sensitive`: `it_rejects_invoice_with_negative_amount`.

### 5.5 State & Idempotence
- Tests must be **stateless**. One test's success must never depend on a previous test's data.
- Listeners extending `IdempotentListener` must guarantee idempotent execution on retry.
- Use `actAsUser()` or factory-built users with clean state; never reuse global fixtures.

---

## 6. Mutation Testing & Coverage Thresholds

### 6.1 Overview
Mutation testing (via **infection/infection**) verifies that your tests are *actually catching bugs*. A mutant is a deliberately introduced code change; if tests pass with the mutant in place, that's a **killed mutant** (bad). If tests fail, the mutant is **survived** (excellent).

### 6.2 Critical Financial Services (Phase 3 Targets)
The following services handle financial arithmetic, billing logic, and decisioning. They require **both** high line coverage **and** high mutation kill rate:

| Service | Unit Tests | Integration Tests | Mutation Kill Target |
| :--- | :---: | :---: | ---: |
| `QuoteService` | ❌ (0) | ➖ (external only) | >85% |
| `BillingTemplateService` | ❌ (0) | ➖ (external only) | >85% |
| `InvoiceGenerator` | ⚠️ (1 file) | ➖ (minimal) | >80% |
| `BillingAnalysisService` | ❌ (0) | ➖ (external only) | >85% |
| `HelcimService` | ➖ (wrapper) | ✅ (spy) | >70% |
| `ProrationService` | ❌ (0) | ➖ (computed) | >80% |
| `VendorReconciliationService` | ❌ (0) | ➖ (external only) | >85% |
| `LicenseDeploymentService` | ❌ (0) | ➖ (external only) | >75% |

### 6.3 Running Mutation Tests

```bash
# Run mutation tests on a specific test file
vendor/bin/infection --test-framework=pest tests/Unit/Services/QuoteServiceTest.php

# Run with detailed output (list killed/survived mutants)
vendor/bin/infection --test-framework=pest --show-mutations tests/Unit/Services/

# Generate HTML report
vendor/bin/infection --test-framework=pest --coverage=reports/coverage --html=reports/mutation-report.html
```

### 6.4 Mutation Configuration
Configuration is in `infection.json5`. Key settings:
- `minMsi` (Mutation Score Indicator): Fail CI if mutation score < threshold (target: 75–80%).
- `minCoverageRequired`: Fail if line coverage < threshold (target: 85–90% for critical paths).
- `mutators`: Operators that introduce variations (arithmetic, conditional, return values, etc.).
- `timeout`: How long each mutant test run is allowed (default: 120s per mutant).

### 6.5 High-Value Mutations for Financial Logic
Focus on:
- **Arithmetic mutations:** `+` → `-`, `*` → `/`, boundary conditions (`≤` → `<`).
- **Return value mutations:** `true` → `false`, `null` returns.
- **Conditional mutations:** `&&` → `||`, edge case conditions.
- **Loop mutations:** Off-by-one, boundary conditions.

---

## 7. Pest Architecture Rules (Auto-Enforcement)
We use Pest's Architecture testing to programmatically enforce these standards. See `tests/ArchTest.php` and `tests/Architecture/` for enforcement details.

```php
// tests/ArchTest.php — Example rules
test('globals')
    ->expect(['dd', 'dump', 'ray', 'env'])
    ->not->toBeUsed();

test('unit-tests-have-no-refresh-database')
    ->expect('Tests\Unit\**')
    ->not->toUse('Illuminate\Foundation\Testing\RefreshDatabase');

test('models')
    ->expect('App\Modules\*\Models')
    ->toExtend('Illuminate\Database\Eloquent\Model')
    ->not->toUse('Illuminate\Support\Facades\DB'); // Use Repositories/Eloquent
```

---

## 8. Performance & CI Thresholds

### 8.1 Execution Time
The entire CI/CD testing pipeline must complete in under **5 minutes** for the default lane (Unit + Integration, non-browser).

| Lane | Suite | Target Time |
| :--- | :--- | ---: |
| PR Gate (A) | Unit + Integration | ≤90 seconds |
| Module Depth (B) | Modules (non-browser) | ≤5 minutes |
| Smoke (C) | Browser + E2E | ≤10 minutes |
| Full Mutation | Critical financial services only | ≤15 minutes |

### 8.2 Parallel Execution
- Run tests in parallel: `php artisan test --parallel --workers=8`.
- Browser tests run sequentially (Dusk/Playwright limitation).
- Use separate CI jobs for different lanes (A, B, C) to avoid resource contention.

### 8.3 Reliability Proof
- Each merge to main must have **3 consecutive green parallel runs** on Lane A (PR gate).
- Nightly builds must have **10 consecutive green parallel runs** on all lanes.
- Flaky tests are immediately tagged `@group flaky-triage` and tracked with a GitHub issue.

---

## 9. Test Contribution Checklist

Before committing tests, ensure:
- [ ] Test file is in the correct layer (`Unit`, `Feature`, `Integration`, `Browser`).
- [ ] Test name is descriptive and human-readable.
- [ ] No `RefreshDatabase` in Unit tests.
- [ ] No direct cross-module model instantiation in Unit tests.
- [ ] All external HTTP calls are faked with `Http::fake()`.
- [ ] State is scoped to the test (no global fixtures).
- [ ] Mutation-sensitive paths are documented with `@mutation-sensitive` comment.
- [ ] For critical financial logic: mutation kill rate verified ≥ target threshold.
- [ ] Full test suite passes locally before commit: `php artisan test`.
- [ ] No skip/pending tests without a linked tracking issue.

---

## 10. Maintenance Cadence

See `docs/testing/TEST_MAINTENANCE_CADENCE.md` for the regular audit and improvement schedule.

**Quick reference:**
- **Weekly:** Audit flaky tests; tag and track.
- **Bi-weekly:** Scan metrics (RefreshDatabase count, assertion counts, mutation scores).
- **Monthly:** Mutation audit on critical financial services; update targets.
- **Quarterly:** Full pyramid audit; plan Phase roadmap.

---

## 11. Anti-Patterns & Red Flags

🚩 **Red flags that indicate test debt (violations of target standards):**

The following patterns indicate non-compliance with this document's target standards and should be addressed during the phased improvement process (see `PROMPT_TEST_SUITE_MAINTENANCE.md` for remediation guidance):

- Tests that pass despite commented-out assertions (✗).
- `dd()` or `dump()` left in test code (✗).
- Tests with no assertions (`expectNotToPerformAssertions()` is ✗).
- `RefreshDatabase` in Unit tests (✗ — Phase 2 target).
- Direct HTTP client calls without `Http::fake()` (✗).
- Brittle string matching (`assertSee("Account #12345")`) when value is dynamic (✗ — Phase 1 target).
- Tests that only exercise model properties, not behavior (✗).
- Flaky tests running without a quarantine plan (✗ — Phase 0 target).
- Mutation score on critical services < 70% (🚩 — Phase 3 target).
- Cross-module model instantiation in unit tests (✗ — Phase 2 target).
- `makePartial()` used on service under test (✗ — Phase 1 target).

**Note:** The current codebase contains instances of all these patterns. The maintenance roadmap prioritizes their removal across phases 0–9. Track remediation progress via the master phase plan.

---

## Resources & Workflow

This document works in concert with the test suite improvement initiative:

- **Master Phase Plan:** `docs/development/WIP/TEST_SUITE_MASTER_PHASE_PLAN_6_5_TO_10.md` — Strategic roadmap for reaching 10/10.
- **Roadmap Outcomes:** `docs/testing/TESTING_ROADMAP_OUTCOMES.md` — Durable outcomes and sustainment guidance.
- **Contribution Guide:** `docs/testing/TESTING_CONTRIBUTION_GUIDE.md` — Required test authoring and validation rules.
- **Maintenance Cadence:** `docs/testing/TEST_MAINTENANCE_CADENCE.md` — Weekly to quarterly operating rhythm.
- **Maintenance Prompt:** `docs/development/Prompts/PROMPT_TEST_SUITE_MAINTENANCE.md` — LLM-driven audits and actionable phase tasks.
- **Infection/Mutation Testing:** https://infection.github.io — Verify tests catch real bugs.
- **Pest Testing Framework:** https://pestphp.com — Modern PHP testing with architecture rules.
- **Laravel Testing Guide:** https://laravel.com/docs/testing — Official Laravel documentation.
- **Testing Pyramid:** https://martinfowler.com/bliki/TestPyramid.html — Conceptual foundation.

### How to Use This Document in Daily Work

**When writing or reviewing tests:**
1. Check Section 9 (Test Contribution Checklist).
2. If you're writing a test in a critical domain, review Section 6 (Mutation Testing).
3. For module tests, verify Section 4 (Isolation Standards) is satisfied.

**When running the maintenance cadence (weekly/monthly):**
1. Execute `PROMPT_TEST_SUITE_MAINTENANCE.md` to audit current state.
2. Compare results against the targets in Section 1.5.
3. Track progress in `docs/development/WIP/TEST_SUITE_MASTER_PHASE_PLAN_6_5_TO_10.md`.

**When starting a new phase:**
1. Reference the phase work items in the master plan.
2. Use `PROMPT_TEST_SUITE_MAINTENANCE.md` to identify specific files/modules to address.
3. Commit changes atomically with clear messages tied to phase milestones.
