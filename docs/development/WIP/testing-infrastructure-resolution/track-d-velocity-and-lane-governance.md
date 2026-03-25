# Track D - Velocity, Lane Governance, And Isolation Cleanup

## Goal
Protect the current fast feedback loop by keeping unit tests framework-free, making skipped-test governance explicit, and validating lane budgets with actionable ownership.

## Why This Track Exists
- The suite completes in `109.44s` with 10 workers, which is strong for this repository size.
- `tests/Pest.php` intentionally binds almost all Unit tests to `Tests/PureUnitTestCase`.
- Four guarded Unit files still boot Laravel through `Tests/UnitTestCase`.
- 19 Unit files reference facades, indicating possible hidden container coupling.
- 3 tests are skipped, but the governance path for skipped/quarantined tests is not surfaced in the audit output.

## Primary Deliverables
- [ ] Hidden framework boot in Unit tests is reduced or explicitly justified.
- [ ] Skipped tests are inventoried with owner and exit criteria.
- [ ] Lane-budget enforcement is observable and easy to act on.

## Tasks
### D1. Audit Unit isolation exceptions
- [ ] Inspect the four framework-boot guard files declared in `tests/Pest.php`.
- [ ] Confirm whether each exception is still necessary.
- [ ] For any removable exception, migrate it to `Tests/PureUnitTestCase`.

### D2. Audit facade usage inside Unit tests
- [ ] Inventory the 19 Unit files that reference facades.
- [ ] Classify each usage:
  - safe fake/mock pattern
  - hidden container/framework coupling
  - should move to Integration
- [ ] Create follow-up tasks for any file that still violates true unit isolation.

### D3. Skipped-test governance
- [ ] Identify the 3 skipped tests from the latest full-suite run.
- [ ] Assign owner, reason, and exit condition for each.
- [ ] Decide whether they belong in quarantine governance or should be fixed immediately.

### D4. Lane budget reporting
- [ ] Review `scripts/ci/check-test-lane-runtime-budgets.php` and existing budget reports.
- [ ] Ensure Unit, Feature, and Integration lane runs are easy to execute independently.
- [ ] Add a short operator note describing what to do when a lane exceeds budget.

## Acceptance Markers
- [ ] Every Unit framework-boot exception is either removed or justified.
- [ ] Facade-using Unit tests are classified into keep/fix/move buckets.
- [ ] Skipped tests are no longer anonymous.
- [ ] Lane-budget actions are documented for maintainers.

## Suggested Agent Assignment
- Best for an agent focused on suite performance, isolation discipline, and CI governance.
