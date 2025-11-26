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

        // Ensure we don't use cached files in tests
        clearstatcache();
        $cacheFiles = [
            $app->getCachedRoutesPath(),
            $app->getCachedConfigPath(),
            $app->getCachedPackagesPath(),
            $app->getCachedServicesPath(),
        ];

        foreach ($cacheFiles as $file) {
            if (file_exists($file)) {
                @unlink($file);
            }
        }

        $app->make(Kernel::class)->bootstrap();

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
