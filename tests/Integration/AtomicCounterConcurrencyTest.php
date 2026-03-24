<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Services\AtomicCounterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * CRITICAL TEST: Verifies AtomicCounterService prevents lost updates
 * under concurrent load.
 *
 * This test is a BLOCKER for Phase 2.3 (Asset Management).
 */
class AtomicCounterConcurrencyTest extends TestCase
{
    protected string $originalDefaultConnection;

    /**
     * @param  array<int|string, Process>  $processes
     */
    protected function stopRunningProcesses(array $processes): void
    {
        foreach ($processes as $process) {
            if ($process->isRunning()) {
                $process->stop(1);
            }
        }
    }

    protected function assertProcessSucceeded(Process $process, int|string $index): void
    {
        if (! $process->isSuccessful()) {
            $output = $process->getOutput();
            $error = $process->getErrorOutput();
            $this->fail("Process {$index} failed:\nOutput: {$output}\nError: {$error}");
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Save original default connection so we can restore it in tearDown
        $this->originalDefaultConnection = config('database.default');

        // Use file-based SQLite for concurrent process testing
        $dbPath = database_path('testing.sqlite');

        // Create fresh test database
        if (file_exists($dbPath)) {
            unlink($dbPath);
        }
        touch($dbPath);

        // Temporarily change default connection
        config(['database.default' => 'sqlite_testing']);
        config(['database.connections.sqlite_testing' => [
            'driver' => 'sqlite',
            'database' => $dbPath,
            'prefix' => '',
            'foreign_key_constraints' => true,
            'busy_timeout' => 5000,
            'journal_mode' => 'WAL',
            'synchronous' => 'NORMAL',
        ]]);

        // Run migrations on the file-based database
        $this->artisan('migrate:fresh', [
            '--database' => 'sqlite_testing',
            '--force' => true,
        ]);

        // Switch DB facade to use the file-based test database.
        // IMPORTANT: Do NOT purge the 'sqlite' connection — RefreshDatabase
        // holds an open transaction on the in-memory :memory: connection.
        // Purging it would orphan that transaction, causing VACUUM errors
        // and "table migrations already exists" cascading failures.
        DB::setDefaultConnection('sqlite_testing');
    }

    protected function tearDown(): void
    {
        // Clean up test database
        $dbPath = database_path('testing.sqlite');
        if (file_exists($dbPath)) {
            unlink($dbPath);
        }

        // Restore original default connection BEFORE parent::tearDown()
        // so RefreshDatabase's rollback callback operates on the correct connection.
        // Without this, the in-memory sqlite transaction is never rolled back,
        // RefreshDatabaseState::$migrated gets reset, and ALL subsequent tests
        // fail with "table migrations already exists".
        DB::purge('sqlite_testing');
        config(['database.default' => $this->originalDefaultConnection]);
        DB::setDefaultConnection($this->originalDefaultConnection);

        parent::tearDown();
    }

    /**
     * Test that concurrent increments do not lose updates
     *
     * CRITICAL: This test must pass before Phase 2 can begin
     *
     * Setup:
     * - Create counter with initial value 0
     * - Spawn 20 parallel processes
     * - Each process increments counter 50 times
     * - Expected final value: 20 * 50 = 1000
     *
     * If this test fails, there are lost updates (race condition)
     */
    public function test_concurrent_increments_no_lost_updates(): void
    {
        // Create test counter
        DB::table('test_counters')->insert([
            'id' => 1,
            'count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $processCount = 20;
        $iterationsPerProcess = 50;
        $expectedFinalCount = $processCount * $iterationsPerProcess; // 1000

        // Spawn parallel processes
        $processes = [];
        $dbPath = database_path('testing.sqlite');

        for ($i = 0; $i < $processCount; $i++) {
            $process = new Process([
                'php',
                base_path('artisan'),
                'test:atomic-counter-increment',
                "--iterations={$iterationsPerProcess}",
                '--counter-id=1',
            ]);

            // Set environment to use test database
            $process->setEnv([
                'APP_ENV' => 'testing',
                'DB_CONNECTION' => 'sqlite',
                'DB_DATABASE' => $dbPath,
            ]);

            $process->setTimeout(30);
            $processes[] = $process;
        }

        try {
            // Start all processes simultaneously
            $this->info("Starting {$processCount} parallel processes...");
            foreach ($processes as $process) {
                $process->start();
            }

            // Wait for all processes to complete
            foreach ($processes as $i => $process) {
                $process->wait();
                $this->assertProcessSucceeded($process, $i);
            }

            // Verify final count
            $finalCount = DB::table('test_counters')->where('id', 1)->value('count');

            $this->assertEquals(
                $expectedFinalCount,
                $finalCount,
                'CRITICAL FAILURE: Concurrent increments lost updates! '.
                "Expected {$expectedFinalCount} but got {$finalCount}. ".
                'This indicates a race condition in AtomicCounterService.'
            );

            $this->info("✅ PASSED: All {$expectedFinalCount} increments were atomic!");
        } finally {
            $this->stopRunningProcesses($processes);
        }
    }

    /**
     * Test that concurrent increments across different counters don't interfere
     */
    public function test_concurrent_increments_multiple_counters(): void
    {
        // Create multiple test counters
        for ($i = 1; $i <= 5; $i++) {
            DB::table('test_counters')->insert([
                'id' => $i,
                'count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Spawn parallel processes
        $processes = [];
        $iterationsPerProcess = 20;
        $dbPath = database_path('testing.sqlite');

        try {
            // Start 5 processes, each incrementing a different counter
            for ($counterId = 1; $counterId <= 5; $counterId++) {
                $process = new Process([
                    'php',
                    base_path('artisan'),
                    'test:atomic-counter-increment',
                    "--iterations={$iterationsPerProcess}",
                    "--counter-id={$counterId}",
                ]);

                $process->setEnv([
                    'APP_ENV' => 'testing',
                    'DB_CONNECTION' => 'sqlite',
                    'DB_DATABASE' => $dbPath,
                ]);

                $process->setTimeout(30);
                $processes[$counterId] = $process;
                $process->start();
            }

            // Wait for completion
            foreach ($processes as $counterId => $process) {
                $process->wait();
                $this->assertProcessSucceeded($process, $counterId);
            }

            // Verify each counter
            for ($counterId = 1; $counterId <= 5; $counterId++) {
                $count = DB::table('test_counters')->where('id', $counterId)->value('count');

                $this->assertEquals(
                    $iterationsPerProcess,
                    $count,
                    "Counter {$counterId} has incorrect value"
                );
            }
        } finally {
            $this->stopRunningProcesses($processes);
        }
    }

    /**
     * Test decrement operations under concurrent load
     */
    public function test_concurrent_decrements(): void
    {
        // Create counter with initial value 1000
        DB::table('test_counters')->insert([
            'id' => 1,
            'count' => 1000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Decrement 500 times in parallel (10 processes × 50 iterations)
        $processes = [];
        $dbPath = database_path('testing.sqlite');

        try {
            for ($i = 0; $i < 10; $i++) {
                $process = new Process([
                    'php',
                    base_path('artisan'),
                    'test:atomic-counter-decrement',
                    '--iterations=50',
                    '--counter-id=1',
                ]);

                $process->setEnv([
                    'APP_ENV' => 'testing',
                    'DB_CONNECTION' => 'sqlite',
                    'DB_DATABASE' => $dbPath,
                ]);

                $process->setTimeout(30);
                $processes[] = $process;
                $process->start();
            }

            foreach ($processes as $i => $process) {
                $process->wait();
                $this->assertProcessSucceeded($process, $i);
            }

            $finalCount = DB::table('test_counters')->where('id', 1)->value('count');

            $this->assertEquals(
                500, // 1000 - (10 * 50)
                $finalCount,
                'Concurrent decrements lost updates'
            );
        } finally {
            $this->stopRunningProcesses($processes);
        }
    }

    /**
     * Helper to output info during test
     */
    protected function info(string $message): void
    {
        fwrite(STDOUT, $message.PHP_EOL);
    }
}
