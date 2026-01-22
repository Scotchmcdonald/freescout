<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use App\Services\CircuitBreaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * CircuitBreaker Integration Tests
 * 
 * Tests the circuit breaker pattern implementation that protects
 * the system from cascading failures when external services fail.
 * 
 * Critical for:
 * - Helcim payment gateway
 * - Google Workspace API
 * - Action1 API
 */
#[Group('integration')]
#[Group('services')]
#[Group('circuit-breaker')]
class CircuitBreakerTest extends TestCase
{
    use RefreshDatabase;

    private CircuitBreaker $breaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->breaker = app(CircuitBreaker::class);
        
        // Ensure circuit_breaker_states table exists
        if (!DB::getSchemaBuilder()->hasTable('circuit_breaker_states')) {
            DB::statement('
                CREATE TABLE circuit_breaker_states (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    service VARCHAR(100) NOT NULL UNIQUE,
                    state VARCHAR(20) DEFAULT "closed",
                    failure_count INTEGER DEFAULT 0,
                    last_failure_at TIMESTAMP NULL,
                    opened_at TIMESTAMP NULL,
                    created_at TIMESTAMP,
                    updated_at TIMESTAMP
                )
            ');
        }
        
        // Reset any existing state
        DB::table('circuit_breaker_states')->truncate();
    }

    /**
     * Test successful call passes through closed circuit.
     */
    public function test_successful_call_passes_through(): void
    {
        $result = $this->breaker->call('test_service', function () {
            return 'success';
        });

        $this->assertEquals('success', $result);
    }

    /**
     * Test circuit starts in closed state.
     */
    public function test_circuit_starts_closed(): void
    {
        $this->assertFalse($this->breaker->isOpen('new_service'));
    }

    /**
     * Test single failure doesn't open circuit.
     */
    public function test_single_failure_keeps_circuit_closed(): void
    {
        try {
            $this->breaker->call('test_service', function () {
                throw new \Exception('Service failed');
            });
        } catch (\Exception $e) {
            // Expected
        }

        $this->assertFalse($this->breaker->isOpen('test_service'));
    }

    /**
     * Test circuit opens after threshold failures.
     */
    public function test_circuit_opens_after_threshold_failures(): void
    {
        $threshold = config('services.circuit_breaker.threshold', 5);

        // Cause threshold number of failures
        for ($i = 0; $i < $threshold; $i++) {
            try {
                $this->breaker->call('failing_service', function () {
                    throw new \Exception('Service unavailable');
                });
            } catch (\Exception $e) {
                // Expected
            }
        }

        $this->assertTrue($this->breaker->isOpen('failing_service'));
    }

    /**
     * Test open circuit throws immediately without calling callback.
     */
    public function test_open_circuit_throws_without_calling_callback(): void
    {
        // Force circuit open
        $threshold = config('services.circuit_breaker.threshold', 5);
        for ($i = 0; $i < $threshold; $i++) {
            try {
                $this->breaker->call('blocked_service', function () {
                    throw new \Exception('Fail');
                });
            } catch (\Exception $e) {
                // Expected
            }
        }

        // Now verify callback is not called
        $callbackCalled = false;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Circuit breaker is open');

        $this->breaker->call('blocked_service', function () use (&$callbackCalled) {
            $callbackCalled = true;
            return 'should not reach here';
        });

        $this->assertFalse($callbackCalled);
    }

    /**
     * Test successful call resets failure count.
     */
    public function test_success_resets_failure_count(): void
    {
        // Cause some failures (less than threshold)
        for ($i = 0; $i < 3; $i++) {
            try {
                $this->breaker->call('recoverable_service', function () {
                    throw new \Exception('Temporary failure');
                });
            } catch (\Exception $e) {
                // Expected
            }
        }

        // Then succeed
        $this->breaker->call('recoverable_service', function () {
            return 'recovered';
        });

        // Verify circuit is still closed and can handle more failures
        $this->assertFalse($this->breaker->isOpen('recoverable_service'));
    }

    /**
     * Test different services have independent circuits.
     */
    public function test_services_have_independent_circuits(): void
    {
        $threshold = config('services.circuit_breaker.threshold', 5);

        // Open circuit for service A
        for ($i = 0; $i < $threshold; $i++) {
            try {
                $this->breaker->call('service_a', function () {
                    throw new \Exception('A failed');
                });
            } catch (\Exception $e) {
                // Expected
            }
        }

        // Service B should still work
        $result = $this->breaker->call('service_b', function () {
            return 'B works';
        });

        $this->assertTrue($this->breaker->isOpen('service_a'));
        $this->assertFalse($this->breaker->isOpen('service_b'));
        $this->assertEquals('B works', $result);
    }

    /**
     * Test callback exceptions are propagated.
     */
    public function test_callback_exceptions_propagate(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Custom error message');

        $this->breaker->call('test_service', function () {
            throw new \InvalidArgumentException('Custom error message');
        });
    }

    /**
     * Test callback return value is passed through.
     */
    public function test_callback_return_value_passed_through(): void
    {
        $complexResult = ['data' => ['nested' => true], 'count' => 42];

        $result = $this->breaker->call('test_service', function () use ($complexResult) {
            return $complexResult;
        });

        $this->assertEquals($complexResult, $result);
    }

    /**
     * Test circuit breaker with real-world scenario.
     */
    public function test_realistic_payment_scenario(): void
    {
        $threshold = config('services.circuit_breaker.threshold', 5);
        $successfulPayments = 0;
        $blockedPayments = 0;

        // Simulate payment processing with intermittent failures
        for ($i = 0; $i < 20; $i++) {
            try {
                $this->breaker->call('helcim', function () use ($i) {
                    // First 5 calls fail (simulating service outage)
                    if ($i < 5) {
                        throw new \Exception('Gateway timeout');
                    }
                    return ['status' => 'success', 'transaction_id' => 'TXN-' . $i];
                });
                $successfulPayments++;
            } catch (\RuntimeException $e) {
                // Circuit open
                $blockedPayments++;
            } catch (\Exception $e) {
                // Regular failure
            }
        }

        // After 5 failures, circuit should open and block remaining calls
        $this->assertGreaterThan(0, $blockedPayments);
        $this->assertTrue($this->breaker->isOpen('helcim'));
    }
}
