<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\ParallelTesting;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, WithFaker;
    
    protected function setUp(): void
    {
        // FORCE MEMORY LIMIT
        ini_set('memory_limit', '4096M');

        parent::setUp();
        
        // BEST PRACTICE: Isolate filesystem for parallel tests
        \Illuminate\Support\Facades\Storage::fake('local');
        \Illuminate\Support\Facades\Storage::fake('public');

        // BEST PRACTICE: Robust mock for missing/broken Eventy package
        // This binds a Null Object to the container, preventing "Facade root not set" errors
        if (class_exists(\TorMorten\Eventy\Facades\Events::class)) {
            $nullEventy = new class {
                public function addFilter($tag, $callback, $priority = 10, $accepted_args = 1) { return true; }
                public function addAction($tag, $callback, $priority = 10, $accepted_args = 1) { return true; }
                public function filter($tag, $value) { return $value; }
                public function action($tag, ...$args) { return null; }
            };
            
            // Bind to common accessors
            $this->app->instance('eventy', $nullEventy);
            $this->app->instance(\TorMorten\Eventy\Events::class, $nullEventy);
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Force garbage collection every 50 tests to free memory
        // More frequent GC causes transaction state issues
        static $testCount = 0;
        $testCount++;
        
        if ($testCount % 50 === 0 && gc_enabled()) {
            gc_collect_cycles();
        }
    }

    /**
     * Setup parallel testing support for SQLite databases.
     * This ensures each parallel worker gets its own database file.
     */
    protected function setUpTraits()
    {
        $uses = parent::setUpTraits();
        
        // Configure parallel testing to use separate databases
        if (ParallelTesting::token()) {
            $this->setupParallelDatabase();
        }
        
        return $uses;
    }
    
    /**
     * Configure database for parallel testing.
     * Creates separate SQLite file for each worker.
     */
    protected function setupParallelDatabase(): void
    {
        ParallelTesting::setUpTestDatabase(function (string $database, string $token) {
            $databasePath = database_path("testing_{$token}.sqlite");
            $basePath = database_path("testing.sqlite");
            
            // Ensure database directory exists
            if (!file_exists(dirname($databasePath))) {
                mkdir(dirname($databasePath), 0755, true);
            }

            // Create empty database file for this worker
            if (!file_exists($databasePath)) {
                touch($databasePath);
            }
            
            // BEST PRACTICE: Run fresh migrations for each worker
            // This ensures perfect isolation and schema consistency
            Artisan::call('migrate:fresh', [
                '--database' => 'sqlite',
                '--force' => true,
                '--path' => 'database/migrations',
                '--realpath' => true,
            ]);
            
            // Update config to use the worker-specific database
            config(['database.connections.sqlite.database' => $databasePath]);
        });
    }
}
