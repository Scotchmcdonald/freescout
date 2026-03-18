# Test Maintenance Cadence

*Established: 2026-03-18*

## Quarterly Schedule

### Q1 (January–March): Stale Test Triage

- Identify tests not modified in 6+ months that cover code that has also not changed.
- Decide: keep, consolidate, or delete.
- Review `@flaky` annotations and quarantine markers — resolve or remove.
- Target: remove or consolidate at least 10 stale test files per cycle.

### Q2 (April–June): Duplicate Scenario Scan and Consolidation

- Run duplicate detection: find test methods with >80% assertion overlap.
- Consolidate duplicate coverage into the most appropriate layer (prefer unit > integration > feature).
- Review `assertSee` count and reduce toward target (≤100).
- Target: `assertSee` count ≤ previous quarter's count.

### Q3 (July–September): Scorecard Refresh and Allowlist Expiry Review

- Run the full scorecard from `TEST_SUITE_MASTER_PHASE_PLAN_6_5_TO_10.md` Part 4.
- Review all `@expires` dates on allowlist entries in `ModuleUnitIsolationGuardTest`.
- Extend or resolve expired entries — no silent rollovers.
- Update `TESTING_CONTRIBUTION_GUIDE.md` with any new patterns or prohibitions.
- Target: 0 expired allowlist entries.

### Q4 (October–December): Mutation Score Re-run and Regression Gate Recalibration

- Re-run `infection/infection` on the 3 financial domains (ContractManager, PIB, Payment).
- Compare mutation scores against the recorded baseline — investigate any regression > 5 points.
- Recalibrate CI mutation thresholds if the codebase has grown or refactored significantly.
- Review architecture enforcement rules — add new rules for any new modules.
- Target: mutation scores ≥ prior quarter's baseline.

## Ongoing (Every Sprint)

- Run `php artisan test --parallel --processes=10` before merging any PR.
- Check `reports/test-results-latest.log` for failures — do not merge with red tests.
- New modules must include `Tests/Unit/`, `Tests/Integration/`, and `Tests/Feature/` directories.
- New services in financial modules must have at least 1 failure-path test.

## Metrics to Track

| Metric | Review Frequency | Direction |
|---|---|---|
| Total test files | Quarterly | Stable or decreasing |
| RefreshDatabase count | Quarterly | Decreasing |
| assertSee count | Quarterly | Decreasing (target ≤100) |
| makePartial count | Quarterly | Decreasing (target ≤5) |
| Allowlist entries | Quarterly | Decreasing |
| Consecutive green runs | Monthly | Increasing |
| Mutation score (3 domains) | Q4 | Stable or increasing |
