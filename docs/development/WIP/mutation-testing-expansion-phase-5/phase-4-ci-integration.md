# Phase 5: Mutation Testing Expansion — CI/CD Integration Strategy

**Objective:** Integrate mutation testing as a gated CI/CD step without blocking velocity.

---

## Current CI/CD State

**Problem:** Mutation testing is not in `scripts/ci/` pipeline. It's opt-in local only.

**Impact:**
- No enforcement of mutation score thresholds.
- Regressions slip past code review.
- Only developers running `./vendor/bin/infection` locally see results.

---

## Proposed CI Integration Model

### Tier-Based Execution Strategy

| Tier | Scope | Run Trigger | Frequency | Timeout | MSI Threshold |
| :--- | :--- | :--- | :--- | ---: | ---: |
| Tier 1 | 3 Modules | Manual / Nightly | Weekly | 180 min | ≥ 80 |
| Tier 2 | Tier 1 + app/Services | Post-PR | Every PR | 45 min | ≥ 70 |
| Tier 3 | All (deferred) | TBD | TBD | - | TBD |

---

## Integration Points

### 1. Post-PR Gate (Tier 2)

**When:** After unit/integration tests pass.
**Where:** `scripts/ci/check-mutation-tier2.sh` (implemented in Phase 3).

**GitHub Actions / GitLab CI Example:**

```yaml
# .github/workflows/test-mutation.yml (GitHub)
name: Mutation Testing (Tier 2)

on:
  pull_request:
    paths-ignore:
      - 'docs/**'
      - '*.md'

jobs:
  mutation:
    runs-on: ubuntu-latest
    timeout-minutes: 55

    steps:
      - uses: actions/checkout@v3

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: xdebug

      - name: Install Dependencies
        run: composer install --no-interaction

      - name: Run Test Suite (Parallel, No Coverage)
        run: ./vendor/bin/pest --parallel --processes=10

      - name: Collect Coverage
        run: |
          XDEBUG_MODE=coverage php -d memory_limit=3G ./vendor/bin/pest \
            --coverage-xml=storage/infection/coverage

      - name: Mutation Testing (Tier 2)
        run: bash scripts/ci/check-mutation-tier2.sh

      - name: Upload Results
        if: always()
        uses: actions/upload-artifact@v3
        with:
          name: mutation-results
          path: |
            reports/infection-tier2-summary.json
            reports/infection-tier2.log
```

---

### 2. Scheduled Nightly (Tier 1)

**When:** Once per week (e.g., Monday 2 AM UTC).
**Where:** Separate CI job targeting Tier 1 only.
**Output:** Notify Slack/email if MSI drops below 80.

```yaml
# .github/workflows/mutation-tier1-nightly.yml
name: Mutation Testing (Tier 1 - Full Suite)

on:
  schedule:
    # Monday 2 AM UTC
    - cron: '0 2 * * 1'
  workflow_dispatch:

jobs:
  mutation-tier1:
    runs-on: ubuntu-latest
    timeout-minutes: 200

    steps:
      - uses: actions/checkout@v3

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: xdebug

      - name: Install Dependencies
        run: composer install --no-interaction

      - name: Collect Coverage
        run: |
          XDEBUG_MODE=coverage php -d memory_limit=4G ./vendor/bin/pest \
            --coverage-xml=storage/infection/coverage

      - name: Mutation Testing (Tier 1)
        run: ./vendor/bin/infection

      - name: Notify Results
        if: always()
        run: |
          # Parse infection-summary.json and post to Slack/Teams
          php scripts/ci/notify-mutation-results.php

      - name: Archive Results
        uses: actions/upload-artifact@v3
        with:
          name: mutation-tier1-results
          path: |
            reports/infection-summary.json
            reports/infection.log
          retention-days: 30
```

---

### 3. Local Pre-Commit Hook (Optional)

**Goal:** Catch issues before pushing.

**File:** `.git/hooks/pre-push` (developers run `./scripts/setup-hooks.sh`)

```bash
#!/bin/bash
# Pre-push mutation check (Tier 2 only, ~40 min)
# Developers can skip with: git push --no-verify

echo "🧬 Pre-push mutation check (Tier 2)..."
bash scripts/ci/check-mutation-tier2.sh

if [ $? -ne 0 ]; then
    echo ""
    echo "❌ Mutation Tier 2 failed. Review escaped mutants:"
    echo "   cat reports/infection-tier2.log"
    echo ""
    echo "To skip this check: git push --no-verify"
    exit 1
fi
```

---

## Notification & Reporting

### Post-PR Feedback

**GitHub:** Comment on PR with summary.

```
🧬 **Mutation Testing Results (Tier 2)**

| Metric | Value | Status |
| :--- | ---: | :--- |
| MSI | 73% | ✅ Pass (≥70) |
| Covered MSI | 76% | ✅ Pass (≥75) |
| Killed | 2,847 / 3,890 | |
| Escaped | 42 | ⚠️ Review |

**Escaped Mutants** (top 5):
- Line 145 in `app/Services/EntitlementEngine.php`: Incremented value not asserted
- Line 203 in `app/Services/CacheService.php`: Null check missing test
- ...

👉 [View Full Report](reports/infection-tier2.log)
```

**Implementation:** Create `scripts/ci/post-mutation-github-comment.php`.

---

### Mutation Trend Dashboard (Phase 6)

Store MSI per commit in a JSON historical log:

```json
[
  {
    "commit": "abc1234",
    "date": "2026-03-25T10:30:00Z",
    "tier1_msi": 100,
    "tier2_msi": 73,
    "escaped_count": 42,
    "status": "pass"
  },
  ...
]
```

Plot in a dashboard (GitHub Pages or equivalent) to visualize trends.

---

## Error Handling & Fallback

### Timeout Handling (Tier 2 > 45 min)

**Action:** Fail PR with diagnostic.

```bash
# In check-mutation-tier2.sh
timeout 45m php vendor/bin/infection ... || {
    EXIT_CODE=$?
    if [ $EXIT_CODE -eq 124 ]; then
        echo "❌ Mutation testing timed out (> 45 min)."
        echo "   This usually means coverage data is too large or threads oversubscribed."
        echo "   Review: reports/infection-tier2.log (partial)"
        echo ""
        echo "   Mitigation: Reduce --threads from 6 to 4 and retry."
        exit 1
    fi
}
```

---

### Memory Exhaustion (Coverage Merge)

**Action:** Detect OOM and retry sequentially.

```bash
# Wrapped in check-mutation-tier2.sh
if grep -q "Allowed memory size" reports/infection-tier2.log; then
    echo "⚠️  Memory exhaustion detected during coverage."
    echo "   Retrying with sequential coverage mode..."

    # Fallback: Sequential coverage (simply omit --parallel flag)
    XDEBUG_MODE=coverage php -d memory_limit=4G ./vendor/bin/pest \
        --coverage-xml=storage/infection/coverage

    # Retry mutation
    php vendor/bin/infection --configuration=infection-extended.json5
fi
```

---

## Cost & Performance Analysis

### Tier 2 Post-PR Cost

| Component | Time | Cost (@ $1/min) |
| :--- | ---: | ---: |
| Test Suite (parallel) | 2 min | $2 |
| Coverage Collection | 8 min | $8 |
| Mutation Tier 2 | 30–40 min | $30–40 |
| **Total per PR** | **40–50 min** | **$40–50** |

**Optimization Options:**
- **Reduce threads:** 6 → 4 saves ~10 min.
- **Cache coverage:** Reuse if code unchanged (advanced).
- **Selective mutation:** Only mutate changed files (Infection v0.29+).

---

## Rollout Schedule

| Date | Action |
| :--- | :--- |
| Week 1 (Phase 3) | Implement & test `check-mutation-tier2.sh` locally. |
| Week 2 | Enable in staging CI/CD branch. |
| Week 2–3 | Trial run on select PRs; refine timeouts. |
| Week 3 | Announce to team; enable on all PRs. |
| Week 4 | Enable scheduled Tier 1 (nightly). |

---

## Team Expectations

### PR Author
- Mutation Tier 2 must pass (or result in no-regression score).
- Address escaped mutants by improving assertions.
- Run locally before pushing: `bash scripts/ci/check-mutation-tier2.sh`.

### Code Reviewer
- Check mutation summary comment on PR.
- Query if escaped mutants indicate weak assertions.
- Example: "Mutation escaped at line 145; ensure assertion checks side effect."

### QA Lead
- Monitor mutation trends (weekly).
- Investigate Tier 1 nightly failures.
- Adjust MSI thresholds as coverage improves.

---

## Success Metrics

- ✅ 100% of PRs run Tier 2 mutation testing.
- ✅ Average Tier 2 time ≤ 45 min.
- ✅ Tier 2 MSI maintained ≥ 70.
- ✅ Zero OOM errors in parallel coverage (sequential fallback works).
- ✅ Team runs Tier 2 locally before pushing.

