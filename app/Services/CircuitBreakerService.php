<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * CircuitBreaker - Service health management
 *
 * Implements circuit breaker pattern to prevent cascading failures
 * from external services. Tracks failure rates and automatically
 * opens circuit after threshold is exceeded.
 *
 * States:
 * - CLOSED: Normal operation, requests pass through
 * - OPEN: Circuit is open, requests fail immediately
 * - HALF_OPEN: Testing if service has recovered
 *
 * Usage:
 * $breaker = app(CircuitBreaker::class);
 *
 * $result = $breaker->call('google_workspace', function() {
 *     return $googleService->makeApiCall();
 * });
 */
class CircuitBreakerService
{
    const STATE_CLOSED = 'closed';
    const STATE_OPEN = 'open';
    const STATE_HALF_OPEN = 'half_open';

    protected int $failureThreshold;
    protected int $recoveryTimeout;

    public function __construct()
    {
        $thresh = config('services.circuit_breaker.threshold', 5);
        $this->failureThreshold = is_numeric($thresh) ? intval($thresh) : 5;

        $timeout = config('services.circuit_breaker.timeout', 60);
        $this->recoveryTimeout = is_numeric($timeout) ? intval($timeout) : 60;
    }

    /**
     * Execute callback with circuit breaker protection
     *
     * @param  string  $service  Service identifier
     * @param  callable  $callback  Function to execute
     * @return mixed Result from callback
     *
     * @throws \RuntimeException If circuit is open
     */
    public function call(string $service, callable $callback): mixed
    {
        $state = $this->getState($service);

        // If circuit is open, check if recovery timeout has passed
        if ($state['state'] === self::STATE_OPEN) {
            if ($this->shouldAttemptRecovery($state)) {
                $this->transitionTo($service, self::STATE_HALF_OPEN);
            } else {
                throw new \RuntimeException("Circuit breaker is open for service: {$service}");
            }
        }

        try {
            // Execute callback
            $result = $callback();

            // Success - reset failure count or close circuit
            if ($state['state'] === self::STATE_HALF_OPEN) {
                $this->transitionTo($service, self::STATE_CLOSED);
            } elseif ($state['failure_count'] > 0) {
                $this->resetFailures($service);
            }

            return $result;
        } catch (\Throwable $e) {
            // Failure - increment counter and check threshold
            $this->recordFailure($service);

            $newState = $this->getState($service);

            if ($newState['failure_count'] >= $this->failureThreshold) {
                $this->transitionTo($service, self::STATE_OPEN);
                Log::warning("Circuit breaker opened for service: {$service}");
            }

            throw $e;
        }
    }

    /**
     * Check if circuit is open for a service
     *
     * @param  string  $service  Service identifier
     * @return bool True if circuit is open
     */
    public function isOpen(string $service): bool
    {
        $state = $this->getState($service);

        return $state['state'] === self::STATE_OPEN;
    }

    /**
     * Manually reset circuit breaker for a service
     *
     * @param  string  $service  Service identifier
     */
    public function reset(string $service): void
    {
        $this->transitionTo($service, self::STATE_CLOSED);
        $this->resetFailures($service);

        Log::info("Circuit breaker manually reset for service: {$service}");
    }

    /**
     * Get states for all services (for dashboard)
     *
     * @return array<int, \stdClass> Array of service states
     */
    public function getAllStates(): array
    {
        /** @var array<int, \stdClass> */
        return DB::table('circuit_breaker_states')->get()->toArray();
    }

    /**
     * Get state transitions for a service (last 24 hours)
     *
     * @param  string  $service  Service identifier
     * @return array<int, mixed> Array of transitions
     */
    public function getRecentTransitions(string $service): array
    {
        // This would require a separate transitions table to be fully implemented
        // For now, return empty array as placeholder
        return [];
    }

    /**
     * Get current state for a service
     *
     * @return array<string, mixed>
     */
    protected function getState(string $service): array
    {
        $state = DB::table('circuit_breaker_states')
            ->where('service', $service)
            ->first();

        if (! $state) {
            // Initialize default state
            DB::table('circuit_breaker_states')->insert([
                'service' => $service,
                'state' => self::STATE_CLOSED,
                'failure_count' => 0,
                'last_failure_at' => null,
                'opened_at' => null,
            ]);

            return [
                'service' => $service,
                'state' => self::STATE_CLOSED,
                'failure_count' => 0,
                'last_failure_at' => null,
                'opened_at' => null,
            ];
        }

        return (array) $state;
    }

    /**
     * Transition circuit to new state
     */
    protected function transitionTo(string $service, string $newState): void
    {
        $updates = ['state' => $newState];

        if ($newState === self::STATE_OPEN) {
            $updates['opened_at'] = now();
        } elseif ($newState === self::STATE_CLOSED) {
            $updates['opened_at'] = null;
            $updates['failure_count'] = 0;
        }

        DB::table('circuit_breaker_states')
            ->where('service', $service)
            ->update($updates);
    }

    /**
     * Record a failure for a service
     */
    protected function recordFailure(string $service): void
    {
        DB::table('circuit_breaker_states')
            ->where('service', $service)
            ->increment('failure_count', 1, [
                'last_failure_at' => now(),
            ]);
    }

    /**
     * Reset failure count for a service
     */
    protected function resetFailures(string $service): void
    {
        DB::table('circuit_breaker_states')
            ->where('service', $service)
            ->update([
                'failure_count' => 0,
                'last_failure_at' => null,
            ]);
    }

    /**
     * Check if enough time has passed to attempt recovery
     *
     * @param  array<string, mixed>  $state
     */
    protected function shouldAttemptRecovery(array $state): bool
    {
        if (! $state['opened_at']) {
            return true;
        }

        $val = $state['opened_at'];
        $openedAt = strtotime(is_string($val) ? $val : '');
        $elapsed = time() - $openedAt;

        return $elapsed >= $this->recoveryTimeout;
    }
}
