# Phase 5: CI Speed And Reliability

Status: In Progress (2026-03-24 waves 1-4 implemented; exit gate not yet passed)
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

## Wave 4 Implementation Snapshot (Exit Gate Verification)

- Created exit gate verification system:
  - `scripts/ci/check-phase-5-exit-gate.php` - Automated exit gate checker (validates 10 green runs, skip budget, flake rate < 1%)
  - `scripts/ci/build-phase-5-dashboard.php` - Generate HTML compliance dashboard
- Created comprehensive exit gate documentation:
  - `docs/testing/PHASE_5_EXIT_GATE.md` - Full approval workflow, escalation procedures, monitoring guidelines
- Exit gate criteria:
  1. **10 consecutive green runs**: All PR lanes (Guards, Unit, Feature, Integration, Architecture) within SLO budgets
  2. **Skip budget trending down**: Total `markTestSkipped()` ≤12, 14-day trend decreasing/flat
  3. **Flake rate < 1%**: Measured from flake reports over 14-day trailing period

## Wave 4 Validation Evidence

- Exit gate script syntax validated and tested locally
- Exit gate documentation complete with approval workflow, escalation procedures, FAQ
- Dashboard generator validates with no syntax errors
- Exit gate tracking infrastructure ready for 10-run collection phase

## Current Exit Gate State

- Latest verification indicates gate is not yet passed because criterion 1 is unmet (10 consecutive green runs still at 0 of 10).
- Criteria 2 and 3 are currently passing in the latest available local evidence.

## Remaining Work (Phase 5)

### Immediate (Next 1-2 days):
1. Integrate exit gate checks into GitHub Actions (`test-lanes.yml`)
   - Add exit gate verification step after all lanes complete
   - Store gate status in artifacts for tracking
2. Wire up dashboard generation to CI
   - Run dashboard generator after each commit to main
   - Publish to public dashboard endpoint

### Medium-term (2-3 weeks):
1. Collect 10 consecutive green runs through normal CI activity
   - Each PR/push advances the green-run counter
   - Counter resets on any SLO breach
2. Monitor trends while collecting samples
   - Skip governance: trend should remain flat/decreasing
   - Flake rate: should stabilize below 1%
3. Population of quarantine registry as flakes are identified
   - Teams add entries via quarantine triage workflow
   - Each entry includes issue link, owner, expiry

### Final (Post-10-run gate):
1. Phase 5 approval and closure
   - Stakeholder sign-off on gate report
   - Merge Phase 5 feature branches
   - Tag release and document closure
2. Transition to ongoing monitoring:
   - Weekly SLO review (no WARN decisions)
   - Daily flake rate tracking (should stay < 1%)
   - Monthly skip budget audit

---

## Exit Gate Approval Checklist

- [ ] Exit gate report shows all 3 criteria PASSED
- [ ] 10 consecutive green runs visible in SLO compliance matrix
- [ ] Skip budget trending down confirmed (14-day analysis)
- [ ] Flake rate < 1.0% confirmed over 14 days
- [ ] No active SLO regressions in last 7 days
- [ ] Quarantine registry populated with identified flaky tests
- [ ] QA/Testing lead approval obtained
- [ ] Platform/DevOps lead approval obtained
- [ ] Engineering manager approval obtained
- [ ] Exit gate report committed to codebase

---

## Running Exit Gate Checks Locally

```bash
# Generate exit gate report
php scripts/ci/check-phase-5-exit-gate.php \
  --reports-dir=reports \
  --output=reports/phase-5-exit-gate-latest.md

# View the report
cat reports/phase-5-exit-gate-latest.md

# Generate compliance dashboard
php scripts/ci/build-phase-5-dashboard.php \
  --reports-dir=reports \
  --output=public/dashboards/phase-5-compliance.html
```

---

## Key Resources

- **Full Exit Gate Documentation**: [docs/testing/PHASE_5_EXIT_GATE.md](../../testing/PHASE_5_EXIT_GATE.md)
- **Skip Governance Policy**: [docs/testing/TESTING_CONTRIBUTION_GUIDE.md#skip-governance](../../TESTING_CONTRIBUTION_GUIDE.md)
- **Flaky Test Triage**: [docs/testing/FLAKY_TEST_TRIAGE.md](../../FLAKY_TEST_TRIAGE.md)
- **CI Scripts Reference**: [scripts/ci/README.md](../../../../scripts/ci/README.md)
- **Quick Start Commands**: [docs/testing/TESTING_QUICK_START.md](../../TESTING_QUICK_START.md)
