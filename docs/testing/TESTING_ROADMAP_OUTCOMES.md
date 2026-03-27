# Testing Roadmap Outcomes and Sustainment

## Why this exists
This is the durable replacement for the temporary phase execution pack. It keeps only high-impact outcomes and ongoing operating rules.

## Outcomes delivered (Phases 0-10)
- Architecture and isolation guardrails are active and enforced in CI.
- Unit test hygiene is protected (no unit `RefreshDatabase` regressions).
- CI lanes are explicit: guards, unit, feature, architecture, integration.
- Test documentation now includes quick-start commands, guard failure triage, and flaky-test handling.
- Workflow safety checks fail fast if DB targets are not clearly test/local.
- **100% type coverage**: all method return types and parameters are declared across `app/` and `Modules/`; enforced by `scripts/ci/check-type-coverage.php` with the quality gate at 100.0.
- **15 architecture test files** (58+ rules) covering layer separation, module boundaries, naming conventions, action isolation, strict-types per-layer enforcement, unit isolation guards, and cross-module controller import prevention.
- **641 boundary hits** across 574 test files covering authorization, validation, rate-limiting, throttle, and cross-tenant access control.

## Non-negotiable standards
- No live external HTTP in tests.
- No cross-module coupling in unit scope.
- No new unit `RefreshDatabase` usage.
- Keep assertions behavior-focused and deterministic.
- All PHP files in `app/` and `Modules/` must declare `strict_types=1`.
- All methods and parameters must carry explicit type declarations (100% type coverage gate).
- Every new endpoint must have at least one authorization test and one validation boundary test.

Primary references:
- `tests/testing_standards.md`
- `docs/testing/TESTING_CONTRIBUTION_GUIDE.md`
- `docs/testing/CI_GUARD_STAGES.md`

## Sustainment cadence
Weekly:
- Review new failures and flaky candidates.
- Fix or quarantine flaky tests immediately.

Bi-weekly:
- Scan drift metrics:
  - `makePartial()`
  - `assertSee/assertSeeText`
  - unit-scope `RefreshDatabase`

Monthly:
- Review six-dimension scorecard.
- Review coverage and mutation health for changed critical services.

Quarterly:
- Re-audit the pyramid and retire obsolete tests/guard exceptions.
- Propose next improvement tranche only if trend data supports it.

## Canonical maintenance commands
```bash
php artisan test --parallel --processes=10
bash scripts/ci/check-architecture-compliance.sh
php scripts/ci/check-type-coverage.php
php scripts/ci/check-testing-quality-gate.php
grep -RIn "makePartial()" tests Modules --include='*.php'
grep -RInE "assertSee\(|assertSeeText\(" tests Modules --include='*.php'
grep -RIl "RefreshDatabase" tests/Unit Modules/*/Tests/Unit --include='*.php'
```

## Escalate when
- Reliability drops below the 3-run proof threshold.
- Guard failures indicate structural architecture drift.
- Mutation or critical-path coverage shows sustained decline.
- Type coverage drops below 100% (quality gate failure).
- Boundary hit count drops below 50 (quality gate failure).

Last updated: 2026-03-27
