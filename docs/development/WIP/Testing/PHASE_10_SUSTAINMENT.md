# Phase 10: Sustainment

## Objective

Keep the suite healthy after the major remediation phases are complete.

## Ongoing Targets

- reliability remains above the 3-run proof threshold
- architecture and isolation regressions are caught quickly
- critical-service mutation thresholds stay healthy
- test-suite metrics are reviewed on a routine cadence

## Operating Model

Weekly:
- review newly failing tests
- tag or fix flaky behavior immediately

Bi-weekly:
- scan counts for `makePartial()`, `assertSee`, and unit `RefreshDatabase`
- inspect critical service test drift

Monthly:
- refresh the six-dimension scorecard
- inspect coverage and mutation health on modified critical services

Quarterly:
- re-audit the pyramid
- retire obsolete tests and guard exceptions
- decide the next improvement tranche

## Autonomous Execution Guidance

Autonomy level: high for audits, medium for remediation.

The agent should autonomously:
- run scans
- summarize trends
- prepare targeted maintenance PRs

The agent should pause when:
- a trend implies roadmap reprioritization or a major architecture shift

## Safe Command Patterns

```bash
php artisan test
grep -RIn "makePartial()\|assertSee\|assertSeeText" tests Modules --include='*.php'
grep -RIl "RefreshDatabase" tests/Unit Modules/*/Tests/Unit --include='*.php'
git diff --stat
```

## Effective LLM Prompt

```text
You are executing Phase 10 of the testing roadmap: Sustainment.

Work autonomously where possible. Run the recurring audit checks, summarize drift, and implement small corrective changes that keep the suite aligned with the standards. Treat this as ongoing quality maintenance, not a one-off cleanup.

Escalate only when the data suggests a broader roadmap or architecture change is needed.
```
