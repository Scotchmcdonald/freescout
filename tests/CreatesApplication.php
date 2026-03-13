<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Artisan;

trait CreatesApplication
{
    /**
     * Creates the application.
     */
    public function createApplication(): Application
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        // Harden test isolation: always force a separate in-memory SQLite DB in test runs.
        if ($app->runningUnitTests()) {
            config([
                'database.default' => 'sqlite',
                'database.connections.sqlite.database' => ':memory:',
                'cache.default' => 'array',
                'session.driver' => 'array',
                'queue.default' => 'sync',
            ]);
        }

        // Mock optimization commands to prevent file generation during tests
        $commands = ['optimize', 'config:cache', 'route:cache', 'view:cache', 'event:cache'];
        foreach ($commands as $command) {
            Artisan::command($command, function () use ($command) {
                $this->info("Mocked {$command} executed successfully.");

                return 0;
            });
        }

        return $app;
    }
}
