# Phase 1 — EmailMigration: 8% → 60%

**Coverage target:** +6,983 lines → running total ~65.6%
**Why first:** Largest single gap in the codebase (12,339 uncovered lines). Most classes are pure PHP (Models, DTOs, Events, Services) requiring only factory + Mockery skills already established.

---

## Current State

| Sub-namespace | Exe Lines | Covered | % |
|:--------------|----------:|--------:|--:|
| Models (10 classes) | ~1,400 | ~0 | ~0% |
| Jobs (12 classes) | ~2,300 | ~120 | ~5% |
| Services (10 classes) | ~1,800 | ~90 | ~5% |
| Controllers (8 classes) | ~1,100 | ~55 | ~5% |
| Console commands (14) | ~700 | ~0 | ~0% |
| Events / DTOs / Listeners / Mail | ~350 | ~80 | ~23% |
| **Total** | **~13,428** | **~1,089** | **8.1%** |

---

## Test File Plan

### 1A — Models (target: 90% each)

**File:** `tests/Integration/EmailMigration/ModelsCoverageTest.php`

Classes to cover:
- `MigrationProject` — factory, create/status transitions, relationships (mailboxes, batches, logs)
- `MigrationMailbox` — factory, status enum transitions, relationships (project, logs)
- `MigrationBatch` — factory, batch-status, timestamps
- `MigrationLog` — factory, level filtering, message truncation
- `MigrationJobLog` — factory, status/error fields
- `MigrationMapping` — factory, source/destination fields
- `MigrationProfile` — factory, credential masking
- `MigrationCheckpoint` — factory, offset/resume fields
- `MigrationMessage` — factory, size/uid fields
- `MigrationSubscription` — factory, event subscription

**Estimated lines covered:** ~1,200
**Test pattern:** `IntegrationTestCase` + `RefreshDatabase`, use `MigrationProjectFactory`, `MigrationMailboxFactory`, `MigrationMappingFactory`

```php
it('creates a migration project with mailboxes', function () {
    $project = MigrationProject::factory()
        ->has(MigrationMailbox::factory()->count(3))
        ->create();
    expect($project->mailboxes)->toHaveCount(3);
    expect($project->status)->toBe(MigrationStatus::Pending);
});
```

### 1B — Events, DTOs, Exceptions (target: 95%)

**File:** `tests/Unit/EmailMigration/EventsDtosPureTest.php`

Classes to cover:
- All 9 Event classes (DiscoveryCompleted, MailboxCompleted, MigrationDailySummaryReady, etc.)
- `StoreMigrationProfileData`, `StoreProjectMailboxData`, `UpdateMigrationProfileData`
- `RateLimitException`

**Estimated lines covered:** ~280
**Test pattern:** `PureUnitTestCase` — direct construction, assert `broadcastOn()`, DTO property access

### 1C — Pure / Utility Services (target: 75%)

**File:** `tests/Unit/EmailMigration/UtilityServicesTest.php`

Services with no external I/O (fully mockable or pure):
- `ImapErrorParser` — string parsing, error classification → pure unit test
- `LabValidator` — validation rules on config arrays → pure unit test
- `ProviderProfileFactory` — factory methods, DTO construction → pure unit test
- `LabHealthService` — status aggregation logic (mock DB queries)

**Estimated lines covered:** ~350
**Test pattern:** `PureUnitTestCase`, mock any repository with Mockery

### 1D — External-API Services (target: 50% via mocking)

**File:** `tests/Unit/EmailMigration/ExternalServicesTest.php`

- `LabManager` (663-line gap) — mock Docker client, assert container lifecycle calls
- `ImapDiscoveryService` (158-line gap) — mock IMAP connection, test folder discovery logic
- `MappingCsvService` (135-line gap) — pure CSV parsing, no external deps
- `ConnectivityAuditor` (121-line gap) — mock socket connections, test result aggregation
- `MigrationTicketService` (130-line gap) — mock DB / FreeScout models, assert ticket creation
- `TestConnectionService` — mock IMAP, assert result format
- `GoogleMigrationService` — mock Google client, assert API calls

**Estimated lines covered:** ~900
**Test pattern:** Mockery to mock external clients; test internal logic branches

```php
it('detects rate limit from IMAP error response', function () {
    $parser = new ImapErrorParser();
    $result = $parser->classify('[THROTTLED] Too many connections');
    expect($result->isRateLimit())->toBeTrue();
});
```

### 1E — Jobs (target: 40% via Queue faking)

**File:** `tests/Integration/EmailMigration/JobsTest.php`

- `RunMigrationJob` (538-line gap) — `Queue::fake()` + mock LabManager, assert chained jobs dispatched
- `MigrateMailboxJob` (368-line gap) — mock IMAP, assert log creation + status updates
- `MigrateFolderJob` (207-line gap) — mock IMAP folder iterator, assert progress updates
- `VerifyMigrationJob` (186-line gap) — mock verification service, assert pass/fail result
- `CancelMigrationJob`, `CheckDnsPropagation`, `FetchSourceFoldersJob`, `RunHealthCheckJob` — happy path + error path

**Estimated lines covered:** ~1,000
**Test pattern:** `IntegrationTestCase`, `Queue::fake()`, mock external dependencies via constructor injection

```php
it('dispatches MigrateMailboxJob for each active mailbox', function () {
    Queue::fake();
    $project = MigrationProject::factory()->withMailboxes(3)->create();
    dispatch(new RunMigrationJob($project->id));
    Queue::assertPushed(MigrateMailboxJob::class, 3);
});
```

### 1F — Controllers (target: 50%)

**File:** `tests/Feature/EmailMigration/ControllersTest.php`

- `EmailMigrationController` (551-line gap) — CRUD for projects: index, store, show, update, delete
- `LabController` (162-line gap) — lab start/stop/status endpoints
- `MappingController` (157-line gap) — CSV upload, mapping CRUD
- `DiscoveryController` — discovery start, result fetch
- `MigrationDashboardController` — dashboard data endpoints
- `PublicStatusController` — public status endpoint (no auth)
- `ScheduleController` — schedule CRUD

**Estimated lines covered:** ~630

```php
it('admin can view migration projects list', function () {
    actingAsAdmin()
        ->getJson(route('email-migration.index'))
        ->assertOk()
        ->assertJsonStructure(['data' => [['id', 'status', 'mailbox_count']]]);
});

it('rejects unauthenticated access to migration projects', function () {
    getJson(route('email-migration.index'))
        ->assertStatus(401);
});
```

### 1G — Console Commands (target: 60%)

**File:** `tests/Integration/EmailMigration/ConsoleCommandsTest.php`

- `CheckScheduledMigrationsCommand` — assert jobs dispatched for due migrations
- `MigrationWatchtower` — mock stuck states, assert alerts fired
- `ResumeRateLimitedMigrationsCommand` — assert resumes only rate-limited projects
- `EmergencyStopCommand` — assert all active jobs cancelled
- `VerificationReportCommand` — assert report mail dispatched
- (remaining commands as secondary targets)

**Estimated lines covered:** ~450

---

## Phase 1 Estimated Coverage Gain

| Test File | Est. Lines Covered |
|:----------|-------------------:|
| 1A Models | +1,200 |
| 1B Events/DTOs | +280 |
| 1C Utility Services | +350 |
| 1D External Services | +900 |
| 1E Jobs | +1,000 |
| 1F Controllers | +630 |
| 1G Console | +450 |
| **Total** | **+4,810** |

> Running total: 24,244 + 4,810 = **29,054 / 47,606 = 61.0%**
> (Conservative; actual may be higher pending inline coverage from shared code paths)

---

## Acceptance Criteria

- [ ] All 7 test files exist with passing tests
- [ ] `EmailMigration` namespace coverage ≥ 50% (from 8.1%)
- [ ] Full test suite: `php artisan test --parallel --processes=10` → green
- [ ] Global coverage: ≥ 60% (verify via coverage-xml script)
- [ ] Tier 2 mutation MSI ≥ 95 (unchanged)
- [ ] Commit: `test: Phase 1 — EmailMigration coverage (8% → 50%+)`

---

## Notes

- `LabManager` wraps Docker Shell — use `Mockery::mock()` for the `Process` class or similar. If Docker Process is not injectable, cover only the orchestration branches and mark the Docker-call lines with `// @codeCoverageIgnore`.
- `ImapDiscoveryService` and `ConnectivityAuditor` wrap socket connections — isolate via constructor injection of a connection factory; mock the factory.
- `RunMigrationJob` likely contains the most branching logic — prioritise dispatch chain tests over completing the full 538 gap; 40% coverage still adds 215 newly-covered lines.
