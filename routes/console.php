<?php

use App\Jobs\RenewExpiringWebhooksJob;
use Illuminate\Support\Facades\Schedule;

// Schedule automatic email fetching
Schedule::command('freescout:fetch-emails')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground();

// Schedule daily security audit
Schedule::command('security:audit')
    ->dailyAt('08:00')
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
