<?php

declare(strict_types=1);

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Send follow-up reminders daily at 9:00 AM
        // Checks for conversations with due follow-ups and sends email/database notifications
        $timezone = config('app.timezone') ?? 'UTC';
        if (!is_string($timezone)) {
            $timezone = 'UTC';
        }
        
        $schedule->command('followup:send-reminders')
            ->dailyAt('09:00')
            ->timezone($timezone)
            ->onSuccess(function () {
                Log::info('Follow-up reminders scheduled task completed successfully');
            })
            ->onFailure(function () {
                Log::error('Follow-up reminders scheduled task failed');
            });
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
