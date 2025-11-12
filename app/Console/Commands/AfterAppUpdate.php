<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AfterAppUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'freescout:after-app-update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run commands after application has been updated';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Running post-update tasks...');

        // Clear all caches
        $this->call('freescout:clear-cache');

        // Run migrations
        $this->info('Running database migrations...');
        // In testing, use pretend mode to avoid conflicts with RefreshDatabase
        $options = ['--force' => true];
        if (app()->environment('testing')) {
            $options['--pretend'] = true;
        }
        $this->call('migrate', $options);

        // Restart queue workers
        $this->info('Restarting queue workers...');
        $this->call('queue:restart');

        $this->info('Post-update tasks completed successfully!');

        return 0;
    }
}
