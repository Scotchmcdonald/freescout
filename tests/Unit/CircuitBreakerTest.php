<?php

namespace Tests\Unit;

use App\Services\CircuitBreaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CircuitBreakerTest extends TestCase
{
    use RefreshDatabase;
    
    protected CircuitBreaker $breaker;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->breaker = new CircuitBreaker();
    }
    
    public function test_closed_circuit_allows_requests(): void
    {
        $executed = false;
        
        $result = $this->breaker->call('test_service', function() use (&$executed) {
            $executed = true;
            return 'success';
        });
        
        $this->assertTrue($executed);
        $this->assertEquals('success', $result);
    }
    
    public function test_circuit_opens_after_threshold(): void
    {
        $service = 'failing_service';
        $threshold = 5;
        
        // Cause threshold failures
        for ($i = 0; $i < $threshold; $i++) {
            try {
                $this->breaker->call($service, function() {
                    throw new \Exception('Service failure');
                });
            } catch (\Exception $e) {
                // Expected
            }
        }
        
        // Circuit should be open now
        $this->assertTrue($this->breaker->isOpen($service));
        
        // Next call should fail immediately
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Circuit breaker is open');
        
        $this->breaker->call($service, fn() => 'should not execute');
    }
    
    public function test_circuit_resets_on_success(): void
    {
        $service = 'intermittent_service';
        
        // Cause 3 failures (below threshold of 5)
        for ($i = 0; $i < 3; $i++) {
            try {
                $this->breaker->call($service, function() {
                    throw new \Exception('Failure');
                });
            } catch (\Exception $e) {
                // Expected
            }
        }
        
        // Success should reset counter
        $this->breaker->call($service, fn() => 'success');
        
        // Verify failure count was reset
        $state = DB::table('circuit_breaker_states')
            ->where('service', $service)
            ->first();
        
        $this->assertEquals(0, $state->failure_count);
    }
    
    public function test_circuit_transitions_to_half_open_after_timeout(): void
    {
        $service = 'recovery_service';
        
        // Open the circuit
        for ($i = 0; $i < 5; $i++) {
            try {
                $this->breaker->call($service, function() {
                    throw new \Exception('Failure');
                });
            } catch (\Exception $e) {
                // Expected
            }
        }
        
        $this->assertTrue($this->breaker->isOpen($service));
        
        // Simulate timeout by backdating the opened_at timestamp
        DB::table('circuit_breaker_states')
            ->where('service', $service)
            ->update(['opened_at' => now()->subSeconds(61)]);
        
        // Should transition to half-open and allow a test request
        $result = $this->breaker->call($service, fn() => 'recovered');
        
        $this->assertEquals('recovered', $result);
        $this->assertFalse($this->breaker->isOpen($service));
    }
    
    public function test_half_open_circuit_reopens_on_failure(): void
    {
        $service = 'flaky_service';
        
        // Open circuit
        for ($i = 0; $i < 5; $i++) {
            try {
                $this->breaker->call($service, fn() => throw new \Exception('Fail'));
            } catch (\Exception $e) {
                // Expected
            }
        }
        
        // Force to half-open
        DB::table('circuit_breaker_states')
            ->where('service', $service)
            ->update([
                'state' => 'half_open',
                'opened_at' => now()->subSeconds(61)
            ]);
        
        // Failure in half-open should reopen
        try {
            $this->breaker->call($service, fn() => throw new \Exception('Still failing'));
        } catch (\Exception $e) {
            // Expected
        }
        
        // Should be open again (failure threshold reached)
        $state = DB::table('circuit_breaker_states')
            ->where('service', $service)
            ->first();
        
        $this->assertEquals('open', $state->state);
    }
    
    public function test_manual_reset(): void
    {
        $service = 'manual_reset_service';
        
        // Open circuit
        for ($i = 0; $i < 5; $i++) {
            try {
                $this->breaker->call($service, fn() => throw new \Exception('Fail'));
            } catch (\Exception $e) {
                // Expected
            }
        }
        
        $this->assertTrue($this->breaker->isOpen($service));
        
        // Manual reset
        $this->breaker->reset($service);
        
        $this->assertFalse($this->breaker->isOpen($service));
        
        // Should allow requests
        $result = $this->breaker->call($service, fn() => 'working');
        $this->assertEquals('working', $result);
    }
    
    public function test_different_services_are_independent(): void
    {
        // Open circuit for service1
        for ($i = 0; $i < 5; $i++) {
            try {
                $this->breaker->call('service1', fn() => throw new \Exception('Fail'));
            } catch (\Exception $e) {
                // Expected
            }
        }
        
        // service2 should still work
        $result = $this->breaker->call('service2', fn() => 'working');
        
        $this->assertTrue($this->breaker->isOpen('service1'));
        $this->assertFalse($this->breaker->isOpen('service2'));
        $this->assertEquals('working', $result);
    }
}
