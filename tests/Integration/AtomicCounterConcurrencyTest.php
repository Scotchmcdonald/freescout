<?php

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
    protected function setUp(): void
    {
        parent::setUp();
        
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
        ]]);
        
        // Run migrations on the file-based database
        $this->artisan('migrate:fresh', [
            '--database' => 'sqlite_testing',
            '--force' => true
        ]);
        
        // Switch DB facade to use the test database
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite_testing');
    }
    
    protected function tearDown(): void
    {
        // Clean up test database
        $dbPath = database_path('testing.sqlite');
        if (file_exists($dbPath)) {
            unlink($dbPath);
        }
        
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
                '--counter-id=1'
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
        
        // Start all processes simultaneously
        $this->info("Starting {$processCount} parallel processes...");
        foreach ($processes as $process) {
            $process->start();
        }
        
        // Wait for all processes to complete
        foreach ($processes as $i => $process) {
            $process->wait();
            
            if (!$process->isSuccessful()) {
                $output = $process->getOutput();
                $error = $process->getErrorOutput();
                $this->fail("Process {$i} failed:\nOutput: {$output}\nError: {$error}");
            }
        }
        
        // Verify final count
        $finalCount = DB::table('test_counters')->where('id', 1)->value('count');
        
        $this->assertEquals(
            $expectedFinalCount,
            $finalCount,
            "CRITICAL FAILURE: Concurrent increments lost updates! " .
            "Expected {$expectedFinalCount} but got {$finalCount}. " .
            "This indicates a race condition in AtomicCounterService."
        );
        
        $this->info("✅ PASSED: All {$expectedFinalCount} increments were atomic!");
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
        
        // Start 5 processes, each incrementing a different counter
        for ($counterId = 1; $counterId <= 5; $counterId++) {
            $process = new Process([
                'php',
                base_path('artisan'),
                'test:atomic-counter-increment',
                "--iterations={$iterationsPerProcess}",
                "--counter-id={$counterId}"
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
        foreach ($processes as $process) {
            $process->wait();
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
        
        for ($i = 0; $i < 10; $i++) {
            $process = new Process([
                'php',
                base_path('artisan'),
                'test:atomic-counter-decrement',
                '--iterations=50',
                '--counter-id=1'
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
        
        foreach ($processes as $process) {
            $process->wait();
        }
        
        $finalCount = DB::table('test_counters')->where('id', 1)->value('count');
        
        $this->assertEquals(
            500, // 1000 - (10 * 50)
            $finalCount,
            "Concurrent decrements lost updates"
        );
    }
    
    /**
     * Helper to output info during test
     */
    protected function info(string $message): void
    {
        fwrite(STDOUT, $message . PHP_EOL);
    }
}
