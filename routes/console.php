<?php

use Illuminate\Support\Facades\Artisan;
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

// Asset Management: Daily Reconciliation & High-Water Mark Snapshot (Phase 2.3)
// TODO: Uncomment when AssetManagement module is implemented
// Schedule::job(new \Modules\AssetManagement\Jobs\ReconcileAssetsJob)->dailyAt('01:00');
// Schedule::job(new \Modules\AssetManagement\Jobs\RecordDailyAssetCountJob)->dailyAt('23:55');

// CRM: Monthly client service metrics calculation (1st of each month at 2:00 AM)
Schedule::job(new \Modules\Crm\Jobs\CalculateClientServiceMetricsJob)
    ->monthlyOn(1, '02:00')
    ->withoutOverlapping()
    ->onOneServer();

// PIB: Monthly time entry aggregation into service_usage (1st of each month at 3:00 AM)
Schedule::job(new \Modules\PIB\Jobs\MonthEndTimeAggregationJob)
    ->monthlyOn(1, '03:00')
    ->withoutOverlapping()
    ->onOneServer();

