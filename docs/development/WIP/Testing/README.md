# Testing Phase Implementation Pack

This folder operationalizes the test-suite roadmap into phase-specific execution guides.

Each phase file contains:
- scope and exit criteria
- current baseline from the March 19, 2026 audit
- implementation sequence
- autonomous execution guidance for an LLM agent
- safe command patterns that align with terminal auto-approve
- a copy-pasteable prompt to initiate the work

## How To Use This Pack

1. Start with the phase file that matches the current bottleneck.
2. Give the LLM the prompt at the end of that file.
3. Let it work autonomously on read-only inspection, targeted edits, non-destructive test runs, and log review.
4. Require a checkpoint before architectural rewrites, broad cross-module refactors, or any destructive operation.

## Start Phase 0 Now

Use this sequence to begin immediately:

1. Read:
	- `docs/development/WIP/Testing/PHASE_0_STABILIZE.md`
	- `docs/development/TESTING_CONTRIBUTION_GUIDE.md`
	- `docs/development/TEST_MAINTENANCE_CADENCE.md`
2. Run architecture and isolation checks first.
3. Inspect `reports/test-results-latest.log` and triage top recurring failures.
4. Iterate with focused fixes and test slices.
5. Track toward 3 consecutive canonical green runs.

Suggested kickoff commands:

```bash
php artisan test tests/Architecture/
php artisan test tests/Unit/ModuleUnitIsolationGuardTest.php
grep -A 8 "FAILED" reports/test-results-latest.log
tail -n 40 reports/test-results-latest.log
```

## Phase Auto-Selection Meta-Prompt

Use this prompt when you want the agent to choose the correct phase automatically and begin work immediately:

```text
You are running the Testing Phase Implementation Pack.

Read docs/development/WIP/Testing/README.md and all phase files under docs/development/WIP/Testing.
Inspect current suite signals from reports/test-results-latest.log, recent reports/test-results-*.log, and quick metric scans in tests/ and Modules/.

Select the single highest-leverage phase to execute next, explain why in 3-5 bullets, then start implementation work immediately.

Autonomy requirements:
- proceed autonomously for inspection, targeted edits, and non-destructive test commands
- prefer php artisan test and saved reports logs for validation
- avoid destructive or blocked shell patterns
- pause only for ambiguous product requirements or major architecture decisions

Delivery requirements:
- list files changed
- list tests run and outcome
- list updated metrics and remaining risks
- recommend the next phase after completion
```

## Current Recommended Order

1. Phase 0: Stabilize
2. Phase 1: Junk Elimination
3. Phase 2: Pyramid Rebalance
4. Phase 3: Critical Coverage
5. Phase 4: Reliability Proof
6. Phase 5: Isolation Tightening
7. Phase 6: Zero-Allowlist Architecture
8. Phase 7: Regression Guardrails
9. Phase 8: Mutation CI Integration
10. Phase 9: Developer Experience
11. Phase 10: Sustainment

## Autonomous Execution Rules

The current terminal auto-approve configuration supports a high degree of autonomous work for this testing program.

### Safe Autonomous Work

An agent can usually proceed without waiting when doing the following:
- run `php artisan test`
- run targeted `vendor/bin` tools that match the allowlist
- inspect files with `grep`, `find`, `sed`, `awk`, `head`, `tail`, `wc`, `sort`, `uniq`, `diff`
- inspect git state with `git status`, `git diff`, `git log`, `git show`
- inspect filesystem layout with `ls`, `pwd`, `realpath`, `stat`, `tree`
- use non-destructive artisan commands such as `about`, `route:list`, `optimize:clear`

### Commands To Prefer

- Prefer `php artisan test` because the project already logs to `reports/test-results-<timestamp>.log` and updates `reports/test-results-latest.log`.
- Prefer reading `reports/test-results-latest.log` instead of rerunning expensive test suites.
- Prefer simple single-line commands over shell grouping or complex command substitutions.
- Prefer focused test runs before full suite runs.

### Commands To Avoid In Autonomous Mode

The auto-approve config is intentionally hostile to risky shell patterns. Agents should avoid:
- destructive commands such as `rm`, `rmdir`, `dd`, `kill`, `chmod`, `chown`
- network fetches such as `curl` and `wget`
- shell groupings or patterns with parentheses, braces, or backticks in the command line
- any command that tries to bypass log capture or pipe test output away from the terminal unnecessarily

### Practical Agent Behavior

Use this default behavior unless the phase guide says otherwise:
- inspect first
- edit the minimum number of files needed
- run the narrowest relevant test slice
- inspect `reports/test-results-latest.log`
- continue autonomously until the phase task is complete or a real design decision is required

## File Index

- `PHASE_0_STABILIZE.md`
- `PHASE_1_JUNK_ELIMINATION.md`
- `PHASE_2_PYRAMID_REBALANCE.md`
- `PHASE_3_CRITICAL_COVERAGE.md`
- `PHASE_4_RELIABILITY_PROOF.md`
- `PHASE_5_ISOLATION_TIGHTENING.md`
- `PHASE_6_ZERO_ALLOWLIST_ARCHITECTURE.md`
- `PHASE_7_REGRESSION_GUARDRAILS.md`
- `PHASE_8_MUTATION_CI_INTEGRATION.md`
- `PHASE_9_DEVELOPER_EXPERIENCE.md`
- `PHASE_10_SUSTAINMENT.md`
