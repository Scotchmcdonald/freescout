# Architecture Implementation Guide
**Version:** 1.1  
**Date:** February 9, 2026  
**Status:** Partially Completed - See Status Notes Below  
**Audience:** Development Team  

---

## ⚠️ Implementation Status Note

**Many items in this guide have been completed as of February 9, 2026.**

For historical reference of completed work, see:
- `docs/ARCHIVE/QUICK_WINS_ACTION_PLAN_COMPLETED_2026-02-09.md` - Most quick wins completed
- `docs/ARCHIVE/ARCHITECTURAL_BEST_PRACTICES_REVIEW_2026-02-08.md` - Initial assessment

**Completed Items (✅):**
- P0.1: Queue Isolation Fix - COMPLETE
- P0.2: Transaction Boundaries Pattern - DOCUMENTED
- P1.1: Missing Event Listeners - COMPLETE
- P1.2: Caching Strategy - COMPLETE
- P1.3: Observability Stack - COMPLETE
- P1.4: API Versioning - DOCUMENTED
- P2.4: Authentication Rate Limiting - COMPLETE
- P2.5: Centralized Audit Log - COMPLETE
- P2.6: API Authentication Strategy - DOCUMENTED
- P2.8: Transaction Management Verification - COMPLETE

**Remaining Items (⏳):**
- P2.1: Interface Segregation - PLANNED
- P2.2: Cache Invalidation Tests - PLANNED  
- P2.3: Architecture Test Guards Enhancement - PLANNED
- P2.7: Centralized Error Tracking (Sentry) - INFRASTRUCTURE READY

**This guide remains useful for:**
- Reference implementation patterns for future work
- Onboarding new developers on architecture decisions
- Understanding the "why" behind current patterns

---

## Table of Contents

1. [Overview](#overview)
2. [Prerequisites](#prerequisites)
3. [P0: Critical Implementations](#p0-critical-implementations)
   - [1.1 Queue Isolation Fix](#11-queue-isolation-fix)
   - [1.2 Transaction Boundaries Pattern](#12-transaction-boundaries-pattern)
4. [P1: High Priority Implementations](#p1-high-priority-implementations)
   - [2.1 Missing Event Listeners](#21-missing-event-listeners)
   - [2.2 Caching Strategy](#22-caching-strategy)
   - [2.3 Observability Stack](#23-observability-stack)
   - [2.4 API Versioning](#24-api-versioning)
5. [P2: Code Quality Improvements](#p2-code-quality-improvements)
   - [3.1 Interface Segregation](#31-interface-segregation)
   - [3.2 Cache Invalidation Tests](#32-cache-invalidation-tests)
   - [3.3 Architecture Test Guards](#33-architecture-test-guards)
   - [3.4 Authentication Rate Limiting](#34-authentication-rate-limiting)
   - [3.5 Centralized Audit Log](#35-centralized-audit-log)
   - [3.6 API Authentication Strategy](#36-api-authentication-strategy)
   - [3.7 Centralized Error Tracking](#37-centralized-error-tracking)
   - [3.8 Transaction Management Verification](#38-transaction-management-verification)
6. [Testing & Verification](#testing--verification)
7. [Deployment Procedures](#deployment-procedures)
8. [Troubleshooting](#troubleshooting)
9. [Rollback Procedures](#rollback-procedures)

---

## Overview

This guide provides **complete, step-by-step implementation instructions** for addressing critical architecture gaps identified in the comprehensive best practices review (Feb 8, 2026).

**Implementation Priority:**
- **P0 (Critical):** Must complete this week - prevents production incidents
- **P1 (High):** Complete in next sprint - closes major architecture gaps
- **P2 (Quality):** Complete in next quarter - improves maintainability

**Estimated Total Effort:** 40-60 developer hours (1-2 sprints)

---

## Prerequisites

Before starting any implementation:

```bash
# 1. Verify you're on the latest develop branch
git checkout develop
git pull origin develop

# 2. Ensure all dependencies are up to date
composer install
npm install

# 3. Verify tests pass
php artisan test

# 4. Create feature branch
git checkout -b feature/architecture-improvements-$(date +%Y%m%d)
```

**Required Access:**
- Write access to repository
- Access to staging environment
- Access to production deployment pipeline
- Sentry account credentials (for P1.3)

---

## P0: Critical Implementations

### 1.1 Queue Isolation Fix

**⏱️ Estimated Time:** 1-2 hours  
**🎯 Goal:** Prevent bulk billing operations from blocking system notifications  
**⚠️ Impact:** HIGH - Currently bulk invoice generation (10,000 invoices) delays password resets by hours

#### Problem Statement

Current state:
```php
// All jobs use default queue
GenerateInvoiceJob::dispatch($template); // Blocks default queue
ApplyProrationJob::dispatch($client);    // Blocks default queue
```

During bulk billing cycles, critical system notifications (password resets, 2FA codes) are delayed because they're queued behind thousands of billing jobs.

#### Step-by-Step Implementation

**Step 1: Identify All PIB Jobs**

```bash
# Find all jobs in PIB module
find Modules/PIB/Jobs -name "*.php" | tee pib_jobs.txt

# Expected output:
# Modules/PIB/Jobs/GenerateInvoiceJob.php
# Modules/PIB/Jobs/ApplyProrationJob.php
# Modules/PIB/Jobs/RecalculateBillingJob.php
# Modules/PIB/Jobs/SyncCreditBalanceJob.php
```

**Step 2: Update Each Job File**

For **EACH** file listed above, apply this pattern:

```php
// Example: Modules/PIB/Jobs/GenerateInvoiceJob.php

namespace Modules\PIB\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // ADD THIS LINE:
    public string $queue = 'billing';
    
    // OR in constructor:
    public function __construct(
        public InvoiceTemplate $template,
        public int $clientId
    ) {
        // Set queue explicitly
        $this->onQueue('billing');
    }

    public function handle(): void
    {
        // Existing implementation
    }
}
```

**Step 3: Verify All Jobs Updated**

```bash
# Check all PIB jobs have queue assignment
for file in $(find Modules/PIB/Jobs -name "*.php"); do
    if grep -q "onQueue\|public.*\$queue" "$file"; then
        echo "✅ $file"
    else
        echo "❌ MISSING: $file"
    fi
done
```

**Expected output:**
```
✅ Modules/PIB/Jobs/GenerateInvoiceJob.php
✅ Modules/PIB/Jobs/ApplyProrationJob.php
✅ Modules/PIB/Jobs/RecalculateBillingJob.php
✅ Modules/PIB/Jobs/SyncCreditBalanceJob.php
```

**Step 4: Update Queue Worker Configuration**

Edit `deployment/freescout-worker.conf.example`:

```ini
[program:freescout-worker-default]
command=php /var/www/html/artisan queue:work --queue=default --sleep=3 --tries=3 --max-time=3600
process_name=%(program_name)s_%(process_num)02d
numprocs=5
autostart=true
autorestart=true
user=www-data

[program:freescout-worker-billing]
command=php /var/www/html/artisan queue:work --queue=billing --sleep=3 --tries=3 --max-time=3600
process_name=%(program_name)s_%(process_num)02d
numprocs=10
autostart=true
autorestart=true
user=www-data
```

**Step 5: Write Tests**

Create `tests/Feature/QueueIsolationTest.php`:

```php
<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Modules\PIB\Jobs\GenerateInvoiceJob;
use Modules\PIB\Jobs\ApplyProrationJob;
use Tests\TestCase;

class QueueIsolationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function billing_jobs_use_dedicated_queue()
    {
        Queue::fake();

        $template = InvoiceTemplate::factory()->create();
        
        GenerateInvoiceJob::dispatch($template);
        
        Queue::assertPushedOn('billing', GenerateInvoiceJob::class);
    }

    /** @test */
    public function proration_jobs_use_billing_queue()
    {
        Queue::fake();

        $client = Client::factory()->create();
        
        ApplyProrationJob::dispatch($client);
        
        Queue::assertPushedOn('billing', ApplyProrationJob::class);
    }
}
```

Run tests:
```bash
php artisan test --filter=QueueIsolationTest
```

**Step 6: Deploy to Staging**

```bash
# Commit changes
git add Modules/PIB/Jobs/
git add tests/Feature/QueueIsolationTest.php
git add deployment/freescout-worker.conf.example
git commit -m "feat(pib): Isolate billing jobs to dedicated queue

- Move all PIB jobs to 'billing' queue
- Prevent bulk billing from blocking system notifications
- Add supervisor config for dedicated billing workers
- Add tests for queue isolation

Fixes: ARCH-001"

# Push to staging
git push origin feature/architecture-improvements-$(date +%Y%m%d)

# Deploy to staging (follow your team's process)
```

**Step 7: Verify in Staging**

```bash
# SSH to staging server
ssh staging.yourapp.com

# Check supervisor status
sudo supervisorctl status

# Expected output:
# freescout-worker-default:freescout-worker-default_00   RUNNING
# freescout-worker-default:freescout-worker-default_01   RUNNING
# freescout-worker-billing:freescout-worker-billing_00   RUNNING
# freescout-worker-billing:freescout-worker-billing_01   RUNNING

# Monitor queues
php artisan queue:monitor billing,default

# Trigger test invoice generation
php artisan tinker
>>> GenerateInvoiceJob::dispatch($template);
>>> \App\Jobs\SendPasswordResetNotification::dispatch($user);

# Verify in Laravel Horizon or logs
tail -f storage/logs/laravel.log | grep queue
```

**Step 8: Production Deployment**

See [Deployment Procedures](#deployment-procedures) section.

#### Success Criteria

✅ All PIB jobs dispatch to `billing` queue  
✅ System notifications remain fast (<1 min) during bulk billing  
✅ Tests pass  
✅ No errors in production logs after 24 hours  

#### Troubleshooting

**Issue: Jobs not processing**
```bash
# Check worker is running
sudo supervisorctl status freescout-worker-billing

# Restart if needed
sudo supervisorctl restart freescout-worker-billing:*

# Check for failed jobs
php artisan queue:failed
```

**Issue: Queue depth growing**
```bash
# Check queue size
php artisan queue:monitor billing

# Scale up workers temporarily
sudo supervisorctl scale freescout-worker-billing:15
```

---

### 1.2 Transaction Boundaries Pattern

**⏱️ Estimated Time:** Already documented in SYSTEM_ARCHITECTURE.md Section 16  
**🎯 Goal:** Provide clear guidance on when/how to use database transactions  
**⚠️ Impact:** MEDIUM - Prevents data corruption in financial operations  

#### Implementation Status

✅ **Already Complete** - Added to SYSTEM_ARCHITECTURE.md Section 16: Transaction Management Guidelines

#### Developer Training Required

Conduct 1-hour team training session covering:

1. **When to Use Transactions** (Checklist from Section 16.1)
2. **4 Transaction Patterns** (Section 16.2)
   - Financial atomicity
   - Idempotent handlers
   - Atomic counters
   - Compensating transactions (saga pattern)
3. **3 Anti-Patterns to Avoid** (Section 16.3)
   - API calls inside transactions
   - Nested transactions
   - No deadlock retry logic

#### Code Review Checklist

Add to your team's code review guidelines:

**Transaction Usage Checklist:**
- [ ] Does this modify financial data? → Requires transaction
- [ ] Does this update multiple related tables? → Requires transaction
- [ ] Does this call external APIs? → Must be OUTSIDE transaction
- [ ] Does this handle events? → Each listener handles own transaction
- [ ] Is lockForUpdate() used correctly? → Must be inside transaction
- [ ] Is deadlock retry implemented? → Required for high-concurrency operations

#### Automated ArchTest Guard

Add to `tests/Architecture/LayerTest.php`:

```php
public function test_transactions_do_not_contain_http_calls(): void
{
    Arch::expect('App')
        ->not->toUse([
            \Illuminate\Support\Facades\Http::class,
            \GuzzleHttp\Client::class,
        ])
        ->whenUsing(\Illuminate\Support\Facades\DB::transaction(...));
}
```

---

## P1: High Priority Implementations

### 2.1 Missing Event Listeners

**⏱️ Estimated Time:** 4-6 hours  
**🎯 Goal:** Close gaps in event-driven architecture  
**⚠️ Impact:** MEDIUM - Contract changes not triggering proration recalculation  

#### Gap Analysis

Current missing listeners identified:

1. **ContractRevised** → Should trigger proration recalculation
2. **SoftwareCountChanged** → Should trigger billing adjustment
3. **ClientArchived** → Should pause billing

#### Step 1: Create RecalculateProrationOnContractChange Listener

```bash
# Create listener file
php artisan make:listener RecalculateProrationOnContractChange --event=ContractRevised
```

Edit `Modules/PIB/Listeners/RecalculateProrationOnContractChange.php`:

```php
<?php

namespace Modules\PIB\Listeners;

use App\Listeners\IdempotentListener;
use Illuminate\Support\Facades\Log;
use Modules\ContractManager\Events\ContractRevised;
use Modules\PIB\Jobs\ApplyProrationJob;

class RecalculateProrationOnContractChange extends IdempotentListener
{
    /**
     * Handle contract revision event with proration calculation.
     */
    protected function handleIdempotent($event): void
    {
        /** @var ContractRevised $event */
        $contract = $event->contract;
        
        Log::info('Contract revised, checking proration', [
            'contract_id' => $contract->id,
            'client_id' => $contract->client_id,
            'revised_at' => $event->revisedAt,
        ]);

        // Only apply proration if:
        // 1. Contract is active
        // 2. Change happened mid-billing-cycle
        if ($contract->status === 'active' && $this->isMidCycle($contract)) {
            Log::info('Triggering proration recalculation', [
                'contract_id' => $contract->id,
                'client_id' => $contract->client_id,
            ]);

            ApplyProrationJob::dispatch($contract->client_id)
                ->onQueue('billing')
                ->delay(now()->addMinutes(5)); // Small delay to batch multiple changes
        }
    }

    /**
     * Check if contract change happened mid-billing-cycle.
     */
    private function isMidCycle($contract): bool
    {
        $billingCycleStart = $contract->billing_cycle_start;
        $billingCycleEnd = $contract->billing_cycle_end;
        
        return now()->between($billingCycleStart, $billingCycleEnd);
    }
}
```

#### Step 2: Create AdjustBillingOnSoftwareCountChange Listener

```bash
php artisan make:listener AdjustBillingOnSoftwareCountChange --event=SoftwareCountChanged
```

Edit `Modules/PIB/Listeners/AdjustBillingOnSoftwareCountChange.php`:

```php
<?php

namespace Modules\PIB\Listeners;

use App\Listeners\IdempotentListener;
use Illuminate\Support\Facades\Log;
use Modules\SoftwareSubscriptions\Events\SoftwareCountChanged;
use Modules\PIB\Jobs\RecalculateBillingJob;

class AdjustBillingOnSoftwareCountChange extends IdempotentListener
{
    protected function handleIdempotent($event): void
    {
        /** @var SoftwareCountChanged $event */
        
        Log::info('Software count changed', [
            'client_id' => $event->clientId,
            'subscription_id' => $event->subscriptionId,
            'old_count' => $event->oldCount,
            'new_count' => $event->newCount,
        ]);

        // Trigger billing recalculation
        RecalculateBillingJob::dispatch(
            $event->clientId,
            $event->subscriptionId
        )->onQueue('billing');
    }
}
```

#### Step 3: Create PauseBillingOnClientArchive Listener

```bash
php artisan make:listener PauseBillingOnClientArchive --event=ClientArchived
```

Edit `Modules/PIB/Listeners/PauseBillingOnClientArchive.php`:

```php
<?php

namespace Modules\PIB\Listeners;

use App\Listeners\IdempotentListener;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Crm\Events\ClientArchived;
use Modules\PIB\Models\BillingSchedule;

class PauseBillingOnClientArchive extends IdempotentListener
{
    protected function handleIdempotent($event): void
    {
        /** @var ClientArchived $event */
        
        Log::warning('Client archived, pausing billing', [
            'client_id' => $event->clientId,
            'archived_at' => $event->archivedAt,
        ]);

        DB::transaction(function () use ($event) {
            // Pause all active billing schedules
            BillingSchedule::where('client_id', $event->clientId)
                ->where('status', 'active')
                ->update([
                    'status' => 'paused',
                    'paused_at' => $event->archivedAt,
                    'paused_reason' => 'client_archived',
                ]);
        });
    }
}
```

#### Step 4: Register Listeners in Service Provider

Edit `Modules/PIB/Providers/PIBServiceProvider.php`:

```php
<?php

namespace Modules\PIB\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\ContractManager\Events\ContractRevised;
use Modules\Crm\Events\ClientArchived;
use Modules\PIB\Listeners\AdjustBillingOnSoftwareCountChange;
use Modules\PIB\Listeners\PauseBillingOnClientArchive;
use Modules\PIB\Listeners\RecalculateProrationOnContractChange;
use Modules\SoftwareSubscriptions\Events\SoftwareCountChanged;

class PIBServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Register event listeners
        Event::listen(
            ContractRevised::class,
            RecalculateProrationOnContractChange::class
        );

        Event::listen(
            SoftwareCountChanged::class,
            AdjustBillingOnSoftwareCountChange::class
        );

        Event::listen(
            ClientArchived::class,
            PauseBillingOnClientArchive::class
        );
    }
}
```

#### Step 5: Write Tests

Create `tests/Feature/PIB/EventListenerTest.php`:

```php
<?php

namespace Tests\Feature\PIB;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Modules\ContractManager\Events\ContractRevised;
use Modules\Crm\Events\ClientArchived;
use Modules\PIB\Jobs\ApplyProrationJob;
use Modules\PIB\Jobs\RecalculateBillingJob;
use Modules\PIB\Listeners\PauseBillingOnClientArchive;
use Modules\PIB\Listeners\RecalculateProrationOnContractChange;
use Modules\PIB\Models\BillingSchedule;
use Modules\SoftwareSubscriptions\Events\SoftwareCountChanged;
use Tests\TestCase;

class EventListenerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function contract_revision_triggers_proration_recalculation()
    {
        Queue::fake();
        
        $contract = Contract::factory()->active()->midCycle()->create();
        
        event(new ContractRevised($contract, now()));
        
        Queue::assertPushed(ApplyProrationJob::class, function ($job) use ($contract) {
            return $job->clientId === $contract->client_id;
        });
    }

    /** @test */
    public function software_count_change_triggers_billing_recalculation()
    {
        Queue::fake();
        
        $clientId = 123;
        $subscriptionId = 456;
        
        event(new SoftwareCountChanged($clientId, $subscriptionId, 10, 15));
        
        Queue::assertPushed(RecalculateBillingJob::class, function ($job) use ($clientId) {
            return $job->clientId === $clientId;
        });
    }

    /** @test */
    public function client_archive_pauses_billing_schedules()
    {
        $client = Client::factory()->create();
        $schedule = BillingSchedule::factory()->active()->create([
            'client_id' => $client->id,
        ]);

        event(new ClientArchived($client->id, now()));

        $this->assertDatabaseHas('billing_schedules', [
            'id' => $schedule->id,
            'status' => 'paused',
            'paused_reason' => 'client_archived',
        ]);
    }

    /** @test */
    public function listeners_are_idempotent()
    {
        Queue::fake();
        
        $contract = Contract::factory()->active()->midCycle()->create();
        
        // Dispatch event twice
        event(new ContractRevised($contract, now()));
        event(new ContractRevised($contract, now()));
        
        // Should only process once due to IdempotentListener base class
        Queue::assertPushed(ApplyProrationJob::class, 1);
    }
}
```

Run tests:
```bash
php artisan test --filter=EventListenerTest
```

#### Step 6: Verify Event Flow

```bash
# Use Laravel Telescope to monitor events
php artisan telescope:install

# In browser: http://localhost/telescope/events

# Trigger test events
php artisan tinker
>>> $contract = Contract::find(1);
>>> event(new ContractRevised($contract, now()));
>>> // Check Telescope UI for event flow
```

#### Success Criteria

✅ All 3 listeners created and registered  
✅ Tests pass with 100% coverage  
✅ Idempotency verified (duplicate events handled correctly)  
✅ Jobs dispatched to correct queue  
✅ Event flow visible in Telescope  

---

### 2.2 Caching Strategy

**⏱️ Estimated Time:** Already documented in SYSTEM_ARCHITECTURE.md Section 14.2  
**🎯 Goal:** Implement comprehensive caching strategy  
**⚠️ Impact:** HIGH - 50-80% performance improvement expected  

#### Implementation Status

✅ **Already Documented** - Complete caching strategy in SYSTEM_ARCHITECTURE.md Section 14.2

#### Step 1: Create CacheService Helper

Create `app/Services/CacheService.php`:

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CacheService
{
    /**
     * Cache TTL constants (in seconds)
     */
    public const TTL_USER_PERMISSIONS = 86400;     // 24 hours
    public const TTL_CLIENT_ENTITLEMENTS = 300;    // 5 minutes
    public const TTL_CREDIT_BALANCE = 60;          // 1 minute
    public const TTL_ASSET_COUNT = 300;            // 5 minutes
    public const TTL_RATE_LIMITER = 3600;          // 1 hour

    /**
     * Remember a value with standardized key naming.
     */
    public function remember(
        string $domain,
        string $entityType,
        int|string $entityId,
        ?string $attribute,
        int $ttl,
        callable $callback
    ): mixed {
        $key = $this->buildKey($domain, $entityType, $entityId, $attribute);
        
        return Cache::remember($key, $ttl, function () use ($key, $callback) {
            Log::debug("Cache miss: $key");
            return $callback();
        });
    }

    /**
     * Forget (invalidate) a cached value.
     */
    public function forget(
        string $domain,
        string $entityType,
        int|string $entityId,
        ?string $attribute = null
    ): bool {
        $key = $this->buildKey($domain, $entityType, $entityId, $attribute);
        Log::debug("Cache invalidate: $key");
        
        return Cache::forget($key);
    }

    /**
     * Flush all caches for an entity (using tags).
     */
    public function flushEntity(
        string $domain,
        string $entityType,
        int|string $entityId
    ): void {
        $tag = "{$domain}:{$entityType}:{$entityId}";
        Log::debug("Cache flush tag: $tag");
        
        Cache::tags([$tag])->flush();
    }

    /**
     * Build standardized cache key.
     */
    private function buildKey(
        string $domain,
        string $entityType,
        int|string $entityId,
        ?string $attribute
    ): string {
        $parts = [$domain, $entityType, $entityId];
        if ($attribute) {
            $parts[] = $attribute;
        }
        
        return implode(':', $parts);
    }
}
```

#### Step 2: Update Services to Use Caching

Example: Cache client entitlements in PIB module.

Edit `Modules/PIB/Services/EntitlementService.php`:

```php
<?php

namespace Modules\PIB\Services;

use App\Services\CacheService;
use Modules\PIB\Models\Entitlement;

class EntitlementService
{
    public function __construct(
        private CacheService $cache
    ) {}

    /**
     * Get current entitlements for a client (cached).
     */
    public function getCurrentEntitlements(int $clientId): array
    {
        return $this->cache->remember(
            domain: 'billing',
            entityType: 'entitlement',
            entityId: $clientId,
            attribute: 'current',
            ttl: CacheService::TTL_CLIENT_ENTITLEMENTS,
            callback: fn() => $this->fetchEntitlementsFromDatabase($clientId)
        );
    }

    /**
     * Invalidate entitlements cache when invoices paid.
     */
    public function invalidateCache(int $clientId): void
    {
        $this->cache->forget('billing', 'entitlement', $clientId, 'current');
    }

    private function fetchEntitlementsFromDatabase(int $clientId): array
    {
        return Entitlement::where('client_id', $clientId)
            ->where('status', 'active')
            ->with(['product', 'addons'])
            ->get()
            ->toArray();
    }
}
```

#### Step 3: Add Cache Invalidation Listeners

Create `app/Listeners/InvalidateCacheOnInvoicePaid.php`:

```php
<?php

namespace App\Listeners;

use App\Services\CacheService;
use Modules\PIB\Events\InvoicePaid;

class InvalidateCacheOnInvoicePaid
{
    public function __construct(
        private CacheService $cache
    ) {}

    public function handle(InvoicePaid $event): void
    {
        $clientId = $event->invoice->client_id;

        // Clear affected caches
        $this->cache->forget('billing', 'entitlement', $clientId, 'current');
        $this->cache->forget('billing', 'client', $clientId, 'balance');
        $this->cache->forget('billing', 'client', $clientId, 'invoices');
        
        // Flush all billing-related caches for this client
        $this->cache->flushEntity('billing', 'client', $clientId);
    }
}
```

Register in `app/Providers/EventServiceProvider.php`:

```php
protected $listen = [
    InvoicePaid::class => [
        InvalidateCacheOnInvoicePaid::class,
    ],
];
```

#### Step 4: Add Cache Warming Command

Create `app/Console/Commands/WarmCache.php`:

```php
<?php

namespace App\Console\Commands;

use App\Services\CacheService;
use Illuminate\Console\Command;
use Modules\Crm\Models\Client;
use Modules\PIB\Services\EntitlementService;

class WarmCache extends Command
{
    protected $signature = 'cache:warm {--clients=100 : Number of clients to warm}';
    protected $description = 'Pre-warm frequently accessed caches';

    public function handle(EntitlementService $entitlementService): int
    {
        $this->info('Warming cache...');

        $clientCount = (int) $this->option('clients');

        // Warm most active clients (by recent activity)
        $clients = Client::query()
            ->where('status', 'active')
            ->orderByDesc('last_activity_at')
            ->limit($clientCount)
            ->get();

        $bar = $this->output->createProgressBar($clients->count());
        $bar->start();

        foreach ($clients as $client) {
            // This will populate cache
            $entitlementService->getCurrentEntitlements($client->id);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("✅ Warmed cache for {$clients->count()} clients");

        return Command::SUCCESS;
    }
}
```

Schedule in `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule): void
{
    // Warm cache every morning before business hours
    $schedule->command('cache:warm --clients=500')
        ->dailyAt('06:00')
        ->timezone('America/New_York');
}
```

#### Step 5: Write Tests

Create `tests/Feature/CachingTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Services\CacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Modules\PIB\Services\EntitlementService;
use Tests\TestCase;

class CachingTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function entitlements_are_cached()
    {
        Cache::flush();
        
        $client = Client::factory()->create();
        $entitlementService = app(EntitlementService::class);

        // First call - should hit database
        $first = $entitlementService->getCurrentEntitlements($client->id);
        
        // Second call - should hit cache
        $second = $entitlementService->getCurrentEntitlements($client->id);

        $this->assertEquals($first, $second);
        
        // Verify cache hit
        $this->assertTrue(Cache::has("billing:entitlement:{$client->id}:current"));
    }

    /** @test */
    public function cache_invalidates_on_invoice_paid()
    {
        $client = Client::factory()->create();
        $invoice = Invoice::factory()->create(['client_id' => $client->id]);
        
        $entitlementService = app(EntitlementService::class);
        
        // Populate cache
        $entitlementService->getCurrentEntitlements($client->id);
        $this->assertTrue(Cache::has("billing:entitlement:{$client->id}:current"));

        // Pay invoice (should invalidate cache)
        event(new InvoicePaid($invoice, Payment::factory()->create()));

        // Cache should be cleared
        $this->assertFalse(Cache::has("billing:entitlement:{$client->id}:current"));
    }

    /** @test */
    public function cache_ttl_is_respected()
    {
        Cache::flush();
        
        $key = 'test:key';
        $ttl = 1; // 1 second

        Cache::put($key, 'value', $ttl);
        $this->assertEquals('value', Cache::get($key));

        // Wait for TTL to expire
        sleep(2);

        $this->assertNull(Cache::get($key));
    }
}
```

#### Step 6: Monitor Cache Performance

Add metrics to track cache hit ratio:

```php
// app/Http/Middleware/CacheMetrics.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CacheMetrics
{
    public function handle(Request $request, Closure $next)
    {
        // Track cache hits/misses
        $hitsBefore = Cache::getStore()->hits();
        $missesBefore = Cache::getStore()->misses();

        $response = $next($request);

        $hitsAfter = Cache::getStore()->hits();
        $missesAfter = Cache::getStore()->misses();

        // Log metrics (or send to Prometheus)
        $hitRatio = ($hitsAfter - $hitsBefore) / 
                    max(1, ($hitsAfter - $hitsBefore) + ($missesAfter - $missesBefore));

        if ($hitRatio < 0.5) {
            logger()->warning('Low cache hit ratio', [
                'url' => $request->url(),
                'hit_ratio' => $hitRatio,
            ]);
        }

        return $response;
    }
}
```

#### Success Criteria

✅ CacheService implemented and tested  
✅ Key services using caching (EntitlementService, CreditService)  
✅ Cache invalidation listeners registered  
✅ Cache warming command scheduled  
✅ Cache hit ratio > 80% in production  
✅ Response times improved by 50-80%  

---

### 2.3 Observability Stack

**⏱️ Estimated Time:** 8-12 hours (1 week)  
**🎯 Goal:** Complete production monitoring and error tracking  
**⚠️ Impact:** CRITICAL - Reduces MTTR from hours to minutes  

#### Step 1: Install Sentry (Error Tracking)

```bash
# Install Sentry SDK
composer require sentry/sentry-laravel

# Publish configuration
php artisan sentry:publish --dsn
```

Edit `.env`:
```env
SENTRY_LARAVEL_DSN=https://your-dsn@sentry.io/project-id
SENTRY_TRACES_SAMPLE_RATE=0.1
SENTRY_ENVIRONMENT=production
```

Edit `config/sentry.php`:

```php
<?php

return [
    'dsn' => env('SENTRY_LARAVEL_DSN'),
    'environment' => env('SENTRY_ENVIRONMENT', env('APP_ENV')),
    'traces_sample_rate' => (float) env('SENTRY_TRACES_SAMPLE_RATE', 0.0),
    
    'send_default_pii' => false, // GDPR compliance
    
    // Scrub sensitive data
    'before_send' => function (\Sentry\Event $event): ?\Sentry\Event {
        // Remove passwords, tokens, credit cards from error reports
        $event->setExtra([
            'password' => '[REDACTED]',
            'token' => '[REDACTED]',
            'card_number' => '[REDACTED]',
        ]);
        return $event;
    },
    
    // Ignore certain exceptions
    'ignore_exceptions' => [
        \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Validation\ValidationException::class,
    ],
];
```

#### Step 2: Add Custom Error Context

Create `app/Services/ErrorTrackingService.php`:

```php
<?php

namespace App\Services;

use Sentry\State\Scope;
use function Sentry\configureScope;

class ErrorTrackingService
{
    /**
     * Add user context to error reports.
     */
    public function setUser(?int $userId, ?string $email = null): void
    {
        configureScope(function (Scope $scope) use ($userId, $email): void {
            $scope->setUser([
                'id' => $userId,
                'email' => $email,
            ]);
        });
    }

    /**
     * Add client context to error reports.
     */
    public function setClient(int $clientId, string $clientName): void
    {
        configureScope(function (Scope $scope) use ($clientId, $clientName): void {
            $scope->setContext('client', [
                'id' => $clientId,
                'name' => $clientName,
            ]);
        });
    }

    /**
     * Track custom event.
     */
    public function trackEvent(string $message, array $context = []): void
    {
        \Sentry\captureMessage($message, \Sentry\Severity::info(), [
            'extra' => $context,
        ]);
    }
}
```

#### Step 3: Add Performance Monitoring

Edit `app/Http/Middleware/PerformanceMonitoring.php`:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use function Sentry\startTransaction;

class PerformanceMonitoring
{
    public function handle(Request $request, Closure $next)
    {
        // Start Sentry transaction for performance monitoring
        $transaction = startTransaction([
            'op' => 'http.server',
            'name' => $request->method() . ' ' . $request->path(),
            'description' => $request->fullUrl(),
        ]);

        \Sentry\SentrySdk::getCurrentHub()->setSpan($transaction);

        $response = $next($request);

        $transaction->finish();

        return $response;
    }
}
```

Register in `app/Http/Kernel.php`:

```php
protected $middleware = [
    // ...
    \App\Http\Middleware\PerformanceMonitoring::class,
];
```

#### Step 4: Install Prometheus Metrics (Optional)

```bash
composer require spatie/laravel-prometheus
```

Create `app/Services/MetricsService.php` (already documented in SYSTEM_ARCHITECTURE.md Section 15):

```php
<?php

namespace App\Services;

use Prometheus\CollectorRegistry;
use Prometheus\Storage\Redis;

class MetricsService
{
    private CollectorRegistry $registry;

    public function __construct()
    {
        $this->registry = new CollectorRegistry(new Redis([
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'port' => env('REDIS_PORT', 6379),
        ]));
    }

    /**
     * Increment a counter metric.
     */
    public function incrementCounter(
        string $name,
        string $help,
        array $labels = [],
        int $value = 1
    ): void {
        $counter = $this->registry->getOrRegisterCounter(
            'app',
            $name,
            $help,
            array_keys($labels)
        );
        
        $counter->incBy($value, array_values($labels));
    }

    /**
     * Observe a histogram value (for timing/duration metrics).
     */
    public function observeHistogram(
        string $name,
        string $help,
        float $value,
        array $labels = []
    ): void {
        $histogram = $this->registry->getOrRegisterHistogram(
            'app',
            $name,
            $help,
            array_keys($labels),
            [0.005, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1, 2.5, 5, 10]
        );
        
        $histogram->observe($value, array_values($labels));
    }

    /**
     * Set a gauge value.
     */
    public function setGauge(
        string $name,
        string $help,
        float $value,
        array $labels = []
    ): void {
        $gauge = $this->registry->getOrRegisterGauge(
            'app',
            $name,
            $help,
            array_keys($labels)
        );
        
        $gauge->set($value, array_values($labels));
    }

    /**
     * Get metrics output for Prometheus scraping.
     */
    public function getMetrics(): string
    {
        $renderer = new \Prometheus\RenderTextFormat();
        return $renderer->render($this->registry->getMetricFamilySamples());
    }
}
```

Add metrics endpoint in `routes/web.php`:

```php
Route::get('/metrics', function (MetricsService $metrics) {
    return response($metrics->getMetrics())
        ->header('Content-Type', 'text/plain; version=0.0.4');
})->middleware('auth.basic'); // Protect with basic auth
```

#### Step 5: Instrument Key Operations

Add metrics tracking to invoice generation:

```php
// Modules/PIB/Jobs/GenerateInvoiceJob.php
use App\Services\MetricsService;

class GenerateInvoiceJob implements ShouldQueue
{
    public function handle(MetricsService $metrics): void
    {
        $startTime = microtime(true);

        try {
            // Generate invoice
            $invoice = $this->generateInvoice();

            // Track success
            $metrics->incrementCounter(
                'invoices_created_total',
                'Total invoices created',
                ['status' => 'success', 'client_id' => $this->clientId]
            );

        } catch (\Exception $e) {
            // Track failure
            $metrics->incrementCounter(
                'invoices_created_total',
                'Total invoices created',
                ['status' => 'failed', 'client_id' => $this->clientId]
            );

            throw $e;
        } finally {
            // Track duration
            $duration = microtime(true) - $startTime;
            $metrics->observeHistogram(
                'invoice_generation_duration_seconds',
                'Invoice generation duration',
                $duration,
                ['client_id' => $this->clientId]
            );
        }
    }
}
```

#### Step 6: Set Up Health Checks (Already in SYSTEM_ARCHITECTURE.md Section 15.4)

Create `app/Http/Controllers/HealthCheckController.php`:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class HealthCheckController extends Controller
{
    /**
     * Basic health check for load balancer.
     */
    public function basic(): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }

    /**
     * Detailed health check with service checks.
     */
    public function detailed(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'queue' => $this->checkQueue(),
            'storage' => $this->checkStorage(),
        ];

        $allHealthy = !in_array(false, $checks, true);

        return response()->json([
            'status' => $allHealthy ? 'healthy' : 'degraded',
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
        ], $allHealthy ? 200 : 503);
    }

    private function checkDatabase(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Exception $e) {
            logger()->error('Database health check failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    private function checkRedis(): bool
    {
        try {
            Redis::ping();
            return true;
        } catch (\Exception $e) {
            logger()->error('Redis health check failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    private function checkQueue(): bool
    {
        try {
            $size = Redis::llen('queues:default');
            return $size < 10000; // Alert if queue too deep
        } catch (\Exception $e) {
            return false;
        }
    }

    private function checkStorage(): bool
    {
        try {
            return is_writable(storage_path('logs'));
        } catch (\Exception $e) {
            return false;
        }
    }
}
```

Add routes:
```php
// routes/web.php
Route::get('/health', [HealthCheckController::class, 'basic']);
Route::get('/health/detailed', [HealthCheckController::class, 'detailed']);
```

#### Step 7: Configure Alerting

Create `config/alerting.php`:

```php
<?php

return [
    'sentry' => [
        'alert_rules' => [
            'high_priority' => [
                'invoice_failures' => [
                    'threshold' => 10,
                    'window' => '5m',
                    'action' => 'pagerduty',
                ],
                'database_errors' => [
                    'threshold' => 5,
                    'window' => '1m',
                    'action' => 'pagerduty',
                ],
            ],
            'medium_priority' => [
                'slow_transactions' => [
                    'threshold_p95' => 2000, // ms
                    'window' => '10m',
                    'action' => 'slack',
                ],
                'high_queue_depth' => [
                    'threshold' => 5000,
                    'window' => '5m',
                    'action' => 'slack',
                ],
            ],
        ],
    ],
];
```

#### Step 8: Test Observability Stack

```bash
# Test Sentry error tracking
php artisan tinker
>>> throw new \Exception('Test Sentry integration');
# Check Sentry dashboard for error

# Test metrics endpoint
curl http://localhost/metrics
# Should return Prometheus format metrics

# Test health checks
curl http://localhost/health
curl http://localhost/health/detailed
```

#### Success Criteria

✅ Sentry capturing errors in production  
✅ Metrics endpoint returning data  
✅ Health checks passing  
✅ Alerting rules configured  
✅ MTTR reduced from hours to <15 minutes  
✅ 100% of production errors tracked  

---

### 2.4 API Versioning

**⏱️ Estimated Time:** Already documented in SYSTEM_ARCHITECTURE.md Section 18  
**🎯 Goal:** Establish API versioning strategy  
**⚠️ impact:** MEDIUM - Future-proofs API evolution  

#### Implementation Status

✅ **Already Documented** - Complete API versioning strategy in SYSTEM_ARCHITECTURE.md Section 18

#### Step 1: Create Versioned API Routes

Create `routes/api/v1.php`:

```php
<?php

use App\Http\Controllers\Api\V1\ClientController;
use App\Http\Controllers\Api\V1\InvoiceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'throttle:100,1'])->group(function () {
    // Clients
    Route::get('/clients', [ClientController::class, 'index']);
    Route::get('/clients/{id}', [ClientController::class, 'show']);
    Route::post('/clients', [ClientController::class, 'store']);
    Route::put('/clients/{id}', [ClientController::class, 'update']);

    // Invoices
    Route::get('/invoices', [InvoiceController::class, 'index']);
    Route::get('/invoices/{id}', [InvoiceController::class, 'show']);
    Route::post('/invoices/{id}/pay', [InvoiceController::class, 'pay']);
});
```

Register in `routes/api.php`:

```php
<?php

use Illuminate\Support\Facades\Route;

// API v1
Route::prefix('api/v1')->group(base_path('routes/api/v1.php'));

// Future: API v2 (when breaking changes needed)
// Route::prefix('api/v2')->group(base_path('routes/api/v2.php'));
```

#### Step 2: Create API Controllers

```bash
mkdir -p app/Http/Controllers/Api/V1
php artisan make:controller Api/V1/ClientController --api
```

Edit `app/Http/Controllers/Api/V1/ClientController.php`:

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClientResource;
use Illuminate\Http\Request;
use Modules\Crm\Models\Client;

class ClientController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/clients",
     *     summary="List all clients",
     *     tags={"Clients"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of clients",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Client")),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $clients = Client::query()
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->paginate(50);

        return ClientResource::collection($clients);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/clients/{id}",
     *     summary="Get client by ID",
     *     tags={"Clients"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Client details",
     *         @OA\JsonContent(ref="#/components/schemas/Client")
     *     ),
     *     @OA\Response(response=404, description="Client not found")
     * )
     */
    public function show(int $id)
    {
        $client = Client::findOrFail($id);
        return new ClientResource($client);
    }
}
```

#### Step 3: Create API Resources

```bash
php artisan make:resource ClientResource
```

Edit `app/Http/Resources/ClientResource.php`:

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'status' => $this->status,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            
            // Include related resources only if requested
            'contacts' => $this->whenLoaded('contacts', ContactResource::collection($this->contacts)),
        ];
    }
}
```

#### Step 4: Set Up Laravel Sanctum Authentication

```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

Add to `config/sanctum.php`:

```php
'abilities' => [
    'clients:read' => 'Read client data',
    'clients:write' => 'Create and update clients',
    'invoices:read' => 'Read invoices',
    'invoices:pay' => 'Pay invoices',
],
```

Create tokens with scopes:

```php
// Generate API token for user
$token = $user->createToken('api-token', [
    'clients:read',
    'clients:write',
    'invoices:read',
])->plainTextToken;
```

#### Step 5: Add Deprecation Middleware

Create `app/Http/Middleware/ApiDeprecation.php`:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiDeprecation
{
    private array $deprecatedVersions = [
        // 'v1' => '2027-06-01', // Example: v1 sunset date
    ];

    public function handle(Request $request, Closure $next)
    {
        $version = $this->getApiVersion($request);

        $response = $next($request);

        if (isset($this->deprecatedVersions[$version])) {
            $sunsetDate = $this->deprecatedVersions[$version];
            $response->header('X-API-Deprecated', "true");
            $response->header('X-API-Sunset-Date', $sunsetDate);
            $response->header('X-API-Migration-Guide', url("/docs/api/{$version}/migration"));
        }

        return $response;
    }

    private function getApiVersion(Request $request): string
    {
        $path = $request->path();
        preg_match('/api\/(v\d+)/', $path, $matches);
        return $matches[1] ?? 'unknown';
    }
}
```

#### Step 6: Generate API Documentation (OpenAPI/Swagger)

```bash
composer require darkaonline/l5-swagger
php artisan vendor:publish --provider="L5Swagger\L5SwaggerServiceProvider"
php artisan l5-swagger:generate
```

View documentation at: `http://localhost/api/documentation`

#### Success Criteria

✅ API v1 routes created and tested  
✅ Laravel Sanctum authentication working  
✅ OpenAPI documentation generated  
✅ Deprecation middleware in place  
✅ API rate limiting configured  

---

## P2: Code Quality Improvements

### ✅ 3.1 Interface Segregation - COMPLETE

**⏱️ Estimated Time:** 4-6 hours  
**🎯 Goal:** Split large interfaces into focused, single-purpose contracts  
**⚠️ Impact:** MEDIUM - Improves testability and maintainability  
**✅ Status:** COMPLETED - February 9, 2026

**Implementation Summary:**
- ✅ Created `CreditWriter` interface (write operations only)
- ✅ Created `CreditReader` interface (read operations only)
- ✅ Updated `ClientCreditService` to implement both interfaces
- ✅ Registered segregated interfaces in `PIBServiceProvider`
- ✅ Maintained backward compatibility with `CreditLedgerInterface`
- ✅ Created example service `CreditBalanceReportService`
- ✅ Added comprehensive test suite (10 tests, 19 assertions passing)

**Files Created:**
- `app/Contracts/Billing/CreditWriter.php`
- `app/Contracts/Billing/CreditReader.php`
- `Modules/PIB/Services/Examples/CreditBalanceReportService.php`
- `tests/Feature/InterfaceSegregationTest.php`

**Files Modified:**
- `Modules/PIB/Services/ClientCreditService.php`
- `Modules/PIB/Providers/PIBServiceProvider.php`  

#### Step 1: Identify Large Interfaces

```bash
# Find interfaces with many methods
grep -r "interface.*{" app/ Modules/ | while read file; do
    methods=$(grep -c "public function" "$file")
    if [ "$methods" -gt 5 ]; then
        echo "$file: $methods methods"
    fi
done
```

#### Step 2: Refactor CreditLedger Interface

**Current (problematic):**

```php
// app/Contracts/CreditLedgerInterface.php
namespace App\Contracts;

interface CreditLedgerInterface
{
    public function addCredit(int $clientId, int $cents, string $reason): void;
    public function subtract Credit(int $clientId, int $cents, string $reason): void;
    public function getBalance(int $clientId): int;
    public function getLedger(int $clientId, Carbon $start, Carbon $end): Collection;
    public function reconcile(int $clientId): ReconciliationResult;
    public function exportToAccountingSystem(int $clientId): array;
    public function getAuditTrail(int $clientId): Collection;
}
```

**Refactored (better):**

Create three focused interfaces:

```php
// app/Contracts/CreditWriter.php
namespace App\Contracts;

interface CreditWriter
{
    public function addCredit(int $clientId, int $cents, string $reason): void;
    public function subtractCredit(int $clientId, int $cents, string $reason): void;
}

// app/Contracts/CreditReader.php
namespace App\Contracts;

use Carbon\Carbon;
use Illuminate\Support\Collection;

interface CreditReader
{
    public function getBalance(int $clientId): int;
    public function getLedger(int $clientId, Carbon $start, Carbon $end): Collection;
    public function getAuditTrail(int $clientId): Collection;
}

// app/Contracts/CreditReconciler.php
namespace App\Contracts;

interface CreditReconciler
{
    public function reconcile(int $clientId): ReconciliationResult;
    public function exportToAccountingSystem(int $clientId): array;
}
```

#### Step 3: Update Implementation

```php
// app/Services/ClientCreditService.php
namespace App\Services;

use App\Contracts\CreditWriter;
use App\Contracts\CreditReader;
use App\Contracts\CreditReconciler;

class ClientCreditService implements CreditWriter, CreditReader, CreditReconciler
{
    // Implements all three interfaces
    public function addCredit(int $clientId, int $cents, string $reason): void { ... }
    public function subtractCredit(int $clientId, int $cents, string $reason): void { ... }
    public function getBalance(int $clientId): int { ... }
    public function getLedger(int $clientId, Carbon $start, Carbon $end): Collection { ... }
    public function reconcile(int $clientId): ReconciliationResult { ... }
    public function exportToAccountingSystem(int $clientId): array { ... }
    public function getAuditTrail(int $clientId): Collection { ... }
}
```

#### Step 4: Update Consumers

**Before:**
```php
class BillingAnalysisService
{
    public function __construct(
        private CreditLedgerInterface $creditLedger // Can do EVERYTHING
    ) {}
}
```

**After:**
```php
class BillingAnalysisService
{
    public function __construct(
        private CreditReader $creditReader // Can only READ
    ) {}
}
```

This prevents accidental mutations and makes testing easier:

```php
// Now you can mock just the read interface
$mock = Mockery::mock(CreditReader::class);
$mock->shouldReceive('getBalance')->andReturn(1000);
```

#### Step 5: Update Service Provider Bindings

```php
// app/Providers/AppServiceProvider.php
public function register(): void
{
    // Bind all three interfaces to same implementation
    $this->app->bind(CreditWriter::class, ClientCreditService::class);
    $this->app->bind(CreditReader::class, ClientCreditService::class);
    $this->app->bind(CreditReconciler::class, ClientCreditService::class);
    
    // Keep legacy binding for backward compatibility (temporary)
    $this->app->bind(CreditLedgerInterface::class, ClientCreditService::class);
}
```

#### Step 6: Add ArchTest Guard

```php
// tests/Architecture/InterfaceTest.php
test('interfaces follow segregation principle')
    ->expect('App\Contracts')
    ->toHaveMaxMethods(5, 'Interface should have ≤5 methods');

test('read-only services only depend on reader interfaces')
    ->expect(['App\Services\*AnalysisService', 'App\Services\*ReportService'])
    ->not->toUse([
        CreditWriter::class,
        // Add other writer interfaces
    ]);
```

#### Success Criteria

✅ All large interfaces split (<5 methods each)  
✅ Consumers updated to depend on specific interfaces  
✅ Service provider bindings configured  
✅ ArchTest guards passing  
✅ Tests updated and passing  

---

### ✅ 3.2 Cache Invalidation Tests - COMPLETE

**⏱️ Completed:** February 9, 2026  
**🎯 Goal:** Ensure cache invalidation works correctly across all scenarios  
**⚠️ Impact:** HIGH - Prevents stale data and cache-related bugs  

#### Implementation Summary

**Files Created:**
- `tests/Feature/CacheInvalidationTest.php` - 13 comprehensive tests covering:
  - Cache key standardization
  - Cache remember/forget operations
  - Event-driven invalidation
  - Cache warming functionality
  - TTL management
  - Multi-entity caching

**Test Coverage:**
```bash
✅ cache service builds keys correctly
✅ cache service remembers values on subsequent calls
✅ cache service forgets specific keys
✅ cache invalidation listener fires on invoice paid event
✅ cache warming works for multiple entities
✅ cache warming continues on individual failures
✅ cache service respects TTL constants
✅ cache keys follow standard naming convention
✅ multiple caches for same entity can be independently invalidated
✅ cache service handles string entity IDs
✅ cache service returns default value when key does not exist
✅ invoice paid event is properly wired in event service provider
✅ cache invalidation follows documented patterns from architecture
```

#### Running the Tests

```bash
# Run cache invalidation tests
./vendor/bin/pest tests/Feature/CacheInvalidationTest.php

# Expected: 13 passed (42 assertions)
```

#### Success Criteria

✅ All cache invalidation tests passing  
✅ Event listeners properly wired  
✅ Cache service follows documented patterns  
✅ TTL constants defined and used consistently  
✅ Cache warming handles failures gracefully  

---

### ✅ 3.3 Architecture Test Guards - COMPLETE

**⏱️ Completed:** February 9, 2026  
**🎯 Goal:** Enforce architectural patterns and prevent violations  
**⚠️ Impact:** HIGH - Prevents architecture drift and technical debt  

#### Implementation Summary

Architecture tests automatically enforce best practices using Pest's Arch Testing features.

**Files Created:**

1. **`tests/Architecture/InterfaceSegregationTest.php`**
   - Enforces Interface Segregation Principle (ISP)
   - Prevents services from bypassing interfaces
   - Validates interface naming conventions

2. **`tests/Architecture/LayerTest.php`**
   - Enforces layered architecture separation
   - Validates proper use of services, models, events
   - Ensures dependency direction is correct

3. **`tests/Architecture/ModuleBoundariesTest.php`**
   - Prevents cross-module coupling
   - Enforces module isolation rules
   - Validates module structure

4. **`tests/Architecture/NamingConventionsTest.php`**
   - Enforces consistent naming patterns
   - Validates suffix conventions (Controller, Service, Job, etc.)
   - Documents technical debt via ignoring lists

#### Test Coverage

```bash
# Interface Segregation
✅ all interfaces have Interface suffix or descriptive focused names
✅ services should not bypass interfaces and use implementations directly

# Layer Architecture
✅ services are in Services directory
✅ contracts are interfaces  
✅ listeners are in Listeners directory
✅ jobs are queueable
✅ events use Dispatchable trait
✅ models are in Models directory
✅ providers extend ServiceProvider

# Module Boundaries
✅ modules should not depend on other module implementations
✅ module models are namespaced under Modules
✅ module events are properly namespaced
✅ module jobs are queueable

# Naming Conventions
✅ controllers have Controller suffix
✅ services have Service suffix
✅ repositories have Repository suffix
✅ jobs have Job suffix
✅ listeners are in Listeners directory
✅ events use Dispatchable trait
✅ exceptions have Exception suffix
✅ middleware has descriptive names
✅ policies have Policy suffix
✅ facades extend Facade base class
✅ data transfer objects are readonly
✅ enums are in Enums directory
```

#### Running the Tests

```bash
# Run all architecture tests
./vendor/bin/pest tests/Architecture/

# Expected: 25 passed (32 assertions)

# Run specific test suites
./vendor/bin/pest tests/Architecture/InterfaceSegregationTest.php
./vendor/bin/pest tests/Architecture/LayerTest.php
./vendor/bin/pest tests/Architecture/ModuleBoundariesTest.php
./vendor/bin/pest tests/Architecture/NamingConventionsTest.php
```

#### Continuous Integration

Add to CI/CD pipeline:

```yaml
# .github/workflows/tests.yml
- name: Run Architecture Tests
  run: ./vendor/bin/pest tests/Architecture/ --stop-on-failure
```

Architecture tests will fail CI if violations are introduced, preventing architecture drift.

#### Success Criteria

✅ All 25 architecture tests passing  
✅ Tests integrated into CI/CD pipeline  
✅ Technical debt documented via ignoring lists  
✅ Team trained on architecture patterns  

---

### ✅ 3.4 Authentication Rate Limiting - COMPLETE

**⏱️ Completed:** February 9, 2026  
**🎯 Goal:** Protect authentication endpoints from brute force attacks  
**⚠️ Impact:** HIGH - Critical security enhancement  

#### Implementation Summary

Added rate limiting to all authentication endpoints to prevent brute force attacks, account enumeration, and email flooding.

**Rate Limits Applied:**
- **Login:** 5 attempts per minute per IP
- **Register:** 5 attempts per minute per IP
- **Forgot Password:** 3 attempts per minute per IP (stricter to prevent email flooding)
- **Reset Password:** 5 attempts per minute per IP

**Files Modified:**
- `routes/auth.php` - Added `throttle` middleware to auth POST endpoints

**Files Cauth rate limiting tests
./vendor/bin/pest tests/Feature/AuthRateLimitingTest.php

# 6. Run reated:**
- `tests/Feature/AuthRateLimitingTest.php` - 8 comprehensive tests

#### Implementation Details

```php
// routes/auth.php - Login endpoint
Route::post('login', [AuthenticatedSessionController::class, 'store'])
    ->middleware('throttle:5,1'); // 5 attempts per 1 minute

// routes/auth.php - Register endpoint
Route::post('register', [RegisteredUserController::class, 'store'])
    ->middleware('throttle:5,1');

// routes/auth.php - Forgot Password (stricter limit)
Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
    ->name('password.email')
    ->middleware('throttle:3,1'); // 3 attempts per 1 minute

// routes/auth.php - Reset Password
Route::post('reset-password', [NewPasswordController::class, 'store'])
    ->name('password.store')
    ->middleware('throttle:5,1');
```

#### Test Coverage

```bash
✅ login endpoint is rate limited to 5 attempts per minute
✅ register endpoint is rate limited to 5 attempts per minute
✅ forgot password endpoint is rate limited to 3 attempts per minute
✅ reset password endpoint is rate limited to 5 attempts per minute
✅ rate limit headers are present in throttled responses
✅ successful login attempts still count toward rate limit
✅ rate limits are per IP address
✅ rate limit applies correctly before threshold
```

#### Running the Tests

```bash
# Run auth rate limiting tests
./vendor/bin/pest tests/Feature/AuthRateLimitingTest.php

# Expected: 8 passed (21 assertions)
```

#### HTTP Response Headers

When rate limit is exceeded, responses include:

```http
HTTP/1.1 429 Too Many Requests
X-RateLimit-Limit: 5
X-RateLimit-Remaining: 0
Retry-After: 60
```

#### Security Benefits

1. **Brute Force Protection:** Limits password guessing attacks
2. **Account Enumeration Prevention:** Makes it harder to discover valid accounts
3. **Email Flood Prevention:** Stricter limits on password reset requests
4. **Clear User Feedback:** Rate limit headers inform clients when to retry
5. **IP-Based Tracking:** Isolated rate limits per IP address

#### Customizing Rate Limits

To adjust rate limits, modify the middleware parameters in `routes/auth.php`:

```php
// Syntax: throttle:max_attempts,decay_minutes
->middleware('throttle:10,5') // 10 attempts per 5 minutes
->middleware('throttle:3,1')  // 3 attempts per 1 minute
```

#### Success Criteria

✅ All authentication endpoints protected  
✅ 8 comprehensive tests passing  
✅ Rate limit headers present in responses  
✅ No degradation in legitimate user experience  
✅ Prevents brute force attack vectors  

---

### ✅ 3.5 Centralized Audit Log - COMPLETE

**⏱️ Completed:** February 9, 2026  
**🎯 Goal:** Structured logging for sensitive business operations  
**⚠️ Impact:** HIGH - Compliance and security audit trail  

#### Implementation Summary

Implemented centralized audit logging system for tracking sensitive operations such as credit adjustments, quote approvals, data exports, and permission changes.

**Files Created:**
- `app/Services/AuditLogService.php` - Core audit logging service
- `app/Traits/AuditsSensitiveOperations.php` - Convenient trait for service integration
- `tests/Feature/AuditLogTest.php` - Comprehensive test suite

**Files Modified:**
- `config/logging.php` - Added dedicated audit channel (1-year retention)

#### Usage in Services

```php
// Add trait to any service with sensitive operations
use App\Traits\AuditsSensitiveOperations;

class CreditService
{
    use AuditsSensitiveOperations;
    
    public function addCredit(Client $client, int $amountCents, string $reason): void
    {
        // Perform the operation
        $ledger = $this->createLedgerEntry($client, $amountCents);
        
        // Audit it
        $this->auditFinancialOperation(
            operation: 'credit_added',
            subject: $client,
            amountCents: $amountCents,
            additionalProperties: ['reason' => $reason]
        );
    }
    
    public function approveQuote(Quote $quote): void
    {
        $quote->update(['status' => 'approved']);
        
        $this->auditSensitiveOperation(
            operation: 'quote_approved',
            subject: $quote,
            properties: [
                'total_amount' => $quote->total_cents,
                'line_items_count' => $quote->lineItems()->count(),
            ]
        );
    }
}
```

#### Querying Audit Logs

```php
$auditService = app(AuditLogService::class);

// Get recent financial operations
$logs = $auditService->queryLogs([
    'log_name' => 'financial_operations',
    'date_from' => now()->subDays(7),
])->get();

// Get audit trail for a specific client
$trail = $auditService->getSubjectAuditTrail($client, limit: 50);

// Get all sensitive operations in the last 24 hours
$recent = $auditService->getRecentSensitiveOperations(hours: 24);
```

#### Running Tests

```bash
# Run audit log tests
./vendor/bin/pest tests/Feature/AuditLogTest.php

# Expected: 11 passed (45 assertions)
```

#### Success Criteria

✅ AuditLogService and trait implemented  
✅ 11 comprehensive tests passing  
✅ Audit channel configured with 1-year retention  
✅ Context enrichment (IP, user agent, timestamp)  
✅ Query interface for investigations  

---

### ✅ 3.6 API Authentication Strategy - COMPLETE

**⏱️ Completed:** February 9, 2026  
**🎯 Goal:** Document comprehensive API authentication strategy  
**⚠️ Impact:** HIGH - Enables secure third-party integrations  

#### Documentation Summary

Comprehensive documentation for Laravel Sanctum integration strategy, covering SPA authentication, API token management, token abilities, and security best practices.

**Documentation Added:**
- SYSTEM_ARCHITECTURE.md Section 13.10 - "API Authentication Strategy"

**Key Components:**

1. **SPA Authentication (Session-Based)** - Already working
2. **API Token Authentication** - Ready for implementation
3. **Token Abilities** - Fine-grained permissions
4. **Rate Limiting** - Separate limits for API vs web
5. **Security Best Practices** - Token storage, rotation, expiration
6. **Testing Patterns** - Sanctum test helpers

#### Installation Steps (When Ready)

```bash
# 1. Install Sanctum
composer require laravel/sanctum

# 2. Publish configuration
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"

# 3. Run migrations
php artisan migrate

# 4. Add API guard to config/auth.php
'guards' => [
    'api' => [
        'driver' => 'sanctum',
        'provider' => 'users',
    ],
],
```

#### Example Token Usage

```php
// Create token with abilities
$token = $user->createToken('Mobile App', [
    'tickets:read',
    'tickets:create',
    'clients:read',
])->plainTextToken;

// Protect routes
Route::middleware(['auth:sanctum', 'ability:tickets:create'])
    ->post('/api/v1/tickets', [ApiTicketController::class, 'store']);

// API rate limiting
Route::prefix('api')
    ->middleware('throttle:60,1') // 60 per minute
    ->group(function () { ... });
```

#### Success Criteria

✅ Comprehensive documentation complete  
✅ Installation steps documented  
✅ Token management patterns defined  
✅ Security best practices documented  
✅ Testing patterns provided  

---

### ✅ 3.7 Centralized Error Tracking - COMPLETE

**⏱️ Completed:** February 9, 2026  
**🎯 Goal:** Document Sentry integration for production error monitoring  
**⚠️ Impact:** CRITICAL - Real-time error visibility and alerting  

#### Documentation Summary

Comprehensive documentation for Sentry integration, covering error tracking, performance monitoring, slow query detection, and alert configuration.

**Documentation Added:**
- SYSTEM_ARCHITECTURE.md Section 13.11 - "Centralized Error Tracking & Monitoring"

**Key Features Documented:**

1. **Real-Time Error Tracking** - Stack traces, breadcrumbs, user context
2. **Performance Monitoring** - Transaction tracking, span analysis
3. **Slow Query Detection** - Automatic capture of queries >1s
4. **Alert Configuration** - Slack, email, PagerDuty integrations
5. **Release Tracking** - Correlate errors with deployments
6. **Issue Grouping** - Custom fingerprinting strategies

#### Installation Steps (When Ready)

```bash
# 1. Install Sentry SDK
composer require sentry/sentry-laravel

# 2. Publish configuration
php artisan vendor:publish --provider="Sentry\Laravel\ServiceProvider"

# 3. Configure environment
SENTRY_LARAVEL_DSN=https://[KEY]@[ORG].ingest.sentry.io/[PROJECT]
SENTRY_TRACES_SAMPLE_RATE=0.2  # 20% for performance monitoring
SENTRY_ENVIRONMENT=production
```

#### Context Enrichment Example

```php
// Add business context to errors
\Sentry\configureScope(function (\Sentry\State\Scope $scope): void {
    $scope->setUser([
        'id' => $user->id,
        'email' => $user->email,
        'role' => $user->role,
    ]);
    
    $scope->setTag('module', 'PIB');
    $scope->setTag('client_id', $clientId);
});

// Performance tracking
$span = \Sentry\startSpan(['op' => 'invoice.generate']);
// ... expensive operation ...
$span->finish();
```

#### Slow Query Detection

```php
// Already documented in AppServiceProvider pattern
DB::listen(function ($query) {
    if ($query->time > 1000) {
        \Sentry\captureMessage("Slow query: {$query->sql}");
    }
});
```

#### Success Criteria

✅ Comprehensive Sentry documentation complete  
✅ Installation and configuration steps documented  
✅ Context enrichment patterns defined  
✅ Performance monitoring strategy documented  
✅ Alert configuration examples provided  

---

### ✅ 3.8 Transaction Management Verification - COMPLETE

**⏱️ Completed:** February 9, 2026  
**🎯 Goal:** Verify and document transaction usage across codebase  
**⚠️ Impact:** HIGH - Ensures data integrity for financial operations  

#### Verification Summary

Comprehensive audit of database transaction usage throughout the codebase, confirming correct implementation patterns for financial operations, multi-table updates, and race condition prevention.

**Documentation Added:**
- SYSTEM_ARCHITECTURE.md Section 13.12 - "Transaction Management Verification & Best Practices"

**Audit Results:**

✅ **Financial Operations** (Payment Module)
- ClientCreditService uses transactions with `lockForUpdate()`
- Prevents race conditions in balance calculations
- Proper atomic updates with balance snapshots

✅ **Multi-Table Operations** (CRM Actions)
- MergeCustomersAction properly wraps related updates
- Rollback-safe on any failure
- Event integration works correctly

✅ **Contract Operations** (ContractManager)
- Quote creation, revision, conversion use transactions
- Line items created atomically with parent
- Totals recalculated within transaction scope

✅ **Counter Operations** (SoftwareSubscriptions)
- SubscriptionCounterService uses `lockForUpdate()`
- Prevents over-assignment of licenses
- Atomic increment with assignment record creation

✅ **Idempotent Processing**
- IdempotentListener properly wraps check + process + mark
- Prevents duplicate event processing

#### Best Practices Documented

**When to Use Transactions:**
```php
// ✅ Financial operations
DB::transaction(function () {
    $invoice->update(['status' => 'paid']);
    $creditLedger->recordPayment($amount);
});

// ✅ Multi-table updates
DB::transaction(function () {
    $quote->update(['status' => 'approved']);
    $contract = Contract::createFromQuote($quote);
});

// ✅ Counter operations with locking
DB::transaction(function () use ($asset) {
    $asset = Asset::where('id', $asset->id)->lockForUpdate()->first();
    $asset->increment('usage_count');
});
```

**Anti-Patterns to Avoid:**
```php
// ❌ External API calls inside transactions
DB::transaction(function () {
    $invoice = Invoice::create([...]);
    $this->helcimService->charge($invoice); // Cannot rollback!
});

// ❌ Long-running operations holding locks
DB::transaction(function () {
    foreach ($clients as $client) {
        $this->generateReport($client); // Too slow!
    }
});
```

#### Files Verified

- ✅ `Modules/Payment/Services/ClientCreditService.php`
- ✅ `Modules/ContractManager/Services/QuoteService.php`
- ✅ `Modules/ContractManager/Services/ContractService.php`
- ✅ `Modules/SoftwareSubscriptions/Services/SubscriptionCounterService.php`
- ✅ `app/Actions/MergeCustomersAction.php`
- ✅ `app/Listeners/IdempotentListener.php`

#### Success Criteria

✅ Comprehensive transaction audit completed  
✅ Correct usage verified in financial operations  
✅ Best practices and anti-patterns documented  
✅ Testing patterns for atomicity provided  
✅ Recommendations for minor improvements noted  

---

## Testing & Verification

### Local Testing Checklist

Before deploying ANY change:

```bash
# 1. Run all tests (including architecture tests)
php artisan test

# 2. Run architecture tests specifically
./vendor/bin/pest tests/Architecture/

# 3. Run cache invalidation tests
./vendor/bin/pest tests/Feature/CacheInvalidationTest.php

# 4. Run interface segregation tests
./vendor/bin/pest tests/Feature/InterfaceSegregationTest.php

# 5. Run static analysis
./vendor/bin/phpstan analyse

# 6. Check code style
./vendor/bin/pint --test

# 7. Verify no unused dependencies
php composer-unused

# 8. Check for security vulnerabilities
composer audit
```

### Staging Testing Protocol

After deploying to staging:

**1. Smoke Tests (5 minutes)**
```bash
# Test health checks
curl https://staging.yourapp.com/health
curl https://staging.yourapp.com/health/detailed

# Test API endpoints
curl -H "Authorization: Bearer $TOKEN" \
     https://staging.yourapp.com/api/v1/clients

# Test metrics endpoint
curl https://staging.yourapp.com/metrics
```

**2. Queue Isolation Tests (10 minutes)**
```bash
# SSH to staging
ssh staging.yourapp.com

# Trigger bulk invoice generation
php artisan pib:generate-bulk-invoices --count=1000

# In another terminal, send password reset
php artisan tinker
>>> $user = User::first();
>>> $user->sendPasswordResetNotification($token);

# Verify notification sent within 1 minute (not delayed)
tail -f storage/logs/laravel.log | grep "password.reset"
```

**3. Event Listener Tests (10 minutes)**
```bash
# Trigger contract revision
php artisan tinker
>>> $contract = Contract::find(1);
>>> event(new ContractRevised($contract, now()));

# Verify proration job queued
php artisan queue:monitor billing
# Should see ApplyProrationJob

# Check Laravel Telescope
# http://staging.yourapp.com/telescope/events
```

**4. Caching Tests (10 minutes)**
```bash
# Clear cache
php artisan cache:clear

# Load client entitlements (should hit database)
curl -H "Authorization: Bearer $TOKEN" \
     https://staging.yourapp.com/api/v1/clients/1

# Load again (should hit cache - faster)
curl -H "Authorization: Bearer $TOKEN" \
     https://staging.yourapp.com/api/v1/clients/1

# Check cache hit ratio in metrics
curl https://staging.yourapp.com/metrics | grep cache_hit
```

**5. Observability Tests (5 minutes)**
```bash
# Trigger test error
curl https://staging.yourapp.com/trigger-error

# Check Sentry dashboard
# https://sentry.io/organizations/your-org/issues/

# Verify error appeared within 30 seconds
```

### Load Testing (Optional)

Use Apache Bench or similar:

```bash
# Test API performance under load
ab -n 1000 -c 10 \
   -H "Authorization: Bearer $TOKEN" \
   https://staging.yourapp.com/api/v1/clients

# Expected results:
# - p95 response time < 500ms
# - No errors
# - Consistent throughput
```

---

## Deployment Procedures

### P0: Queue Isolation Deployment

**1. Pre-Deployment (15 min before)**
```bash
# 1. Backup production database
ssh production.yourapp.com
sudo /root/backup-scripts/full-backup.sh

# 2. Scale up queue workers (prepare for traffic)
sudo supervisorctl scale freescout-worker-default:10

# 3. Notify team in Slack
curl -X POST https://hooks.slack.com/... \
     -d '{"text":"🚀 Deploying queue isolation fix at 3:00 PM"}'
```

**2. Deployment (5 min)**
```bash
# Deploy code
git push production main

# Or if using CI/CD:
# GitHub Actions will automatically deploy

# Verify deployment
ssh production.yourapp.com
cd /var/www/html
git log -1 # Should show latest commit
```

**3. Post-Deployment (15 min)**
```bash
# 1. Add billing queue workers
ssh production.yourapp.com
sudo vim /etc/supervisor/conf.d/freescout-worker.conf
# Add [program:freescout-worker-billing] section

# Reload supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl status

# 2. Monitor queues
watch -n 5 'php artisan queue:monitor billing,default'

# 3. Check logs for errors
tail -f storage/logs/laravel.log | grep ERROR

# 4. Verify no failed jobs
php artisan queue:failed
```

**4. Verification (30 min)**
```bash
# Trigger small test batch (50 invoices)
php artisan pib:generate-bulk-invoices --count=50 --dry-run=false

# Verify:
# - Invoices generated within 2 minutes
# - No delays in system notifications
# - Queue depth stays reasonable (<1000)

# If successful, trigger full batch
php artisan pib:generate-bulk-invoices --count=10000
```

**5. Rollback Plan (if needed)**
```bash
# If issues detected:
git revert HEAD
git push production main

# Stop billing workers
sudo supervisorctl stop freescout-worker-billing:*

# All jobs will fall back to default queue
```

### P1: Observability Stack Deployment

**1. Pre-Deployment**
```bash
# Create Sentry project
# https://sentry.io/organizations/your-org/projects/new/

# Get DSN: https://xxx@sentry.io/yyy

# Add to .env on production
ssh production.yourapp.com
vim /var/www/html/.env
# Add:
# SENTRY_LARAVEL_DSN=https://xxx@sentry.io/yyy
# SENTRY_TRACES_SAMPLE_RATE=0.1
```

**2. Deployment**
```bash
# Deploy code
git push production main

# Clear config cache
ssh production.yourapp.com
cd /var/www/html
php artisan config:clear
php artisan config:cache
```

**3. Verification**
```bash
# Trigger test error
ssh production.yourapp.com
php artisan tinker
>>> throw new \Exception('Sentry integration test');

# Check Sentry dashboard (should appear within 30 seconds)
# https://sentry.io/organizations/your-org/issues/

# Check metrics endpoint
curl https://production.yourapp.com/metrics

# Should return Prometheus format data
```

---

## Troubleshooting

### Queue Issues

**Problem: Jobs not processing**
```bash
# Check workers running
sudo supervisorctl status

# Check queue connection
php artisan tinker
>>> Queue::connection()->size('billing')
>>> Queue::connection()->size('default')

# Check failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

**Problem: Queue depth growing**
```bash
# Scale up workers
sudo supervisorctl scale freescout-worker-billing:20

# Check for deadlocked jobs
php artisan queue:monitor billing --max=10000

# If all else fails, restart workers
sudo supervisorctl restart freescout-worker-billing:*
```

### Cache Issues

**Problem: Stale cache data**
```bash
# Clear all caches
php artisan cache:clear

# Clear specific cache key
php artisan tinker
>>> Cache::forget('billing:entitlement:123:current')

# Warm cache for active clients
php artisan cache:warm --clients=500
```

**Problem: Low cache hit ratio**
```bash
# Check cache connectivity
php artisan tinker
>>> Cache::put('test', 'value', 60)
>>> Cache::get('test')
# Should return 'value'

# Check Redis
redis-cli
> PING
> KEYS billing:*
```

### Sentry Issues

**Problem: Errors not appearing in Sentry**
```bash
# Check DSN configured
grep SENTRY_LARAVEL_DSN .env

# Test manually
php artisan tinker
>>> \Sentry\captureMessage('Test from console');

# Check logs for Sentry errors
grep -i sentry storage/logs/laravel.log
```

**Problem: Too many errors flooding Sentry**
```bash
# Reduce sample rate
vim .env
# Change: SENTRY_TRACES_SAMPLE_RATE=0.01 (1%)

# Add more exceptions to ignore list
vim config/sentry.php
# Add to 'ignore_exceptions' array
```

### Performance Issues

**Problem: Slow response times**
```bash
# Enable query logging
config('database.connections.mysql.log_queries', true)

# Check for N+1 queries in Telescope
# http://localhost/telescope/queries

# Check cache hit ratio
tail -f storage/logs/laravel.log | grep "Cache miss"

# Profile with Xdebug
php artisan tinker
>>> app('debugbar')->enable()
```

---

## Rollback Procedures

### General Rollback (Any Change)

```bash
# 1. Revert code
git revert HEAD
git push origin main

# 2. Clear caches
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 3. Restart services
sudo supervisorctl restart all
sudo systemctl restart php8.1-fpm
sudo systemctl restart nginx

# 4. Verify rollback
curl http://localhost/health

# 5. Notify team
echo "🔴 Rolled back deployment due to [reason]" | \
     slack-cli chat send --channel=#deployments
```

### Specific Rollback: Queue Isolation

```bash
# 1. Stop billing workers
sudo supervisorctl stop freescout-worker-billing:*

# 2. Revert code changes
git revert $(git log --grep="queue isolation" --format="%H" -1)
git push production main

# 3. Restart default workers
sudo supervisorctl restart freescout-worker-default:*

# 4. Monitor
php artisan queue:monitor default
```

### Specific Rollback: Observability

```bash
# 1. Remove Sentry DSN from .env
ssh production.yourapp.com
vim /var/www/html/.env
# Comment out: # SENTRY_LARAVEL_DSN=...

# 2. Clear config cache
php artisan config:clear
php artisan config:cache

# 3. Sentry will stop receiving events (code stays in place)
```

---

## Success Metrics & Monitoring

### P0: Queue Isolation Success Metrics

**Before:**
- 🔴 Notification delay during bulk billing: **2-4 hours**
- 🔴 Queue depth: **10,000+ jobs**
- 🔴 User complaints: **50+ tickets per billing cycle**

**After (Target):**
- ✅ Notification delay: **< 1 minute** (always)
- ✅ Queue depth: **< 1,000 jobs** (per queue)
- ✅ User complaints: **0 tickets**

**Monitoring:**
```bash
# Check queue depth every 5 minutes
watch -n 300 'php artisan queue:monitor billing,default'

# Alert if billing queue > 5000
if [ $(php artisan queue:size billing) -gt 5000 ]; then
    echo "⚠️  Billing queue depth critical" | slack-cli send
fi
```

### P1: Caching Success Metrics

**Before:**
- 🔴 Cache hit ratio: **~30%**
- 🔴 Dashboard load time: **800-1200ms** (p95)
- 🔴 Database queries: **500/sec**

**After (Target):**
- ✅ Cache hit ratio: **> 80%**
- ✅ Dashboard load time: **< 400ms** (p95)
- ✅ Database queries: **< 100/sec**

**Monitoring:**
```bash
# Check cache hit ratio daily
php artisan tinker
>>> $hits = Redis::get('cache:hits');
>>> $misses = Redis::get('cache:misses');
>>> $ratio = $hits / ($hits + $misses);
>>> echo "Cache hit ratio: " . ($ratio * 100) . "%";
```

### P1: Observability Success Metrics

**Before:**
- 🔴 MTTR (Mean Time To Resolution): **4-8 hours**
- 🔴 Error visibility: **~50%** (only what users report)
- 🔴 Performance insights: **None**

**After (Target):**
- ✅ MTTR: **< 15 minutes**
- ✅ Error visibility: **100%** (all errors tracked)
- ✅ Performance insights: **Complete** (P50/P95/P99 tracked)

**Monitoring:**
Check Sentry dashboard daily:
- Issues: https://sentry.io/organizations/your-org/issues/
- Performance: https://sentry.io/organizations/your-org/performance/

---

## Timeline & Task Assignments

> **STATUS BOARD — Updated Feb 8, 2026**
>
> | Task | Owner | Status | Tests Enabled |
> |------|-------|--------|---------------|
> | P0.1 Queue Isolation | **Agent B** | 🔲 Not Started | QueueIsolationTest (Feature) |
> | P0.2 Transaction Boundaries | ✅ Already Documented | N/A | N/A |
> | P1.1 Missing Event Listeners | **Agent A (current)** | 🔄 In Progress | EventDrivenIntegrationPestTest (5 todos), CrossModuleDataOwnershipPestTest (1 todo) |
> | P1.2 Caching Strategy | **Agent B** | 🔲 Not Started | Performance tests (Feature) |
> | P1.3 Observability Stack | **Agent B** | 🔲 Not Started | Health check smoke tests |
> | P1.4 API Versioning | **Agent B** | 🔲 Not Started | RBACSecurityPestTest API token todo |
> | P2.1 Interface Segregation | **Agent B** | 🔲 Not Started | Architecture enforcement tests |
>
> **Agent A focus:** P1.1 Event Listeners + remaining Browser test implementations (billing, commerce, helpdesk)
> **Agent B focus:** P0.1, P1.2, P1.3, P1.4, P2.1 (infrastructure & architecture)

### Week 1 (Feb 8-15, 2026)

**Monday-Tuesday:**
- [ ] P0.1: Queue isolation fix (Agent B) - 2 hours
- [ ] P0.1: Write tests (Agent B) - 1 hour
- [ ] P0.1: Deploy to staging (Agent B) - 1 hour

**Wednesday:**
- [ ] P0.1: Production deployment (Team) - 2 hours
- [ ] P0.1: Monitor + verify (Team) - 4 hours

**Thursday-Friday:**
- [x] P1.1: Event listeners (Agent A) - 6 hours ← **IN PROGRESS**
- [ ] P1.1: Write tests (Agent A) - 2 hours

### Week 2 (Feb 15-22, 2026)

**Monday:**
- [ ] P1.1: Deploy event listeners to staging (Agent A) - 2 hours

**Tuesday-Wednesday:**
- [ ] P1.2: Implement caching (Agent B) - 8 hours
- [ ] P1.2: Add cache warming (Agent B) - 2 hours
- [ ] P1.2: Write tests (Agent B) - 2 hours

**Thursday-Friday:**
- [ ] P1.3: Set up Sentry (Agent B) - 4 hours
- [ ] P1.3: Add instrumentation (Agent B) - 4 hours

### Week 3 (Feb 22-29, 2026)

**Monday:**
- [ ] P1.3: Deploy observability to production (Team) - 2 hours

**Tuesday-Thursday:**
- [ ] P1.4: API versioning (Agent B) - 8 hours
- [ ] P1.4: OpenAPI docs (Agent B) - 4 hours

**Friday:**
- [ ] P2.1: Interface segregation (Agent B) - 6 hours

### Week 4 (Feb 29-Mar 7, 2026)

**Monday-Wednesday:**
- [ ] Buffer time for unexpected issues
- [ ] Code review and refinement
- [ ] Documentation updates

**Thursday:**
- [ ] Retrospective meeting
- [ ] Measure success metrics
- [ ] Plan next quarter improvements

**Friday:**
- [ ] Team presentation of results
- [ ] Celebrate wins 🎉

---

## Related Documents

- [SYSTEM_ARCHITECTURE.md](SYSTEM_ARCHITECTURE.md) - Complete architecture reference
- [ARCHITECTURAL_BEST_PRACTICES_REVIEW.md](ARCHITECTURAL_BEST_PRACTICES_REVIEW.md) - Detailed assessment
- [DOCUMENTATION_INDEX.md](../../DOCUMENTATION_INDEX.md) - All documentation

---

**Document Version:** 1.0  
**Last Updated:** February 8, 2026  
**Maintainers:** Platform Team  
**Next Review:** March 8, 2026

---

## Quick Reference Commands

```bash
# Start here
git checkout -b feature/architecture-improvements

# Run before any commit
php artisan test && ./vendor/bin/phpstan analyse && ./vendor/bin/pint --test

# Deploy to staging
git push origin feature/architecture-improvements
# (Follow team's staging deployment process)

# Deploy to production
git push production main
# (Requires approval + monitoring)

# Monitor production
ssh production.yourapp.com
watch -n 5 'php artisan queue:monitor billing,default'
tail -f storage/logs/laravel.log

# Rollback if needed
git revert HEAD && git push production main

# Check health
curl https://production.yourapp.com/health/detailed
```

**Remember:** Always test in staging first. Never deploy on Fridays. 😊
