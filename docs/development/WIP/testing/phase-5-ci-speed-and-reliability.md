# Phase 5: CI Speed And Reliability

Status: In Progress (2026-03-24 kickoff)
Duration: 4 to 7 days
Goal: Keep confidence high while maintaining fast feedback in parallel lanes.

## Scope

- Enforce lane runtime budgets and trend visibility.
- Reduce flakes and skipped-test drift.
- Improve local developer feedback loops.

## Implementation Tasks

1. Runtime budgets and alerts
- Define lane SLOs and fail on sustained regression:
  - Unit <= 30s
  - Feature <= 90s
  - Integration <= 90s
  - Architecture/Guards <= 30s
- Publish runtime trend artifact per run.

2. Flake management
- Add flake tracker report from recent runs.
- Quarantine policy:
  - require issue link, expiry date, and owner
  - auto-fail if quarantine expires

3. Skip governance
- Block new markTestSkipped usage unless linked to issue + expiry.
- Report skip count per lane and fail above budget.

4. Local DX commands
- Provide one-command local lane equivalents for Unit, Feature, Integration, and Guard checks.
- Keep command docs in docs/testing quick-start references.

## Acceptance Criteria

- Lane SLO checks active in CI.
- Flake rate < 1 percent over trailing 14 days.
- Skip budget enforced and trending down.

## Risks

- Risk: strict budgets may intermittently fail due to CI host variance.
- Mitigation: use rolling median + p95 windows instead of single-run spikes.

## Exit Gate

- 10 consecutive green runs with SLO compliance on all PR lanes.

## Kickoff Progress

- Phase 5 kickoff started after Phase 4 closeout validation.
- Initial execution plan prepared for runtime baseline capture, skip-governance checks, and flake trend reporting.
- Evidence log created at docs/development/WIP/testing/phase-5-kickoff-evidence.md.

## Immediate Next Actions

1. Capture lane runtime baselines from latest CI-style local runs:
- `php artisan test tests/Unit --parallel --processes=10`
- `php artisan test tests/Feature --parallel --processes=10`
- `php artisan test tests/Integration --parallel --processes=10`
- `bash scripts/ci/check-architecture-compliance.sh`

2. Add lightweight runtime budget checker script:
- parse latest report logs and compare against SLOs.
- fail with actionable output only on sustained regression windows.

3. Add skip-governance report and threshold guard:
- enumerate `markTestSkipped` usage and metadata quality.
- enforce issue+owner+expiry policy.
