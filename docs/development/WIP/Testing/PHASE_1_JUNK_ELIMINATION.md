# Phase 1: Junk Elimination

## Objective

Remove low-signal tests and brittle assertions so the suite measures domain behavior instead of framework wiring.

## Current Baseline

**PHASE 1 COMPLETE — 2026-03-19**

| Metric | Baseline | Final | Target |
|--------|---------|-------|--------|
| relation assertions | 0 | 0 | 0 ✅ |
| `assertSee`/`assertSeeText` | 118 | 118 | ≤150 ✅ |
| `makePartial()` | 13 | **10** | ≤10 ✅ |
| fillable/casts/appends refs | 229 | 229 | tracked |
| files with no assert/expect | 4 (all false positives) | 0 genuine | — ✅ |

**Changes made:**
- `tests/Unit/Jobs/QueueIsolationTest.php`: replaced `Mockery::mock(Model::class)->makePartial()` (×2) with `new Product` — model was only a constructor arg to a fake-queued job; no mocking needed
- `tests/Unit/Listeners/ListenersComprehensiveTest.php`: replaced `Mockery::mock(Customer::class)->makePartial()` + `getMainEmail()→null` stub with `Customer::factory()->withoutEmail()->create()` — real factory produces the same null-email outcome
- "No-op test" candidates were false positives: `KernelTest` uses `expectsOutputToContain`, `ClientCreditServicePestTest` uses `->throws()`, `Flaky.php` is an attribute class
- Commit: `1c36e0736` — `refactor(tests): replace makePartial Model/Customer mocks with real instances (13→10)`

## Exit Criteria

- relation assertions remain at 0
- `assertSee` and `assertSeeText` stay at or below 150 and trend downward
- `makePartial()` is reduced to 10 or fewer
- obvious no-op tests are deleted or rewritten to assert behavior

## Implementation Plan

1. Remove or rewrite no-op and framework-internals tests.
2. Replace `makePartial()` usage where the service under test is being over-mocked.
3. Convert copy-based UI assertions into state, structure, authorization, redirect, event, or response assertions.
4. Re-scan counts after each cleanup batch.

## High-Value Targets

- `Modules/Payment/Tests/Feature/PaymentProcessingPestTest.php`
- `Modules/CaseManager/Tests/Unit/DataTransferObjects/DecisionContextTest.php`
- `tests/Unit/Services/ImapServiceTest.php`
- `tests/Unit/ImapServiceAdvancedTest.php`
- `Modules/AssetManagement/Tests/Feature/Admin/AssetAdminPestTest.php`
- `Modules/ContractManager/Tests/Feature/Admin/BillingAdminPestTest.php`

## Autonomous Execution Guidance

Autonomy level: high.

The agent should proceed autonomously when:
- deleting obviously redundant assertions
- replacing `makePartial()` with better fakes or contracts
- narrowing `assertSee` into stronger behavioral assertions
- rerunning only the touched test files

The agent should pause only if:
- removing a test leaves coverage intent unclear
- the test is acting as undocumented product specification

## Safe Command Patterns

```bash
grep -RIn "makePartial()" tests Modules --include='*.php'
grep -RIn "assertSee\|assertSeeText" tests Modules --include='*.php'
php artisan test Modules/Payment/Tests/Feature/PaymentProcessingPestTest.php
php artisan test tests/Unit/Services/ImapServiceTest.php
```

## Effective LLM Prompt

```text
You are executing Phase 1 of the testing roadmap: Junk Elimination.

Work autonomously where possible. Inspect the current offenders, rewrite or remove low-signal tests, reduce makePartial usage, and replace brittle assertSee assertions with behavior-focused checks. Run only the relevant test files after each edit, then summarize new metric counts.

Success criteria:
- no new framework-wiring tests
- makePartial count reduced
- brittle UI-copy assertions reduced or strengthened
- touched tests pass

Keep going without asking unless a test appears to encode unclear product behavior.
```
