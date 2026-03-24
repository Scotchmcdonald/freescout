# Phase 5: 10-Run Collection Runbook

**Purpose:** Guide teams through the 2-3 week exit gate data collection phase
**Audience:** DevOps, QA, Engineering Leads, Developers
**Start Date:** 2026-03-24 (post-Wave 4 validation)
**Expected Duration:** 2-3 weeks

---

## Overview

The Phase 5 exit gate requires **10 consecutive green runs** with SLO compliance on all PR lanes. This runbook explains what "consecutive green runs" means, how to track progress, and how to handle blockers.

### What is a "Green Run"?

A green run is a complete CI execution where:
1. ✅ All 5 test lanes complete successfully
2. ✅ All lanes finish within their SLO budgets:
   - Guards ≤30 seconds
   - Unit ≤30 seconds
   - Feature ≤90 seconds
   - Integration ≤90 seconds
   - Architecture ≤30 seconds

**Counter behavior:**
- ✅ Green → Counter increments (+1 toward 10 runs)
- 🔴 Red (SLO breach) → Counter resets to 0
- ⓘ Infra failure (CI outage) → Counter paused, resumes when CI recovers

---

## How to Track Progress

### Method 1: Check Exit Gate Report
```bash
# After each CI run, check the report
cat reports/phase-5-exit-gate-latest.md

# Look for the gate status
grep "EXIT GATE:" reports/phase-5-exit-gate-latest.md

# View SLO compliance matrix to see green/red runs
grep -A 15 "SLO Compliance Matrix" reports/phase-5-exit-gate-latest.md
```

### Method 2: View Compliance Dashboard
- **URL:** `/dashboards/phase-5-compliance.html`
- **Refresh:** Auto-refreshes every 5 minutes
- **Shows:** Visual progress bar toward 10 runs, criteria status cards, SLO matrix

### Method 3: Automated Notifications (Optional)
```bash
# Check for WARN decisions (indicates SLO breach)
grep "Decision: WARN" reports/lane-runtime-budget-*-latest.md

# If any WARN found, investigate that lane
cat reports/lane-runtime-budget-{red-lane}-latest.md
```

---

## Weekly Tracking Template

Use this template to track progress toward the 10-run gate:

```markdown
## Week 1 (2026-03-24 - 2026-03-30)

**Starting Count:** 0/10
**Target:** 4-5 green runs

| Date | Run | Lane Status | Gate Status | Notes |
|------|-----|-------------|-------------|-------|
| 3/24 | 1   | Guards ✅ Unit ❓ Feature ❓ Integ ❓ Arch ❓ | 🟢 (1/10) | Initial validation |
| 3/25 | 2   | All reported, check each | | Monitor |
| 3/26 | 3   | | | |

**Issues This Week:**
- [List any SLO breaches, infrastructure problems, test failures]

**Action Items:**
- [ ] If any SLO breach, investigate root cause
- [ ] Review flake reports for new quarantine candidates
- [ ] Monitor skip count (should stay ≤12)

**Status:** On track / At risk / Blocked
```

---

## Scenario: SLO Breach (Counter Resets)

### Symptom
You see `Decision: WARN` in a lane report or the compliance dashboard shows a red (🔴) run.

### Investigation Steps

**Step 1: Identify the breach**
```bash
# Which lane breached?
grep "Decision: WARN" reports/lane-runtime-budget-*-latest.md

# View the problematic lane's report
cat reports/lane-runtime-budget-{RED-LANE}-latest.md

# Look at: Duration, Rolling Median, P95, Decision Reason
```

**Step 2: Check recent code changes
```bash
# See what changed since last green run
git log --oneline -15 -- tests/{RED-LANE}/ app/

# Any new tests added?
git diff HEAD~3 HEAD -- tests/{RED-LANE}/
```

**Step 3: Profile locally**
```bash
# Run the lane locally with timing
time php artisan test tests/{Red} --parallel --processes=10

# If slow, identify which tests are slowest
php artisan test tests/{Red} --parallel --processes=10 --verbose | grep "PASSED\|FAILED" | sort -k3 -r | head -10
```

**Step 4: Root cause options**
- **New slow test added:** Profile the test, optimize or move to slower lane
- **Database regression:** Check migrations, setUp methods
- **Network I/O added:** Reduce external API calls in unit tests
- **Flaky test retries:** Might inflate timing; check for instability

**Step 5: Fix & Verify**
```bash
# After fix, run locally again
time php artisan test tests/{Red} --parallel --processes=10

# Once passing, push and watch next CI run
git commit -am "perf(tests): optimize {Red} lane timing"
git push origin {branch}

# Monitor next CI run to see if counter resets or continues
```

**Step 6: Update tracker**
```markdown
## Blocker: SLO Breach

**Lane:** {RED-LANE}
**Duration:** XXs (budget: XXs, exceeded by XXs)
**Cause:** [Describe root cause]
**Fix:** [Describe what was changed]
**Status:** ✅ Fixed / 🔄 In Progress / ❌ Escalated

**Lessons Learned:**
- [Reflect on how to prevent similar regressions]
```

---

## Scenario: Flake Detected (New Quarantine Entry)

### Symptom
Flake report shows new recurring failure with `Quarantined: no` and suggests test file.

### Triage Process

**Step 1: Identify the flake**
```bash
# Review flake report
cat reports/flake-report-phase5-local-latest.md

# Look for Top Recurring Failure Signatures and "Likely Test Files"
grep -A 20 "Top Recurring" reports/flake-report-phase5-local-latest.md
```

**Step 2: Reproduce locally**
```bash
# Run the suspected test multiple times
for i in {1..5}; do
  php artisan test tests/Unit/YourTestFile.php::testName --seed=$RANDOM
done

# Does it fail occasionally? If so, it's flaky
```

**Step 3: Analyze failure pattern**
- **Timing-based:** Add delay assertions, reduce race conditions
- **Environmental:** Set up fixtures more consistently
- **Assertion-heavy:** Reduce assertion count, focus on key checks
- **Mock/Spy issues:** Verify mocks reset between runs

**Step 4: Decision: Fix vs. Quarantine**

**Option A: Fix Immediately (Recommended)**
```bash
# If root cause is obvious, fix it
# Example: Add `@depends` for setUp sequencing
# Example: Use static fixtures instead of random data
# Example: Reduce timeout assertions

# After fix, run test 10+ times to confirm stability
# Then commit and push
```

**Option B: Quarantine Temporarily**
```bash
# If root cause unclear, quarantine to track later
# Add to tests/quarantine/flaky-quarantine-registry.json

# Entry template:
{
  "id": "test-file-testname",
  "test_file": "tests/Unit/YourTestFile.php",
  "reason": "Intermittent timing issue in setUp; needs investigation (Issue #XXX)",
  "owner": "qa",
  "issue": "github-123",
  "expires": "2026-04-21",
  "status": "active"
}

# Tag the test in code:
/**
 * @group flaky-triage
 * @flaky Investigation: timing issue in setUp (Issue #123)
 */
public function testSomething() { ... }
```

**Step 5: Update tracker**
```markdown
## Flake Triage

**Test:** Tests\Unit\YourTestFile::testName
**Pattern:** XYZ happens occasionally
**Decision:** Fixed / Quarantined
**Action:** [Describe what was done]
**Status:** ✅ Resolved / 🔄 Pending / ⌛ Quarantined until 2026-04-21

**Lessons Learned:**
- [Reflect on test design improvements]
```

---

## Scenario: Skip Budget Exceeded (> 12 Total)

### Symptom
Skip governance report shows count > 12 or violations detected.

### Investigation Steps

**Step 1: Check violations**
```bash
# View skip governance report
cat reports/skip-governance-latest.md

# Look for Violations section
grep -A 20 "Violations:" reports/skip-governance-latest.md
```

**Step 2: Identify problematic skips**
- Missing metadata (owner, issue, expires)?
- Expired skips (expiry date in past)?
- New skips without documentation?

**Step 3: Fix each violation**

**For skips without metadata:**
```php
// Before
$this->markTestSkipped('Waiting for feature X');

// After
// @skip owner=qa, issue=github-456, expires=2026-04-24
$this->markTestSkipped('Waiting for feature X (Issue #456)');
```

**For expired skips:**
- Either extend expiry: `expires=2026-05-15`
- Or remove the skip if issue is resolved

**Step 4: Verify compliance**
```bash
# After fixes, run skip governance check locally
php scripts/ci/check-skip-governance.php --output=reports/skip-governance-check.md

# Verify no violations remain
grep "Violations:" reports/skip-governance-check.md
```

**Step 5: Commit & escalate**
```bash
# Commit the fixes
git commit -am "test(skip-governance): resolve budget violations"
git push origin {branch}

# If multiple violations found, escalate to team:
# "Skip budget exceeded; investigate why new skips are being added"
```

---

## Scenario: Counter Progress Stalls (< 5 Green Runs / Week)

### Symptom
After 1-2 weeks, still only 2-3 green runs; counter keeps resetting.

### Diagnostic Checklist

**Check 1: CI reliability**
- Are CI failures infrastructure-related (network, memory)?
- Are test timeouts consistent or intermittent?
- Is there a pattern (certain times of day, certain PRs)?

**Check 2: Regression patterns**
```bash
# Review all WARN decisions from past week
for f in reports/lane-runtime-budget-*-latest.md; do
  if grep -q "WARN" "$f"; then
    echo "=== Breach in $f ===" && grep -A 3 "Duration:" "$f"
  fi
done

# Are breaches in the same lane consistently?
```

**Check 3: Test instability**
```bash
# Count flaky tests in recent reports
for f in reports/flake-report-*-latest.md; do
  echo "=== File: $f ===" && grep -c "FAILED\|FAIL" "$f"
done

# If flake rate > 5%, focus on quarantine/fixes
```

**Check 4: Capacity**
- Are SLO budgets realistic for current test suite size?
- Have many new tests been added but budgets unchanged?
- Is CI infrastructure saturated?

### Escalation Actions

**If budget too tight:**
```markdown
**Discussion Point:** SLO budgets may not account for current test volume.
- Current average: 85s for Unit (budget: 30s)
- Growth rate: +10 tests/week = +5s overhead/week
- Recommendation: Increase budget to 40s or split tests into lanes
```

**If infrastructure bottleneck:**
```markdown
**Action:** Spike on CI performance
- Measure: CPU, memory, disk I/O during test runs
- Check: Can we parallelize further? Use faster hardware?
- Timeline: 2-3 days to investigate, report findings
```

**If test instability endemic:**
```markdown
**Action:** Stabilization week
- Quarantine all flaky tests found
- Audit test code for common anti-patterns
- Set 1-week hard deadline to fix or remove flakes
```

---

## Weekly Standup Script

Use this script to report exit gate progress in team syncs:

```markdown
## Phase 5 Exit Gate Status - Week of {DATE}

**Progress:** {N}/10 consecutive green runs
**Trend:** On track / At risk / Blocked

### Metrics This Week
- **SLO Breaches:** {#WARN in reports}
- **Flake Rate:** {%} (target: < 1%)
- **Skip Count:** {N}/12 (target: trending down)
- **New Quarantine Entries:** {#}

### Blockers
- [List any SLO breaches, infrastructure issues, test instability]

### Actions Completed
- [List fixes applied, tests stabilized, skips resolved]

### Next Week Focus
- [Priority: which issue to tackle]

### Questions for Team
- Any known test suite changes that might affect timing?
- Flaky test root causes identified?
- Progress on quarantine entry metadata?
```

---

## Escalation Decision Tree

```
                        ┌─ Exit Gate Progress Blocked?
                        │
            ┌───────────┴────────────┐
            │                        │
         YES                        NO
            │                        │
            ↓                        ↓
    ┌─────────────────┐     ✅ Continue monitoring
    │ SLO Breaches?   │
    └─────────────────┘
          │  │
      YES │  │ NO
         ↓  │
    ┌─────┐ │
    │ FIX ├─┘─────────────┐
    │ LANE│               ↓
    │TEST │        ┌──────────────┐
    └─────┘        │ Flake Rate   │
                   │ > 2%?        │
                   └──────────────┘
                        │  │
                    YES │  │ NO
                       ↓  │
                   ┌─────┐ │
                   │TRIAGE├─┘──────┐
                   │FLAKES│        ↓
                   └─────┘  ┌──────────────┐
                            │ Skip Budget  │
                            │ > 12?        │
                            └──────────────┘
                                 │  │
                             YES │  │ NO
                                ↓  │
                            ┌─────┐ │
                            │RESOLVE├─┘
                            │SKIPS │
                            └─────┘
```

---

## Success Criteria

**Gate passes when:**
- ✅ Exit gate report shows all 3 criteria PASSED
- ✅ SLO compliance matrix shows 10 consecutive 🟢 runs
- ✅ Skip budget ≤ 12 with downward trend
- ✅ Flake rate < 1.0%
- ✅ No regressions in final 7 days

**Typical milestones:**
- **End of Week 1:** 2-4 green runs accumulated
- **End of Week 2:** 5-8 green runs accumulated
- **Week 3:** 10+ runs → Gate passes

---

## Communication Template

### Daily Standup
"Phase 5 exit gate at N/10. Last run: [🟢 green / 🔴 breach in {lane}]. No blockers. On track."

### Weekly Sync
Use the standup script above.

### Escalation to Lead
"Phase 5 exit gate blocked: {reason}. Estimated recovery: N days. Action: {specific step}."

### Post-Gate Approval
"Phase 5 exit gate achieved! Awaiting formal approval from QA/Platform leads. Gate report in artifacts."

---

## FAQ for Teams

**Q: Does counter reset if CI infrastructure is down?**
A: No, infrastructure outages pause the counter. Once CI recovers, counting resumes with no penalty.

**Q: What if a single test causes constant resets?**
A: That's a blocker. Quarantine or fix the test immediately. Don't wait for the counter to naturally recover.

**Q: Can we manually reset the counter if we make major changes?**
A: No. The 10-run gate is non-negotiable. Even major changes don't reset—they just start the count again at 0.

**Q: How strict is the "consecutive green" requirement?**
A: Strict. One SLO breach resets to 0. The goal is 10 consecutive runs with zero violations.

**Q: Can we adjust SLO budgets mid-collection?**
A: No. Budgets are locked until Phase 5 approval. If budgets prove impossible, escalate to engineering lead for exception.

**Q: What happens post-exit-gate if we breach an SLO?**
A: Post-gate, violations don't reset the counter—they alert the team to investigate a regression. Guardrails remain active permanently.

---

## Resources

- **Exit Gate Documentation:** [docs/testing/PHASE_5_EXIT_GATE.md](PHASE_5_EXIT_GATE.md)
- **Phase 5 Summary:** [docs/testing/PHASE_5_SUMMARY.md](PHASE_5_SUMMARY.md)
- **Skip Governance Policy:** [docs/testing/TESTING_CONTRIBUTION_GUIDE.md#skip-governance](TESTING_CONTRIBUTION_GUIDE.md)
- **Flake Triage Process:** [docs/testing/FLAKY_TEST_TRIAGE.md](FLAKY_TEST_TRIAGE.md)
- **Quick Commands:** [docs/testing/TESTING_QUICK_START.md](TESTING_QUICK_START.md)

---

## Final Notes

✅ **You have everything needed to succeed.** The guardrails are active, the dashboard is live, and the process is clear. Focus on:
1. Fixing SLO breaches immediately (don't let them compound)
2. Triaging and quarantining flakes (work toward < 1% rate)
3. Keeping skip count ≤ 12 and trending down (enforcement is real)

The 10-run gate is not a test—it's a confirmation that the system is stable and ready for production. Each green run proves the guardrails work. Once 10 are achieved, Phase 5 is formally complete.
