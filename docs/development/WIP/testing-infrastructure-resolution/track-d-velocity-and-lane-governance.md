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
- [x] Hidden framework boot in Unit tests is reduced or explicitly justified.
- [x] Skipped tests are inventoried with owner and exit criteria.
- [x] Lane-budget enforcement is observable and easy to act on.

## Tasks
### D1. Audit Unit isolation exceptions
- [x] Inspect the four framework-boot guard files declared in `tests/Pest.php`.
- [x] Confirm whether each exception is still necessary.
- [x] For any removable exception, migrate it to `Tests/PureUnitTestCase`.

Result:
- All four root guard files were only scanning repository files and dates. None required the Laravel container, database, or facades.
- `tests/Unit/UnitFrameworkBootingGuardTest.php`, `tests/Unit/FeatureWriteAssertionDepthGuardTest.php`, `tests/Unit/ModuleUnitIsolationGuardTest.php`, and `tests/Unit/RefreshDatabaseUsageGuardTest.php` now run under `Tests/PureUnitTestCase`.
- The dedicated Unit framework-boot exception block in `tests/Pest.php` was removed.

### D2. Audit facade usage inside Unit tests
- [x] Inventory the 19 Unit files that reference facades.
- [x] Classify each usage:
  - safe fake/mock pattern
  - hidden container/framework coupling
  - should move to Integration
- [x] Create follow-up tasks for any file that still violates true unit isolation.

Inventory summary:
- Keep: 18 files use facades through explicit local container setup or facade swapping inside `Tests/PureUnitTestCase`, which keeps them framework-free at the suite level. This bucket includes the helper, mail, model-helper, middleware, HTTP-request, and service facade tests already migrated into pure unit scope.
- Fix: `tests/Unit/Models/UserPermissionLogicTest.php` still contains a parallel-worker alias-mocking skip. It is not a hidden framework boot issue, but it is a unit-isolation governance issue because the test outcome depends on class-load order.
- Move: none from the 19-file inventory currently require migration to Integration based on the audit sample. They build local containers or use facade fakes without booting the Laravel application kernel.

Follow-up tasks:
- Replace alias-mocking in `tests/Unit/Models/UserPermissionLogicTest.php` with a seam that does not depend on whether `Permission` has already been loaded in a worker.
- Keep watching service tests that construct `Illuminate\Foundation\Application` manually; they are acceptable now, but they should not expand into broader app boot or database usage.

### D3. Skipped-test governance
- [x] Identify the 3 skipped tests from the latest full-suite run.
- [x] Assign owner, reason, and exit condition for each.
- [x] Decide whether they belong in quarantine governance or should be fixed immediately.

Current skipped tests from the latest full-suite JUnit artifact (`storage/infection/junit.xml`):

| Test | Owner | Reason | Exit condition | Decision |
|---|---|---|---|---|
| `Tests\Unit\Models\UserPermissionLogicTest` | QA/Platform | Alias mocking skips when `Permission` is already loaded in a parallel worker. | Replace alias mocking with a non-alias seam or isolated collaborator so the test is deterministic in parallel workers. | Fix immediately; do not quarantine. |
| `Modules\AssetManagement\Tests\Integration\ConcurrentCounterPestTest` | AssetManagement + QA/Platform | Test requires MySQL/PostgreSQL semantics and intentionally skips on SQLite in-memory environments. | Route the test to a database-backed lane or provision the required engine in the lane that owns concurrent-counter guarantees. | Keep under explicit skip governance; environment-gated. |
| `Modules\EmailMigration\Tests\Feature\LabMigrationPestTest` | EmailMigration | Lab host configuration is absent in normal CI/dev environments. | Provide a lab-configured environment or split the scenario into a separately managed environment test lane. | Keep under explicit skip governance; environment-gated. |

### D4. Lane budget reporting
- [x] Review `scripts/ci/check-test-lane-runtime-budgets.php` and existing budget reports.
- [x] Ensure Unit, Feature, and Integration lane runs are easy to execute independently.
- [x] Add a short operator note describing what to do when a lane exceeds budget.

Result:
- Added `scripts/testing/run-test-lane.sh` so maintainers can run `unit`, `feature`, `integration`, `guards`, or `architecture` lanes locally and automatically emit the corresponding runtime-budget report.
- Added an operator-action section to `scripts/ci/check-test-lane-runtime-budgets.php` reports.
- Documented the local runner and breach workflow in `scripts/ci/README.md` and `docs/testing/TESTING_CONTRIBUTION_GUIDE.md`.

## Acceptance Markers
- [x] Every Unit framework-boot exception is either removed or justified.
- [x] Facade-using Unit tests are classified into keep/fix/move buckets.
- [x] Skipped tests are no longer anonymous.
- [x] Lane-budget actions are documented for maintainers.

## Suggested Agent Assignment
- Best for an agent focused on suite performance, isolation discipline, and CI governance.
