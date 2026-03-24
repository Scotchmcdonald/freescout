# Phase 5 Exit Gate: Acceptance Criteria & Approval Process

## Overview

The Phase 5 exit gate is the final validation checkpoint before Phase 5 CI speed/reliability guardrails are considered complete and production-ready. This document defines the three acceptance criteria, approval workflow, and escalation procedures.

---

## Phase 5 Exit Gate: Acceptance Criteria

### Criterion 1: 10 Consecutive Green Runs with SLO Compliance

**Definition:**
- All 5 PR lanes (Guards, Unit, Feature, Integration, Architecture) must complete within their respective SLO budgets on 10 consecutive CI runs.
- SLO Budgets:
  - Guards: ≤30 seconds
  - Unit: ≤30 seconds
  - Architecture: ≤30 seconds
  - Feature: ≤90 seconds
  - Integration: ≤90 seconds

**Measurement:**
- Tracked via `scripts/ci/check-test-lane-runtime-budgets.php` on every CI run
- Each run writes a report: `reports/lane-runtime-budget-{lane}-latest.md`
- Reports include: duration, rolling median, p95, decision (PASS/WARN)

**Pass Criteria:**
- Last 10 consecutive CI runs (across any PR/branch)
- Every run: PASS decision on all 5 lanes
- No WARN decisions (which indicate sustained regression detected)

**How to Track:**
```bash
# View the exit gate status (once reports are generated)
php scripts/ci/check-phase-5-exit-gate.php \
  --reports-dir=reports \
  --output=reports/phase-5-exit-gate-latest.md

# Check the report:
cat reports/phase-5-exit-gate-latest.md
```

**Typical Timeline:**
- First green run: When regression is resolved
- 10 consecutive runs: 1-2 days of normal CI activity (assuming no new regressions)

**Common Failure Patterns:**
- **CI variance spikes**: Transient network delays or resource contention may cause single-run blips; rolling window (median/p95) absorbs these, but 10-run gate ensures stability
- **Local dev environment**: Local runs may have different timing due to machine specs; CI measurements are canonical
- **Hardware scheduling**: Shared CI runners may show variance; use the p95 metric as the true indicator

---

### Criterion 2: Skip Budget Enforced and Trending Down

**Definition:**
- Total `markTestSkipped()` count must remain below baseline budgets
- Per-lane budgets:
  - Feature: ≤10 skips
  - Integration: ≤2 skips
  - Unit: 0 skips
  - Browser: 0 skips
  - Other: 0 skips
- **Trend direction:** Historical skip count over 14 days must show decreasing or flat (not increasing)

**Measurement:**
- Tracked via `scripts/ci/check-skip-governance.php` on every CI run
- Each run writes a report: `reports/skip-governance-latest.md`
- Reports include: full occurrence table, violations, trend analysis

**Pass Criteria:**
- Current skip count ≤ per-lane budgets (all lanes combined: ≤12)
- 14-day historical trend (from reports): decreasing or flat
- No new skips without metadata (owner, issue, expires)
- No expired skips remaining in codebase

**How to Track:**
```bash
# View skip governance status
cat reports/skip-governance-latest.md

# Check for violations
grep -A 10 "Violations" reports/skip-governance-latest.md
```

**Typical Timeline:**
- Baseline established: Phase 5 kickoff (12 allowlisted skips)
- Trending down: Requires 2-3 weeks of incremental removals
- Often blocks exit gate until team resolves long-standing test issues

**Escalation:**
- If skip count is stuck at baseline:
  1. Review root causes (e.g., long-running integrations, flaky assertions)
  2. Prioritize fixing vs. quarantining
  3. Update skip metadata with issue status

---

### Criterion 3: Flake Rate < 1% (Trailing 14 Days)

**Definition:**
- Measured from flake trend reports over the last 14 days
- Rate = (number of flaky test instances) / (total test runs) × 100
- Threshold: **< 1.0%**

**Measurement:**
- Tracked via `scripts/ci/generate-flake-report.php` on every lane run
- Each run writes a report: `reports/flake-report-{lane}-latest.md`
- Reports include: failure signatures, recurring signatures, quarantine status, suggestions

**Pass Criteria:**
- Historical flake rate over 14 days: < 1.0%
- Flaky tests identified and either:
  - Fixed (permanently resolved)
  - Quarantined (with issue link, owner, expiry, and reason)
  - Triaged (documented root cause, assigned to team)

**How to Track:**
```bash
# View flake analysis
cat reports/flake-report-phase5-local-latest.md

# Monitor recurring signatures
grep -A 20 "Top Recurring Failure Signatures" reports/flake-report-phase5-local-latest.md

# Check quarantine status
php scripts/ci/check-quarantine-registry.php --output=reports/quarantine-registry-latest.md
cat reports/quarantine-registry-latest.md
```

**Typical Timeline:**
- First reports: Immediately (Wave 3 completed)
- Baseline flake data: After 3-5 CI runs
- Trend visibility: After 1 week of data collection
- Quarantine population: 1-2 weeks as flakes are identified

**Common Patterns:**
- **Initial spike:** New tests often have early flakes; expect 5-8% initially
- **Regression introduces flakes:** Code changes may destabilize assertions; immediate investigation required
- **Quarantine backlog:** Teams may defer quarantine; escalate via Phase 5 sync

---

## Approval Workflow

### Step 1: Automated Gate Check

When all criteria are met, the exit gate report will show:

```
> ✅ **EXIT GATE: PASSED** — Phase 5 acceptance criteria met.
```

### Step 2: Evidence Review

Gate pass must be accompanied by:
1. ✅ SLO compliance matrix (10 green runs visible in report)
2. ✅ Skip trend analysis (14-day data showing downward/flat trend)
3. ✅ Flake rate calculation (< 1% confirmed)
4. ✅ No active P0 regressions (CI is stable)

Generate the final report:
```bash
php scripts/ci/check-phase-5-exit-gate.php \
  --reports-dir=reports \
  --output=reports/phase-5-exit-gate-final-approval.md
```

Commit this report to the codebase:
```bash
git add reports/phase-5-exit-gate-final-approval.md
git commit -m "docs(phase-5): exit gate approval - 10 green runs verified"
```

### Step 3: Stakeholder Sign-Off

Gate approval requires agreement from:
- **QA/Testing Lead**: Confirms flake triage and quarantine policy compliance
- **Platform/DevOps Lead**: Confirms SLO budgets align with infrastructure capacity
- **Engineering Manager**: Confirms impact on developer experience (skip budget user)

Sign-off can be done via:
- Git code review (PR merge approval)
- Phase 5 sync meeting (recorded in meeting notes)
- Email thread with stakeholders (cc'd into PHASE_5_EXIT_GATE.md file)

### Step 4: Phase 5 Closure

Once approval is obtained:

1. **Merge Phase 5 branches:**
   ```bash
   git merge phase-5-feature-branch
   git merge origin/develop  # Ensure main has all guardrails
   ```

2. **Tag release:**
   ```bash
   git tag -a "phase-5-complete" -m "Phase 5 CI speed/reliability complete"
   ```

3. **Document closure:**
   - Update `docs/development/WIP/testing/phase-5-ci-speed-and-reliability.md` with "Status: Complete"
   - Clean up `docs/development/WIP/testing/` folder (remove kickoff and evidence files to archive)
   - Create `docs/testing/PHASE_5_CLOSURE.md` with final metrics and lessons learned

4. **Archive evidence:**
   ```bash
   mkdir -p reports/phase-5-archive
   cp reports/phase-5-exit-gate-final-approval.md reports/phase-5-archive/
   cp reports/skip-governance-latest.md reports/phase-5-archive/skip-governance-final.md
   cp reports/flake-report-*-latest.md reports/phase-5-archive/
   ```

---

## Escalation Procedures

### Scenario A: Exit Gate Blocked on Criterion 1 (SLO Breach)

**Symptoms:**
- Lane showing WARN decision
- Duration consistently exceeds budget
- Rolling median/p95 indicate sustained regression

**Diagnosis:**
```bash
# Check which lane is breaching
grep "Decision:" reports/lane-runtime-budget-*.md | grep WARN

# View the problematic lane
cat reports/lane-runtime-budget-{problem-lane}-latest.md
```

**Resolution Path (in priority order):**
1. **Identify recent change:** Check `git log --oneline -20` for test-code or config changes
2. **Profile the lane:** Run locally with timing:
   ```bash
   time php artisan test tests/{Lane} --parallel --processes=10
   ```
3. **Investigate test-level causes:**
   - Added slow tests?
   - Database-heavy tests added?
   - Network I/O introduced?
4. **Escalate to team:** If no obvious cause, raise in #platform or Phase 5 sync
5. **Temporary skip:** If fix requires redesign, quarantine slow tests temporarily
6. **Gradual recovery:** Once regression is identified/fixed, next 10 runs will reset gate

### Scenario B: Exit Gate Blocked on Criterion 2 (Skip Budget)

**Symptoms:**
- Skip count > per-lane budget
- Multiple skips without metadata
- Skips with expired expiry dates

**Diagnosis:**
```bash
# View violations
grep -A 20 "Violations:" reports/skip-governance-latest.md
```

**Resolution Path:**
1. **Identify new/expired skips:** Review each violation in report
2. **For each skip without metadata:**
   - Find the skip in codebase: `grep -r "markTestSkipped" tests/ | grep -v "owner"`
   - Add owner, issue, expires metadata
   - Example:
     ```php
     // @skip owner=qa, issue=github-123, expires=2026-04-30
     $this->markTestSkipped('Waiting for API refactor (Issue #123)');
     ```
3. **For expired skips:**
   - Verify fix status
   - Extend expiry if still needed: `expires=2026-05-31`
   - Remove skip if issue is resolved
4. **Trend analysis:** If skip count isn't decreasing over 2 weeks:
   - Escalate to dev team: "Skipped tests should be decreasing"
   - Prioritize fixes for highest-skip lanes (Feature, Integration)

### Scenario C: Exit Gate Blocked on Criterion 3 (Flake Rate)

**Symptoms:**
- Flake rate > 1.0%
- Multiple recurring failure signatures
- New flaks appearing daily

**Diagnosis:**
```bash
# View flake analysis
cat reports/flake-report-phase5-local-latest.md

# Check quarantine status
cat reports/quarantine-registry-latest.md
```

**Resolution Path:**
1. **Identify top flaky tests:** Review "Top Recurring Failure Signatures" table
2. **For each recurring signature:**
   - Check likely test file (mapped in report)
   - Investigate root cause (timing, environment, resource contention)
   - Decide: Fix vs. Quarantine
3. **If fixing:**
   - Update test to improve stability (reduce flakiness)
   - Remove from quarantine after 3 consecutive green runs
4. **If quarantining:**
   - Add entry to `tests/quarantine/flaky-quarantine-registry.json`
   - Include: test_file, reason, owner, issue, expires
   - Tag test with `@group flaky-triage` or `@flaky`
5. **Trend tracking:** Quarantine data should stabilize at < 5 entries
   - If > 5 entries, prioritize permanent fixes in Phase 6

---

## Monitoring & Alerting (Post-Exit-Gate)

Once Phase 5 exit gate is passed, continue monitoring:

**Weekly SLO Review:**
```bash
# Monitor SLO compliance
grep "Decision:" reports/lane-runtime-budget-*-latest.md

# Alert if any WARN appears
# Action: Investigate and resolve within 24 hours
```

**Skip Governance Audit:**
```bash
# Any new skips above budget should be questioned
# Trend should remain flat/decreasing
grep "violations" reports/skip-governance-latest.md
```

**Flake Rate Trend:**
```bash
# Flake rate should remain < 1%
# New quarantine entries indicate instability
grep "Flake Pressure:" reports/flake-report-*-latest.md
```

---

## Rollback Criteria

If Phase 5 guardrails cause operational issues post-approval:

1. **Immediate rollback** may be warranted if:
   - SLO budgets are impossible to meet (i.e., hardware insufficient)
   - False positives > 20% (gate frequently blocked by flakes unrelated to PRs)
   - Skip governance prevents critical tests from running

2. **Rollback procedure:**
   ```bash
   git revert <phase-5-merge-commit>
   git push origin main
   # Document reasons in ROLLBACK_REASONS.md
   ```

3. **Re-planning phase:** Schedule Phase 5 retrospective to adjust budgets/policies

---

## Success Metrics

Once Phase 5 exit gate is passed and maintained:

- ✅ **Throughput:** PR feedback time reduced to < 5 minutes (vs. 8-10 before)
- ✅ **Reliability:** False-flake blocks reduced by 80% (via quarantine)
- ✅ **Developer Experience:** "Skip budget under control" feedback
- ✅ **Operational:** No SLO regressions for 30+ days post-exit

---

## FAQ

**Q: Can we merge Phase 5 before all 10 green runs?**
A: No. The 10-run gate ensures stability across infrastructure variance. Merging early risks reverting later if regressions appear.

**Q: What if CI is down during the 10 green runs?**
A: CI outages don't reset the counter. Once CI is back, gate counting resumes. If there's a regression during outage, counter resets.

**Q: Can we adjust the SLO budgets after exit gate passes?**
A: Not without approval. Re-negotiate budgets → re-run gate with new thresholds. Current budgets are locked until Phase 6.

**Q: What if a skip/flake is fixed before expiry?**
A: Remove the skip entirely or quarantine entry immediately. Expired/invalid entries violate governance.

**Q: Who owns flaky test quarantine long-term?**
A: QA/Testing team (with developer support for root-cause investigation). Review quarantine entries monthly for fixes.

---

## Related Documentation

- [Phase 5 Planning](./PHASE_5_SUMMARY.md)
- [Skip Governance Policy](./TESTING_CONTRIBUTION_GUIDE.md#skip-governance)
- [Flaky Test Triage Runbook](./FLAKY_TEST_TRIAGE.md)
- [CI Scripts Reference](../../scripts/ci/README.md)
- [Testing Quick Start](./TESTING_QUICK_START.md)
