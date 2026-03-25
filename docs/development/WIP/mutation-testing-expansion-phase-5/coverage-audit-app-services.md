# App/Services Coverage Audit — Week 3

**Audit Date:** March 25, 2026 (Phase 5, Week 3)  
**Scope:** `app/Services/*.php` (16 service files)  
**Coverage Target:** ≥60% line coverage to support Tier 2 mutation testing  
**Current Baseline:** ~35% (estimated based on Phase 5 audit)

---

## Services Inventory

### Services with Existing Tests (Unit + Integration)

| Service | Unit Tests | Integration Tests | Status |
| :--- | :--- | :--- | :--- |
| `AtomicCounterService` | ✅ `CacheServiceTest.php` | ✅ `AtomicCounterServiceTest.php` | Well-tested |
| `CircuitBreakerService` | ✅ `CircuitBreakerServiceTest.php` | ✅ `CircuitBreakerServiceTest.php` | Well-tested |
| `RateLimiterService` | ✅ `RateLimiterServiceTest.php` | ✅ `AppRateLimiterServiceTest.php` | Well-tested |
| `SmtpService` | ✅ `SmtpServiceComprehensiveTest.php` | ✅ `SmtpServiceTest.php` | Well-tested |
| `ImapService` | ✅ `ImapServicePureLogicTest.php` | ✅ `ImapServiceTest.php` (3 files) | Well-tested |
| `ModuleSourceService` | ✅ `ModuleSourceServiceTest.php` | ✅ (via integration) | Well-tested |
| `WidgetRegistryService` | ✅ `WidgetRegistryServiceTest.php` | ✅ (via integration) | Well-tested |

### Services with Partial Coverage

| Service | Existing Tests | Status | Action Required | Priority |
| :--- | :--- | :--- | :--- | :--- |
| `CacheService` | ✅ `CacheServiceTest.php` (Unit) | ~50%+ | Add integration tests (caching edge cases) | MEDIUM |
| `AuditLogService` | ❓ Unknown | ~40% (est) | Add unit + integration tests | HIGH |
| `EntitlementEngine` | ❓ Unknown | ~35% (est) | Add unit + integration tests | HIGH |
| `MetricsService` | ❌ None | ~10% (est) | Create from scratch | MEDIUM |

### Services with Minimal/No Coverage

| Service | Tests Found | Status | Action Required | Priority |
| :--- | :--- | :--- | :--- | :--- |
| `CachedMailboxService` | ❌ None | 0% (est) | Create unit + integration | MEDIUM |
| `UserDirectoryRegistryService` | ❌ None | 0% (est) | Create unit + integration | MEDIUM |
| `Navigation/NavigationService` | ❌ None | 0% (est) | Create unit + integration | MEDIUM |
| `Ui/WidgetRegistryService` | ✅ (Partial) | ~60% | Complete coverage | LOW |
| `SentryBeforeBreadcrumb` | ❌ None | 0% (est) | Create lightweight tests | MEDIUM |
| `SentryBeforeSend` | ❌ None | 0% (est) | Create lightweight tests | MEDIUM |

---

## Recommended Actions for Week 3

### Priority 1 - High Impact Services (2–3 should be targeted)

#### 1.1 AuditLogService
**Why:** Critical for compliance/audit trails.  
**Effort:** 4–5 hours (2 unit tests + 3 integration tests)  
**Target Tests:**
```php
// tests/Unit/Services/AuditLogServiceTest.php
test('logs action with proper actor attribution', function () {
    $service = new AuditLogService($logFactory);
    $actor = User::factory()->create();
    
    $service->log('user.invited', ['email' => 'test@example.com'], $actor);
    
    expect(LogEntry::where('action', 'user.invited')->count())->toBe(1);
});

test('sanitizes sensitive fields before logging', function () {
    $service = new AuditLogService($logFactory);
    
    $service->log('password.changed', ['password' => 'secret123']);
    
    expect(LogEntry::first()->metadata)->not->toContain('secret123');
});

// tests/Integration/Services/AuditLogServiceTest.php
test('audit logs survive transaction rollback', function () {
    DB::transaction(function () {
        $service = app(AuditLogService::class);
        $service->log('test', []);
        
        throw new Exception('Rollback');
    });
    
    // Audit log should still exist
    expect(LogEntry::count())->toBeGreaterThan(0);
});
```

#### 1.2 EntitlementEngine
**Why:** Core business logic (usage limits, entitlements).  
**Effort:** 6–8 hours (4 unit tests + 4 integration tests)  
**Target Tests:**
```php
// tests/Unit/Services/EntitlementEngineTest.php
test('correctly calculates remaining usage', function () {
    $engine = new EntitlementEngine($repo);
    $contract = Contract::factory()->create(['usage_limit' => 1000]);
    
    $engine->accrueUsage($contract, 350);
    $remaining = $engine->getRemainingUsage($contract);
    
    expect($remaining)->toBe(650);
});

test('enforces hard limits on usage', function () {
    $engine = new EntitlementEngine($repo);
    $contract = Contract::factory()->create(['usage_limit' => 100]);
    
    $result = $engine->accrueUsage($contract, 110);
    
    expect($result->isValid())->toBeFalse();
    expect($result->error())->toContain('limit exceeded');
});

// tests/Integration/Services/EntitlementEngineTest.php
test('rollovers reset monthly usage counters', function () {
    $engine = app(EntitlementEngine::class);
    $contract = Contract::factory()->create(['rollover_day' => 1]);
    
    // Accrue usage this month
    $engine->accrueUsage($contract, 500);
    expect($contract->usage_accrued)->toBe(500);
    
    // Simulate month rollover
    $engine->processMonthlyRollover($contract);
    
    // Counter should reset
    expect(contract.fresh().usage_accrued)->toBe(0);
});
```

#### 1.3 MetricsService
**Why:** Observability/monitoring critical for production.  
**Effort:** 3–4 hours (3 unit tests + 2 integration tests)  
**Target Tests:**
```php
// tests/Unit/Services/MetricsServiceTest.php
test('collects and aggregates metrics by dimension', function () {
    $service = new MetricsService($storage);
    
    $service->record('api.latency', 150, ['endpoint' => 'users']);
    $service->record('api.latency', 200, ['endpoint' => 'users']);
    $service->record('api.latency', 100, ['endpoint' => 'posts']);
    
    $aggregate = $service->aggregate('api.latency', ['endpoint' => 'users']);
    expect($aggregate['mean'])->toBe(175);
});

test('respects metric retention policies', function () {
    $service = new MetricsService($storage);
    
    $service->record('temp_metric', 100);
    $service->retentionPolicy('temp_metric', maxAge: '5 minutes');
    
    sleep(6); // Simulate 6 minutes
    
    expect($service->get('temp_metric'))->toBeNull();
});
```

---

### Priority 2 - Medium-Complexity Services (optional, if time permits)

- **CachedMailboxService**: Similar pattern to **ImapService** + caching logic.
- **UserDirectoryRegistryService**: Registry pattern (can follow **WidgetRegistryService** template).

---

## Test Template: Unit Service Test

```php
// tests/Unit/Services/NewServiceTest.php

<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\AuditLogService;
use Pest\TestCase;

describe('AuditLogService', function () {
    beforeEach(function () {
        // Setup mocks/fakes
        $this->service = new AuditLogService(fake_factory());
    });

    test('logs action with actor attribution', function () {
        // Arrange
        $actor = factory(User::class)->create();
        
        // Act
        $this->service->log('action', [], $actor);
        
        // Assert
        expect(LogEntry::count())->toBe(1);
        expect(LogEntry::first()->actor_id)->toBe($actor->id);
    });

    test('handles missing optional parameters', function () {
        expect(fn () => $this->service->log('action'))
            ->not()->toThrow(Exception::class);
    });
});
```

---

## Test Template: Integration Service Test

```php
// tests/Integration/Services/NewServiceIntegrationTest.php

<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use Tests\IntegrationTestCase;

describe('AuditLogService Integration', function () {
    test('persists logs to database', function () {
        $service = app(AuditLogService::class);
        
        $service->log('user.created', ['user_id' => 1]);
        
        expect(LogEntry::count())->toBe(1);
    });

    test('survives database transactions', function () {
        DB::transaction(function () {
            $service = app(AuditLogService::class);
            $service->log('test');
        });
        
        expect(LogEntry::count())->toBe(1);
    });
});
```

---

## Success Metrics (End of Week 3)

| Metric | Target | Success Criteria |
| :--- | ---: | :--- |
| New unit tests added | 5–8 | Covering 3+ services |
| New integration tests added | 4–6 | Covering 2+ services |
| app/Services coverage increase | 35% → 50%+ | Measurable improvement |
| Tier 2 mutation impact | Reduced escapes | Fewer unmutated lines |

---

## Timeline

| Day | Task | Effort |
| :--- | :--- | ---: |
| (Day 1) | Audit document + plan (THIS) | 1 hr |
| (Days 2-5) | Write 3 priority services tests | 8–12 hrs |
| (Day 6-7) | Review + iterate on escape mutants | 4–6 hrs |
| (Day 8) | Finalization + coverage report | 2 hrs |

---

## Reference Commands

### Run Tests for Single Service
```bash
./vendor/bin/pest tests/Unit/Services/AuditLogServiceTest.php
./vendor/bin/pest tests/Integration/Services/AuditLogServiceTest.php
```

### Check Coverage for app/Services
```bash
XDEBUG_MODE=coverage php -d memory_limit=3G ./vendor/bin/pest \
    --coverage-filter=app/Services \
    --coverage-xml=storage/infection/coverage
```

### Mutation Testing on Specific Service
```bash
./vendor/bin/infection \
    --configuration=infection-extended.json5 \
    --filter="app/Services/AuditLogService" \
    --threads=4
```

