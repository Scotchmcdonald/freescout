# Phase 4 — Crm, SoftwareSubscriptions, ContractManager, PIB, KnowledgeBase (Stretch)

**Coverage target after phase:** ~90%+
**Status:** STRETCH — begin after Phase 3 validates ≥ 80%. This phase pushes the ceiling toward excellence.

---

## Context

After Phase 3, the programme has achieved the **80% line coverage / Infection best-practices threshold**. Phase 4 covers the remaining large modules that are already partially tested but have significant gaps:

| Namespace | Current % | Existing Tests | Target % | Additional Lines |
|:----------|----------:|:--------------:|--------:|------------------:|
| `Modules/Crm` | 43.1% | 16 files | 75% | +2,230 |
| `Modules/SoftwareSubscriptions` | 44.6% | 19 files | 75% | +2,024 |
| `Modules/ContractManager` | 48.8% | 21 files | 75% | +1,680 |
| `Modules/PIB` | 45.0% | 24 files | 75% | +3,453 |
| `Modules/KnowledgeBase` | 56.3% | 8 files | 80% | +1,325 |
| **Total** | | | | **~10,712** |

---

## 4A — Crm (43% → 75%)

**Expand:** `Modules/Crm/Tests/` — add files alongside existing 16

Key gaps:
- `CrmController` (545-line gap, 0%) — this is the largest non-tested controller in the codebase
  - Client list, filter by tier/status, search
  - Client CRUD (create, update, archive)
  - Contact management endpoints
- `CrmServiceProvider` (432-line gap, 30%) — event listener bindings, route registration assertions
- `CustomerField` entity (147-line gap, 3%) — field type casting, validation rules
- `CreateCustomerAction` — test through the action's `execute()` contract

**New test files:**
- `Modules/Crm/Tests/Feature/CrmControllerTest.php`
- `Modules/Crm/Tests/Unit/CrmEntitiesTest.php`

```php
it('creates a new client via CRM controller', function () {
    actingAsAdmin()
        ->postJson(route('crm.clients.store'), [
            'name' => 'Acme Corp',
            'email' => 'billing@acme.com',
            'tier' => 'enterprise',
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Acme Corp');
});

it('archives a client and fires ClientArchived event', function () {
    Event::fake(ClientArchived::class);
    $client = Client::factory()->active()->create();
    actingAsAdmin()
        ->deleteJson(route('crm.clients.destroy', $client))
        ->assertNoContent();
    Event::assertDispatched(ClientArchived::class);
});
```

**Estimated lines covered:** +2,230

---

## 4B — SoftwareSubscriptions (45% → 75%)

**Expand:** `Modules/SoftwareSubscriptions/Tests/` — add alongside 19 existing files

Key gaps:
- `SoftwareSubscriptionsController` (360-line gap, 3%) — admin panel CRUD for subscriptions, assign/unassign
- `SoftwareAssignmentController` (142-line gap, 0%) — API assignment endpoints
- `SoftwareProductEntitlementResolver` (134-line gap, 0%) — entitlement resolution logic
- `SoftwareSubscriptionsDatabaseSeeder` (154-line gap, 0%) — seed + assert products exist

**New test files:**
- `Modules/SoftwareSubscriptions/Tests/Feature/AdminControllerTest.php`
- `Modules/SoftwareSubscriptions/Tests/Feature/ApiControllerTest.php`
- `Modules/SoftwareSubscriptions/Tests/Unit/EntitlementResolverTest.php`

```php
it('resolves entitlement for an active subscription', function () {
    $subscription = SoftwareSubscription::factory()->active()->create(['seats' => 10]);
    $resolver = app(SoftwareProductEntitlementResolver::class);
    $result = $resolver->resolve($subscription->client_id, $subscription->product_id);
    expect($result->hasAccess())->toBeTrue();
    expect($result->seatsAvailable())->toBe(10);
});

it('returns no access when subscription is expired', function () {
    $subscription = SoftwareSubscription::factory()->expired()->create();
    $resolver = app(SoftwareProductEntitlementResolver::class);
    $result = $resolver->resolve($subscription->client_id, $subscription->product_id);
    expect($result->hasAccess())->toBeFalse();
});
```

**Estimated lines covered:** +2,024

---

## 4C — ContractManager (49% → 75%)

**Expand:** `Modules/ContractManager/Tests/` — add alongside 21 existing files

Key gaps:
- `ContractController` (151-line gap, 38%) — contract CRUD, PDF generation
- `MilestoneController` (166-line gap, 0%) — milestone CRUD, status transitions
- `ContractService` (135-line gap, 43%) — contract validation, auto-renewal logic

**New test files:**
- `Modules/ContractManager/Tests/Feature/MilestoneControllerTest.php`
- `Modules/ContractManager/Tests/Integration/ContractServiceTest.php`

```php
it('creates a milestone for a contract', function () {
    $contract = Contract::factory()->active()->create();
    actingAsAdmin()
        ->postJson(route('contracts.milestones.store', $contract), [
            'title' => 'Q1 Delivery',
            'due_date' => now()->addMonths(3)->toDateString(),
            'amount' => 5000,
        ])
        ->assertCreated();
    expect($contract->milestones()->count())->toBe(1);
});

it('auto-renews a contract at expiry', function () {
    $contract = Contract::factory()->expiringSoon()->create(['auto_renew' => true]);
    $service = app(ContractService::class);
    $renewed = $service->processExpiry($contract);
    expect($renewed->status)->toBe('active');
    expect($renewed->end_date)->toBeGreaterThan($contract->end_date);
});
```

**Estimated lines covered:** +1,680

---

## 4D — PIB (45% → 75%)

**Expand:** `Modules/PIB/Tests/` — add alongside existing 24 files

Key gaps:
- `InvoiceController` (190-line gap, 40%) — invoice create, preview, send, void
- `BillingAdjustmentController` (110-line gap, 10%) — adjustment CRUD, credit application
- PIB models with low coverage — `BillingCycle`, `EntitlementPeriod`, etc.

**New test files:**
- `Modules/PIB/Tests/Feature/InvoiceControllerExpansionTest.php`
- `Modules/PIB/Tests/Feature/BillingAdjustmentControllerTest.php`
- `Modules/PIB/Tests/Integration/PibModelsTest.php`

```php
it('voids an invoice and creates a credit note', function () {
    $invoice = Invoice::factory()->issued()->create();
    actingAsAdmin()
        ->postJson(route('pib.invoices.void', $invoice), ['reason' => 'Duplicate'])
        ->assertOk();
    expect($invoice->fresh()->status)->toBe('voided');
    expect(CreditNote::whereInvoiceId($invoice->id)->exists())->toBeTrue();
});
```

**Estimated lines covered:** +3,453

---

## 4E — KnowledgeBase (56% → 80%)

**Expand:** `Modules/KnowledgeBase/Tests/`

Key gaps:
- `KnowledgeBaseController` (119-line gap, 37%)
- `DemoAccountService` (113-line gap, 0%)

**New test files:**
- `Modules/KnowledgeBase/Tests/Feature/KbControllerExpansionTest.php`
- `Modules/KnowledgeBase/Tests/Unit/DemoAccountServiceTest.php`

**Estimated lines covered:** +1,325

---

## Phase 4 Summary

| Test File Group | Estimated Lines |
|:----------------|----------------:|
| 4A Crm | +2,230 |
| 4B SoftwareSubscriptions | +2,024 |
| 4C ContractManager | +1,680 |
| 4D PIB | +3,453 |
| 4E KnowledgeBase | +1,325 |
| **Total** | **+10,712** |

> Running total: Phase 3 end (~38,310) + 10,712 = **49,022 / 47,606 = ~103%** ← actual will be capped at 100%; this estimate is conservative in coverage gain; real gain is ~8,000 net new lines.
> **Expected actual: ~46,310 / 47,606 ≈ 97%**

---

## Acceptance Criteria

- [ ] All phase 4 test files exist and pass
- [ ] `Modules/Crm` ≥ 70%, `Modules/PIB` ≥ 70%, `Modules/ContractManager` ≥ 70%
- [ ] Global coverage ≥ 85%
- [ ] Tier 2 MSI ≥ 95 (verify after full coverage update)
- [ ] Consider raising `infection.json5` `minMsi` from 95 → 97 given expanded coverage
- [ ] Commit: `test: Phase 4 — Crm, SoftwareSubscriptions, ContractManager, PIB, KnowledgeBase (stretch)`

---

## Notes

- PIB's `InvoiceController` has financial-impact mutations — every branch should be tested with positive + negative assertion pairs.
- Once Phase 4 is complete, re-run `scripts/testing/generate-strict-types-inventory.sh` and `scripts/ci/check-strict-types.sh` to verify no new files were added without declarations.
- After Phase 4, raise the `minMsi` and `minCoveredMsi` in `infection.json5` to **97** to reflect the new baseline.
