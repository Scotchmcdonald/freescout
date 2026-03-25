# Phase 2 — ClientPortal, DeploymentManager, app/Actions, DTOs, Widgets

**Coverage target after phase:** ~74.4% (+4,200 lines from Phase 1 end)
**Why this grouping:** These are mid-size, isolated namespaces with clear testable patterns. ClientPortal is the second-largest "nearly empty" module (12.4%) and has straightforward controller+service patterns.

---

## Namespace Targets

| Namespace | Current % | Target % | Gap | New Lines |
|:----------|----------:|--------:|----:|----------:|
| `Modules/ClientPortal` | 12.4% | 65% | 3,769 | +2,260 |
| `Modules/DeploymentManager` | 18.8% | 65% | 1,293 | +733 |
| `app/Actions` | 20.6% | 80% | 443 | +354 |
| `app/DataTransferObjects` | 13.5% | 85% | 358 | +296 |
| `app/Widgets` | 41.4% | 80% | 243 | +145 |
| `Modules/DevFeedback` | 14.0% | 60% | 313 | +188 |
| **Total** | | | **6,419** | **~3,976** |

---

## Test File Plans

### 2A — ClientPortal Controllers (target: 65%)

**File:** `tests/Feature/ClientPortal/ControllersTest.php`

Classes:
- `PortalController` (179-line gap, 13.1% covered) — dashboard, profile, asset tabs
- `SupportController` (157-line gap, 0%) — ticket list, create, reply
- `ClientPaymentController` (126-line gap, 23%) — payment method CRUD, setup intent
- `BillingController` — invoice list, filter
- `ApprovalController` — approval list, approve/reject actions
- `InvoiceController` — invoice download, PDF view
- `UserProvisioningController` — SSO provisioning

**Key patterns:**
```php
// Authenticate as portal client (specific auth guard)
it('portal client can view their dashboard', function () {
    $client = ClientUser::factory()->create();
    actingAs($client, 'client')
        ->get(route('portal.dashboard'))
        ->assertOk();
});

it('unauthenticated request redirects to portal login', function () {
    get(route('portal.dashboard'))
        ->assertRedirectToRoute('portal.login');
});

it('client cannot access another client invoices', function () {
    $client1 = ClientUser::factory()->create();
    $client2 = ClientUser::factory()->create();
    $invoice = Invoice::factory()->for($client2->customer)->create();
    actingAs($client1, 'client')
        ->get(route('portal.invoices.show', $invoice))
        ->assertStatus(403);
});
```

**Estimated lines covered:** ~1,400

### 2B — ClientPortal Middleware + Service (target: 75%)

**File:** `tests/Unit/ClientPortal/MiddlewareAndServiceTest.php`

- `AuthenticateClient` middleware — assert redirect/pass-through logic
- `EnsureClientIsActive` middleware — assert blocked/active states
- `PortalTabRegistry` service — tab registration, ordering, rendering key assertions

**Estimated lines covered:** ~250

### 2C — DeploymentManager (target: 65%)

**File:** `tests/Integration/DeploymentManager/DeploymentManagerTest.php`

Classes:
- `DeploymentController` — list deployments, create, status update
- `ActivationController` (19% covered) — activate/deactivate module flows
- `ActivationBrokerController` — API activation broker endpoints
- `ActivationService` — activation logic, mock Git provider
- `GitProviderService` — mock GitHub/GitLab API, assert repo access check
- `DeployedModule`, `DeploymentActivation`, `DeploymentRecord` models — factory coverage

**Estimated lines covered:** ~850

```php
it('records a new deployment on create', function () {
    $deployment = DeploymentRecord::factory()->create([
        'module' => 'PIB',
        'version' => '1.2.3',
        'deployed_by' => 'agent',
    ]);
    expect($deployment->module)->toBe('PIB');
    expect($deployment->version)->toBe('1.2.3');
});

it('activates a module and updates module status', function () {
    $service = app(ActivationService::class);
    $activation = $service->activate('SoftwareSubscriptions', '1.0.0');
    expect($activation->status)->toBe('active');
});
```

### 2D — app/Actions (target: 80%)

**File:** `tests/Unit/Actions/AppActionsTest.php`

All action classes in `app/Actions/` — these are typically single-purpose command objects with `execute()` or `handle()` methods. Pure unit tests with minimal mocking:

```bash
# Find all action classes
find app/Actions -name '*.php' | sort
```

**Patterns:**
```php
it('dispute invoice action creates a dispute record', function () {
    $invoice = Invoice::factory()->create(['status' => 'issued']);
    $action = app(DisputeInvoiceAction::class);
    $result = $action->execute($invoice, reason: 'Incorrect amount');
    expect($result->status)->toBe('disputed');
    expect($result->dispute_reason)->toBe('Incorrect amount');
});
```

**Estimated lines covered:** ~354

### 2E — app/DataTransferObjects (target: 85%)

**File:** `tests/Unit/DataTransferObjects/DtosTest.php`

All DTO classes — construction, property access, validation, `fromArray()` / `toArray()` round-trips. Pure unit tests (no DB, no service container).

**Estimated lines covered:** ~296

```php
it('creates a CustomerData DTO from array', function () {
    $dto = CustomerData::fromArray([
        'name' => 'Acme Corp',
        'email' => 'admin@acme.com',
        'tier' => 'enterprise',
    ]);
    expect($dto->name)->toBe('Acme Corp');
    expect($dto->tier)->toBe(Tier::Enterprise);
});
```

### 2F — app/Widgets (target: 80%)

**File:** `tests/Unit/Widgets/WidgetsCoverageTest.php`

All widget classes in `app/Widgets/` — data method return shapes, permission checks, placeholder values. Expand on existing `DashboardWidgetsTest.php` if it doesn't already cover these.

**Estimated lines covered:** ~145

### 2G — Modules/DevFeedback (target: 60%)

**File:** `tests/Integration/DevFeedback/DevFeedbackTest.php`

- Feedback model factory coverage
- Feedback submission endpoint
- Admin listing + status update

**Estimated lines covered:** ~188

---

## Phase 2 Estimated Coverage Gain

| Test File | Est. Lines Covered |
|:----------|-------------------:|
| 2A ClientPortal Controllers | +1,400 |
| 2B ClientPortal Middleware/Service | +250 |
| 2C DeploymentManager | +850 |
| 2D app/Actions | +354 |
| 2E app/DataTransferObjects | +296 |
| 2F app/Widgets | +145 |
| 2G DevFeedback | +188 |
| **Total** | **+3,483** |

> This is conservative. Running total: Phase 1 end (~29,054) + 3,483 = **32,537 / 47,606 = 68.4%**

---

## Acceptance Criteria

- [ ] All 7 test files exist and pass
- [ ] `ClientPortal` coverage ≥ 60%
- [ ] `DeploymentManager` coverage ≥ 60%
- [ ] Full test suite green (`php artisan test --parallel --processes=10`)
- [ ] Coverage ≥ 68% (verify via coverage-xml script)
- [ ] Tier 2 MSI ≥ 95 (run `bash scripts/ci/check-mutation-tier2.sh`)
- [ ] Commit: `test: Phase 2 — ClientPortal, DeploymentManager, app layer`

---

## Notes

- `ClientPortal` uses a **separate auth guard** (`client` guard). Tests must `actingAs($user, 'client')` consistently.
- `DeploymentManager` may require real `module_statuses.json` fixture; create a test helper that sets up module state cleanly.
- `app/Actions` are pipeline-style classes — stub downstream services (billing engine, etc.) rather than testing the full chain here (that's integration test territory).
