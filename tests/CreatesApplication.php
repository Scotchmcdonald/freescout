<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Artisan;

trait CreatesApplication
{
    /**
     * Isolate module filesystem artifacts for each parallel worker.
     */
    protected function configureModuleTestEnvironment(): void
    {
        $token = preg_replace('/[^a-z0-9]/', '', strtolower((string) (env('TEST_TOKEN') ?? getmypid())));
        $baseTestingPath = dirname(__DIR__).'/storage/framework/testing/modules';

        if (! is_dir($baseTestingPath)) {
            mkdir($baseTestingPath, 0755, true);
        }

        $workerStatusesFile = $baseTestingPath.'/modules_statuses_'.$token.'.json';
        $baselineStatusesFile = dirname(__DIR__).'/modules_statuses.json';
        if (file_exists($baselineStatusesFile)) {
            copy($baselineStatusesFile, $workerStatusesFile);
        } else {
            file_put_contents($workerStatusesFile, '{}');
        }

        putenv('MODULES_STATUSES_FILE='.$workerStatusesFile);
        $_ENV['MODULES_STATUSES_FILE'] = $workerStatusesFile;
        $_SERVER['MODULES_STATUSES_FILE'] = $workerStatusesFile;
    }

    /**
     * Creates the application.
     */
    public function createApplication(): Application
    {
        $this->configureModuleTestEnvironment();

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
