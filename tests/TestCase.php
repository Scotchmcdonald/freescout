<?php

namespace Tests;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\ParallelTesting;
use Illuminate\Support\Facades\RateLimiter;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, WithFaker;

    // RefreshDatabase is NOT here. It is applied explicitly in:
    // - IntegrationTestCase (for integration tests)
    // - UnitTestCase (temporary, pending WS-C migration of DB-heavy unit tests)
    // - Feature/Browser/Integration tests via explicit Pest .use(RefreshDatabase::class) binding

    protected function setUp(): void
    {
        // Align memory limit with phpunit.xml (1024M sufficient for tests)
        ini_set('memory_limit', '1024M');

        parent::setUp();

        // Prevent accidental outbound HTTP in tests. Individual tests must
        // opt in with Http::fake() or explicitly allow real requests.
        Http::preventStrayRequests();

        // BEST PRACTICE: Isolate filesystem for parallel tests
        \Illuminate\Support\Facades\Storage::fake('local');
        \Illuminate\Support\Facades\Storage::fake('public');

        // BEST PRACTICE: Prevent actual mail sending in all tests
        \Illuminate\Support\Facades\Mail::fake();

        // BEST PRACTICE: Ensure RateLimiter uses the array cache store for test isolation.
        // During bootstrap, AppServiceProvider::boot() resolves the RateLimiter singleton
        // before CreatesApplication can apply config(['cache.default' => 'array']), so it
        // may end up backed by Redis (a persistent store). Rebinding it here ensures each
        // test starts with a clean, in-memory rate-limit counter.
        $this->app->instance(
            \Illuminate\Cache\RateLimiter::class,
            new \Illuminate\Cache\RateLimiter($this->app->make('cache')->driver('array'))
        );
        \Illuminate\Support\Facades\RateLimiter::clearResolvedInstance(\Illuminate\Cache\RateLimiter::class);
        $this->registerTestRateLimiters();

        // BEST PRACTICE: Robust mock for missing/broken Eventy package
        // This binds a Null Object to the container, preventing "Facade root not set" errors
        if (class_exists(\TorMorten\Eventy\Facades\Events::class)) {
            $nullEventy = new class
            {
                public function addFilter($tag, $callback, $priority = 10, $accepted_args = 1)
                {
                    return true;
                }

                public function addAction($tag, $callback, $priority = 10, $accepted_args = 1)
                {
                    return true;
                }

                public function filter($tag, $value)
                {
                    return $value;
                }

                public function action($tag, ...$args)
                {
                    return null;
                }
            };

            // Bind to common accessors
            $this->app->instance('eventy', $nullEventy);
            $this->app->instance(\TorMorten\Eventy\Events::class, $nullEventy);
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Force garbage collection every 150 tests to balance memory and performance
        // Frequent GC (every 50) causes overhead; too infrequent (300+) causes memory bloat
        static $testCount = 0;
        $testCount++;

        if ($testCount % 150 === 0 && gc_enabled()) {
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

            // Ensure database directory exists
            if (! file_exists(dirname($databasePath))) {
                mkdir(dirname($databasePath), 0755, true);
            }

            // Create empty database file for this worker
            if (! file_exists($databasePath)) {
                touch($databasePath);
            }

            // Point sqlite connection to this worker DB before running migrations.
            config(['database.connections.sqlite.database' => $databasePath]);

            // BEST PRACTICE: Run fresh migrations for each worker
            // This ensures perfect isolation and schema consistency
            Artisan::call('migrate:fresh', [
                '--database' => 'sqlite',
                '--force' => true,
                '--path' => 'database/migrations',
                '--realpath' => true,
            ]);
        });
    }

    /**
     * Restore named limiters after swapping the RateLimiter singleton in tests.
     */
    protected function registerTestRateLimiters(): void
    {
        RateLimiter::for('google_webhooks', function ($request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        RateLimiter::for('action1_webhooks', function ($request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        RateLimiter::for('action1_script_callbacks', function ($request) {
            return Limit::perMinute(30)->by($request->ip());
        });
    }
}
