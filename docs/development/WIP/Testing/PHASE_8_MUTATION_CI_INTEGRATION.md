# Phase 8: Mutation CI Integration

## Objective

Turn mutation testing from an aspirational target into an enforced engineering signal.

## Current Baseline

- no mutation report artifact is present in `reports/`
- no mutation-related CI workflow step is present
- critical-service unit coverage is incomplete, so mutation rollout must start with a narrow subset

## Exit Criteria

- mutation is runnable in CI on a focused critical-service slice
- HTML or equivalent mutation artifacts are published
- minimum MSI thresholds are enforced for the selected slice

## Implementation Plan

1. Choose a narrow initial mutation slice, ideally QuoteService and BillingAnalysisService.
2. Ensure line coverage exists first.
3. Add a CI job that runs mutation on that slice only.
4. Publish artifacts and fail below threshold.
5. Expand the slice later once runtime is acceptable.

## Autonomous Execution Guidance

Autonomy level: medium.

The agent should proceed autonomously when:
- wiring mutation for a focused subset
- updating CI and report publishing
- tuning thresholds already documented in standards

The agent should pause only if:
- mutation runtime is too expensive and a broader CI strategy decision is needed

## Safe Command Patterns

```bash
php artisan test --filter QuoteService
vendor/bin/infection --test-framework=pest --coverage=reports/coverage --html=reports/mutation-report.html
tail -n 40 reports/test-results-latest.log
```

## Effective LLM Prompt

```text
You are executing Phase 8 of the testing roadmap: Mutation CI Integration.

Work autonomously where possible. Start small: wire mutation testing for a focused critical-service subset, publish artifacts, and enforce thresholds that match the testing standards. Do not attempt full-suite mutation unless the targeted slice is already healthy and fast enough.

Report runtime, thresholds, artifacts produced, and the next expansion step.
```
