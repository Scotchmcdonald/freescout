# Phase 1: Baseline And Guardrails

Status: Completed (2026-03-23)
Duration: 3 to 5 days
Goal: Lock in measurable baselines and prevent new debt while migration work starts.

## Scope

- Establish objective baseline metrics for isolation, assertion quality, and runtime.
- Add or tighten guard tests so no new violations are introduced.
- Define temporary allowlists with hard expiry where unavoidable.

## Implementation Tasks

1. Baseline metrics job
- Add a script that reports:
  - Unit files by base class (PureUnitTestCase vs UnitTestCase).
  - Feature write-endpoint tests with status-only assertions.
  - Distribution across Unit/Feature/Integration/Browser.
  - Median and p95 lane runtime from latest CI logs.
- Write output to reports/testing-baseline-YYYY-MM-DD.md.

2. Guardrail tightening
- Extend existing guard tests to fail on:
  - New Unit tests extending framework booting classes.
  - New Feature tests for write endpoints lacking any side-effect assertion.
- Keep existing allowlists but require:
  - expiry date
  - issue link
  - owner

3. Contribution policy updates
- Update docs/testing contribution docs with mandatory quality rules:
  - Unit isolation rule
  - Feature assertion depth rule
  - skip usage policy

## Acceptance Criteria

- Baseline report committed and linked from docs/testing.
- Guard tests fail for new non-compliant tests.
- All allowlist entries have expiry and owner metadata.

## Risks

- Risk: immediate CI friction due to stricter guards.
- Mitigation: use temporary allowlists with 14-day expiry and named owners.

## Exit Gate

- Phase completes when baseline data is reproducible and no unowned allowlist exists.

## Completion Notes (2026-03-23)

Delivered:
- Baseline generator script: scripts/testing_phase1_baseline.php
- Baseline artifact: reports/testing-baseline-2026-03-23.md
- Docs index link added: docs/testing/README.md
- Guardrails added:
  - tests/Unit/UnitFrameworkBootingGuardTest.php
  - tests/Unit/FeatureWriteAssertionDepthGuardTest.php
- Existing allowlist metadata enforcement tightened:
  - tests/Unit/RefreshDatabaseUsageGuardTest.php
  - tests/Unit/ModuleUnitIsolationGuardTest.php
- Contribution policy updated:
  - docs/testing/TESTING_CONTRIBUTION_GUIDE.md

Validation notes:
- PHP lint is clean for all new/updated PHP files.
- Full guard test execution is currently blocked in this container by project-level destructive-command environment safeguards and database permission constraints for parallel test setup.
- CI execution in test lanes remains the source of truth for runtime enforcement until local environment constraints are resolved.
