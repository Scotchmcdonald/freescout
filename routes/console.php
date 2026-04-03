<?php

use App\Jobs\RenewExpiringWebhooksJob;
use Illuminate\Support\Facades\Schedule;

// Schedule automatic email fetching
Schedule::command('freescout:fetch-emails')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground();

// Schedule daily security audit — runs composer audit + npm audit and emails admin on findings
Schedule::command('security:audit')
    ->dailyAt('08:00')
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground();

// Weekly browserslist (caniuse-lite) refresh — keeps CSS/JS compatibility data current
Schedule::command('browserslist:update')
    ->weeklyOn(1, '03:00') // Every Monday at 03:00
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground();

// Webhook: Daily webhook renewal check (renew channels expiring within 48 hours)
Schedule::job(RenewExpiringWebhooksJob::class)
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->onOneServer()
    ->name('renew-expiring-webhooks');

// Asset Management: Daily Reconciliation & High-Water Mark Snapshot (Phase 2.3)
Schedule::job(\Modules\AssetManagement\Jobs\ReconcileAssetsJob::class)->dailyAt('01:00');
Schedule::job(\Modules\AssetManagement\Jobs\RecordDailyAssetCountJob::class)->dailyAt('23:55');

// CRM: Monthly client service metrics calculation (1st of each month at 2:00 AM)
Schedule::job(\Modules\Crm\Jobs\CalculateClientServiceMetricsJob::class)
    ->monthlyOn(1, '02:00')
    ->withoutOverlapping()
    ->onOneServer();

// PIB: Monthly time entry aggregation into service_usage (1st of each month at 3:00 AM)
Schedule::job(\Modules\PIB\Jobs\MonthEndTimeAggregationJob::class)
    ->monthlyOn(1, '03:00')
    ->withoutOverlapping()
    ->onOneServer();

// PIB: Daily recurring invoice generation (1:00 AM) — with duplicate-job guard for concurrent workers
Schedule::job(\Modules\PIB\Jobs\GenerateRecurringInvoicesJob::class)
    ->dailyAt('01:00')
    ->withoutOverlapping()
    ->onOneServer();

// MiddleMan: Daily data lifecycle pruning — prevents log/intercept table disk exhaustion.
// Uses per-table retention windows from middleman.prune config.
Schedule::command('middleman:prune')
    ->dailyAt('03:30')
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground()
    ->name('middleman-prune');
