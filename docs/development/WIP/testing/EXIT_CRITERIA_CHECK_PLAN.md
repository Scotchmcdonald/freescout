# Phase 5 Exit Criteria Check Plan

Date: 2026-03-24
Owner: QA + Platform
Scope: Validate and close Phase 5 exit gate criteria with auditable evidence.

## Goal

Produce objective evidence for all three criteria:
1. 10 consecutive SLO-compliant green runs
2. Skip budget trending down over 14 days
3. Flake rate < 1.0% over 14 days

## Execution Sequence

1. Collect lane artifacts from each CI cycle
- Ensure test lane artifacts are present for guards, unit, feature, integration, architecture.
- Keep historical reports in reports/ for trend analysis.

2. Run governance checks
- php scripts/ci/check-skip-governance.php
- php scripts/ci/check-quarantine-registry.php

3. Run exit-gate verification
- php scripts/ci/check-phase-5-exit-gate.php --reports-dir=reports --output=reports/phase-5-exit-gate-latest.md

4. Generate compliance dashboard
- php scripts/ci/build-phase-5-dashboard.php --reports-dir=reports --output=public/dashboards/phase-5-compliance.html

5. Evaluate criterion status
- Confirm 10/10 consecutive green SLO runs from the generated matrix.
- Confirm skip trend is flat/down and within budget.
- Confirm flake rate is below 1.0% for the trailing 14-day period.

6. Record decision
- If all criteria pass, update phase-5-ci-speed-and-reliability.md checklist items to checked.
- Update reports/phase-5-exit-gate-final-validation.md with PASS verdict and final evidence summary.
- If any criterion fails, record blocker, owner, and next recheck date.

## Evidence To Capture

- reports/phase-5-exit-gate-latest.md
- public/dashboards/phase-5-compliance.html
- reports/skip-governance-latest.md
- reports/quarantine-registry-latest.md
- Relevant lane runtime and flake reports generated during the 10-run window

## Exit Decision Rules

- PASS only when all 3 criteria are satisfied simultaneously.
- Any SLO breach during the consecutive run window resets criterion 1 count.
- Missing artifact data yields NOT YET PASSED (never assume pass).
