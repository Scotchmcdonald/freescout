# Phase 5: CI Speed And Reliability

Status: In Progress (2026-03-24 waves 1-3 implemented)
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

## Wave 1 Implementation Snapshot

- Added runtime budget checker:
  - `scripts/ci/check-test-lane-runtime-budgets.php`
- Added skip governance guard:
  - `scripts/ci/check-skip-governance.php`
- Added flaky trend report generator:
  - `scripts/ci/generate-flake-report.php`
- Wired new scripts into CI lane workflow:
  - `.github/workflows/test-lanes.yml`

## Wave 1 Validation Evidence

- Syntax validation passed for all new scripts.
- Skip governance run passed with current baseline:
  - `reports/skip-governance-latest.md`
- Runtime budget checker exercised for pass and warn scenarios:
  - `reports/lane-runtime-budget-guards-latest.md`
  - `reports/lane-runtime-budget-unit-latest.md`
- Flake trend report generated from recent local logs:
  - `reports/flake-report-phase5-local-latest.md`

## Remaining Work (Phase 5)

- Collect CI lane runtime samples over multiple consecutive runs to activate sustained-regression decisions using full rolling windows.

## Wave 3 Implementation Snapshot

- Enhanced flaky trend parser and normalization:
  - `scripts/ci/generate-flake-report.php`
- Added recurring signature detection based on distinct log count.
- Added likely test-file mapping from parsed `Tests\\...` class names.
- Added quarantine-aware action hints by cross-checking active quarantine registry entries.
- Updated CI lane invocations to pass quarantine registry path.

## Wave 3 Validation Evidence

- Enhanced flake report generated successfully with quarantine-aware output:
  - `reports/flake-report-phase5-wave3-local-latest.md`

## Wave 2 Implementation Snapshot

- Added quarantine registry governance guard:
  - `scripts/ci/check-quarantine-registry.php`
- Added quarantine registry baseline file:
  - `tests/quarantine/flaky-quarantine-registry.json`
- Wired quarantine governance into guards lane in CI:
  - `.github/workflows/test-lanes.yml`

## Wave 2 Validation Evidence

- Quarantine registry guard run passed with baseline registry:
  - `reports/quarantine-registry-latest.md`
- No flaky-triage tagged tests currently require active registry entries.
