<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;

class Update extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'freescout:update {--force : Force the operation to run when in production.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update FreeScout application';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (!$this->confirmToProceed()) {
            return 1;
        }

        $this->info('Starting FreeScout update...');

        // Increase memory limit for update process
        ini_set('memory_limit', '256M');

        try {
            // Run database migrations
            $this->info('Running database migrations...');
            $this->call('migrate', ['--force' => true]);

            // Clear all caches
            $this->info('Clearing caches...');
            $this->call('cache:clear');
            $this->call('config:clear');
            $this->call('route:clear');
            $this->call('view:clear');

            // Optimize application
            $this->info('Optimizing application...');
            $this->call('optimize');

            // Run post-update tasks
            $this->info('Running post-update tasks...');
            $this->call('freescout:after-app-update');

            $this->info('Update completed successfully!');

            return 0;
        } catch (\Exception $e) {
            $this->error('Error occurred during update: ' . $e->getMessage());
            return 1;
        }
    }
}
