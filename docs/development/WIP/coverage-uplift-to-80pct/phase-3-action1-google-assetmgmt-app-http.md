# Phase 3 — Action1, GoogleAdmin, AssetManagement, Alerts, app/Http, app/Console

**Coverage target after phase:** ~83.9% (+4,600 lines from Phase 2 end)
**Key challenge:** Action1 and GoogleAdmin wrap external HTTP APIs. Jobs, Commands need careful mocking.

---

## Namespace Targets

| Namespace | Current % | Target % | Gap | New Lines |
|:----------|----------:|--------:|----:|----------:|
| `Modules/Action1` | 41.1% | 70% | 3,903 | +1,916 |
| `Modules/GoogleAdmin` | 45.7% | 78% | 1,459 | +871 |
| `Modules/AssetManagement` | 36.0% | 70% | 1,548 | +820 |
| `Modules/Alerts` | 51.3% | 78% | 1,090 | +577 |
| `app/Http` | 53.5% | 72% | 3,302 | +1,302 |
| `app/Console` | 36.4% | 70% | 850 | +454 |
| **Total** | | | **12,152** | **~5,940** |

---

## Test File Plans

### 3A — Action1 Service (target: 60% — up from 0%)

**File:** `tests/Unit/Action1/Action1ServiceTest.php`

`Action1Service` (331-line gap, 0%) manages device sync, client config, and script execution against the Action1 MSP RMM API.

**Strategy:** Inject an HTTP client mock via `Http::fake()` or constructor injection.

```php
beforeEach(function () {
    Http::fake([
        'api.action1.com/*' => Http::response(['devices' => []], 200),
    ]);
    $this->service = app(Action1Service::class);
});

it('returns empty device list when API returns no devices', function () {
    $result = $this->service->getDevicesForClient('client-123');
    expect($result)->toBeEmpty();
});

it('throws Action1ApiException on 401 response', function () {
    Http::fake(['api.action1.com/*' => Http::response([], 401)]);
    expect(fn () => $this->service->getDevicesForClient('bad-key'))
        ->toThrow(Action1ApiException::class);
});
```

**Estimated lines covered:** +900

### 3B — Action1 Controllers + Console (target: 65%)

**File:** `tests/Feature/Action1/ControllersConsoleTest.php`

- `AuditController` (262-line gap, 20%) — audit log list, filter by date/type, export
- `Action1ClientConfigController` — client config read/update
- `Action1GroupController` — group CRUD
- `Action1ScriptManagerController` — script upload, list, delete
- `SyncAction1Command` / `SyncAllDevices` console commands (mock Action1Service)

```php
it('admin can view action1 audit log', function () {
    actingAsAdmin()
        ->get(route('action1.audit.index'))
        ->assertOk()
        ->assertViewIs('action1::audit.index');
});

it('sync command dispatches sync jobs for all active clients', function () {
    Queue::fake();
    artisan('action1:sync')->assertExitCode(0);
    Queue::assertPushed(SyncClientDevicesJob::class);
});
```

**Estimated lines covered:** +850

### 3C — GoogleAdmin Service + Controllers (target: 75%)

**File:** `tests/Unit/GoogleAdmin/GoogleWorkspaceServiceTest.php` + `tests/Feature/GoogleAdmin/ControllersTest.php`

- `GoogleWorkspaceService` (288-line gap, 16%) — mock Google API client, test all public methods
  - `listUsers()`, `createUser()`, `suspendUser()`, `listGroups()`, `addToGroup()`
- `GoogleConfigController` (174-line gap, 0%) — settings read/write, OAuth redirect
- Model coverage for `GoogleAdmin` models

```php
beforeEach(function () {
    $this->mockGoogle = Mockery::mock(GoogleDirectoryClient::class);
    app()->instance(GoogleDirectoryClient::class, $this->mockGoogle);
});

it('lists workspace users', function () {
    $this->mockGoogle->shouldReceive('users->list')
        ->andReturn(new UsersListResponse(['users' => []]));
    $service = app(GoogleWorkspaceService::class);
    expect($service->listUsers('domain.com'))->toBeEmpty();
});
```

**Estimated lines covered:** +870

### 3D — AssetManagement (target: 70%)

**File:** `tests/Feature/AssetManagement/AssetManagementCoverageTest.php`

Expand on the 8 existing AssetManagement tests. Current gap: 1,548 lines at 36%.

Key untested paths:
- Asset assignment/deassignment workflows
- Asset conflict resolution
- Portal asset view
- `client_assets` widget data
- Asset CSV import handling

```php
it('assigns an asset to a client and records the assignment', function () {
    $asset = Asset::factory()->unassigned()->create();
    $client = Client::factory()->create();

    actingAsAdmin()
        ->postJson(route('assets.assign', $asset), ['client_id' => $client->id])
        ->assertOk();

    expect($asset->fresh()->client_id)->toBe($client->id);
});

it('detects conflict when asset already assigned', function () {
    $asset = Asset::factory()->assigned()->create();
    actingAsAdmin()
        ->postJson(route('assets.assign', $asset), ['client_id' => 99])
        ->assertStatus(409);
});
```

**Estimated lines covered:** +820

### 3E — Alerts Service + Module (target: 78%)

**File:** `tests/Integration/Alerts/AlertServiceTest.php`

Expand on the 4 existing Alerts tests. `AlertService` (108-line gap, 44%) is the key target.

- `AlertService::dispatch()` — assert notification dispatched per subscriber
- `AlertService::subscribe()` / `unsubscribe()` — DB state transitions
- `AlertService::throttle()` — assert duplicate alerts suppressed within window
- `AlertTypeSeeder` — seed and assert all alert types exist

**Estimated lines covered:** +577

### 3F — app/Http Controllers (target: 72%)

**File:** `tests/Feature/Admin/AppHttpControllersTest.php` (split as needed)

Current gap: 3,302 lines at 53.5%

Key targets:
- `ModulesController` (1,003-line gap, 19%) — module enable/disable, status checks, manifest listing
- `ResilienceController` (533-line gap, 38%) — resilience dashboard data, latency metrics
- `AnalyticsController` (141-line gap, 0%) — analytics data endpoints
- `SettingsController` (133-line gap, 69%) — remaining settings actions
- `SystemController` (253-line gap, 60%) — system info, health checks

```php
it('admin can enable a module', function () {
    actingAsAdmin()
        ->postJson(route('modules.enable', 'SoftwareSubscriptions'))
        ->assertOk();
    expect(module('SoftwareSubscriptions')->isEnabled())->toBeTrue();
});

it('modules controller returns 403 for non-admin', function () {
    actingAsUser()
        ->get(route('modules.index'))
        ->assertStatus(403);
});
```

**Estimated lines covered:** +1,302

### 3G — app/Console Commands (target: 70%)

**File:** `tests/Integration/Console/AppConsoleCommandsCoverageTest.php`

Expand on existing `ConsoleCommandsComprehensivePestTest.php`. Key gaps:
- `TestCommand` (161-line gap, 0%) — note: likely a dev-only command; may be `@codeCoverageIgnore` candidate
- `AnalyzeTests` (124-line gap, 0%) — AST analysis command; test output format
- `RecordTestFailures` (120-line gap, 0%) — test failure recording; assert DB records

```php
it('record-test-failures stores failures in database', function () {
    artisan('test:record-failures', ['--run-id' => 'test-run-123'])
        ->assertExitCode(0);
    expect(TestFailure::where('run_id', 'test-run-123')->count())->toBeGreaterThan(0);
});
```

**Estimated lines covered:** +454

---

## Phase 3 Estimated Coverage Gain

| Test File | Est. Lines Covered |
|:----------|-------------------:|
| 3A Action1 Service | +900 |
| 3B Action1 Controllers/Console | +850 |
| 3C GoogleAdmin Service/Controllers | +870 |
| 3D AssetManagement | +820 |
| 3E Alerts Service | +577 |
| 3F app/Http Controllers | +1,302 |
| 3G app/Console Commands | +454 |
| **Total** | **+5,773** |

> Running total: Phase 2 end (~32,537) + 5,773 = **38,310 / 47,606 = 80.5% ✅ TARGET MET**

---

## Acceptance Criteria

- [ ] All 7 test files exist and pass
- [ ] `Action1` coverage ≥ 65%
- [ ] `GoogleAdmin` coverage ≥ 70%
- [ ] `AssetManagement` coverage ≥ 65%
- [ ] Full test suite green
- [ ] **Coverage ≥ 80%** (primary success gate for the entire uplift program)
- [ ] Tier 2 MSI ≥ 95
- [ ] Infection full-run MSI ≥ 95 (update `infection.json5` minMsi from 95 → 95, no change needed)
- [ ] Commit: `test: Phase 3 — Action1, GoogleAdmin, AssetManagement, app/Http (80% milestone)`

---

## Notes

- `Action1Service` makes HTTP calls — prefer `Http::fake()` over class mocking as it allows exercising the full retry/error-handling path natually.
- `GoogleWorkspaceService` wraps the Google API Client library — mock at the `GoogleDirectoryClient` abstraction layer, not the raw Google library.
- `ModulesController` interacts with `modules_statuses.json` — use the `WithFakeModule` test helper or a temp file approach to avoid polluting real state.
- `TestCommand` is likely a dev scaffolding command. If it has no production use, add `// @codeCoverageIgnore` on the class declaration and exclude it from the gap calculation.
