# Test Maintenance Cadence

This document defines the ongoing operating cadence for maintaining test-suite quality across reliability, signal quality, pyramid balance, isolation, coverage, mutation, and developer experience.

## Goals

- keep reliability stable with repeatable green runs
- prevent regression to low-signal or brittle tests
- maintain strict module isolation in unit scope
- grow and protect critical-service depth
- keep contributor workflow fast and predictable

## Cadence Overview

### Weekly

1. Reliability review
- inspect recent reports/test-results-*.log
- identify repeated failures and trend direction
- ensure flaky tests are tracked and owned

2. Failure triage
- prioritize top recurring failures by frequency and impact
- fix fast regressions immediately

3. Guard health check
- run architecture and isolation guard tests if recent refactors touched those areas

### Bi-Weekly

1. Signal quality scan
- count makePartial usage
- count assertSee and assertSeeText usage
- identify no-op tests or framework-internals tests

2. Pyramid and isolation scan
- review test distribution by layer
- review unit-scope RefreshDatabase usage
- review allowlist size and expiry dates

3. Critical-service delta check
- confirm changed critical services received test additions

### Monthly

1. Full six-dimension scorecard refresh
- reliability
- signal quality
- pyramid balance
- module isolation
- coverage and mutation
- developer experience

2. Coverage and mutation review
- run or inspect mutation for critical-service slices
- confirm thresholds remain healthy

3. Workflow improvement actions
- update docs and phase plans if process drift is found

### Quarterly

1. Strategic pyramid audit
- rebalance layers where drift occurred

2. Architecture hardening review
- remove legacy exceptions and shrink guard allowlists

3. Roadmap update
- refresh priorities in docs/testing/TESTING_ROADMAP_OUTCOMES.md

## Standard Runbook

For maintenance cycles, use this sequence:

1. Inspect current reports and metrics.
2. Select the highest-leverage maintenance action from docs/testing/TESTING_ROADMAP_OUTCOMES.md.
3. Execute a focused remediation batch.
4. Validate with targeted test runs.
5. Record outcomes and residual risk.

## Suggested Command Set

Use non-destructive commands and prefer existing logs:

```bash
php artisan test
tail -n 40 reports/test-results-latest.log
grep -A 8 "FAILED" reports/test-results-latest.log
grep -RIn "makePartial()|assertSee|assertSeeText" tests Modules --include='*.php'
grep -RIl "RefreshDatabase" tests/Unit Modules/*/Tests/Unit --include='*.php'
php artisan test tests/Unit/ModuleUnitIsolationGuardTest.php
php artisan test tests/Architecture/
```

## Autonomous Agent Policy

Given the current terminal auto-approve policy, autonomous work is encouraged for:
- metric scans
- targeted test runs
- focused test/doc refactors
- guard validation and report interpretation

Agent must pause for confirmation only when:
- destructive or blocked commands would be required
- a major architecture change is needed
- product behavior is unclear

## Reporting Template

After each maintenance cycle, publish:

1. Summary
- phase executed
- key fixes landed

2. Validation
- tests run
- pass or fail summary

3. Metrics delta
- before and after counts for target metrics

4. Risk and follow-up
- unresolved blockers
- next best phase

## Ownership and SLA

- QA Lead: monthly scorecard, quarterly roadmap
- Module Owners: weekly failure and quality remediation in owned modules
- Platform or DevOps: CI lane performance, reporting, and artifact publication

Target SLAs:
- recurring failure triage started within 1 business day
- flaky test ownership assigned within 2 business days
- critical-service regression fixed or quarantined within 3 business days

Last updated: 2026-03-19
