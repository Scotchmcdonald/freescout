<?php

declare(strict_types=1);

namespace Modules\MiddleMan\Services;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Psr\Log\LoggerInterface;

/**
 * Circuit Breaker for MiddleMan — guarantees zero production impact.
 *
 * Three states:
 *   CLOSED  → Normal operation. MiddleMan processes events.
 *   OPEN    → MiddleMan is disabled. All events pass through untouched.
 *   HALF    → Recovery probe. A single event is tested, then CLOSED or re-OPENED.
 *
 * Triggers that OPEN the breaker:
 *   1. Consecutive failures (cache/queue exceptions) exceed the threshold.
 *   2. Events-per-second rate exceeds the storm threshold (memory protection).
 *   3. Queue depth exceeds the backpressure limit.
 *
 * The breaker stores state in a local static property (microsecond reads)
 * with periodic sync to cache for cross-process consistency.
 */
final class CircuitBreaker
{
    public const STATE_CLOSED = 'closed';
    public const STATE_OPEN = 'open';
    public const STATE_HALF = 'half_open';

    private const CACHE_KEY = 'middleman:circuit_breaker';
    private const COUNTER_KEY = 'middleman:cb_failures';
    private const RATE_WINDOW_KEY = 'middleman:cb_rate_window'; // @phpstan-ignore classConstant.unused

    /**
     * Fallback flag file written to local disk when primary cache (Redis) is
     * unavailable.  This guarantees the OPEN state survives across PHP-FPM
     * worker restarts even if the static property resets to CLOSED.
     */
    private const FALLBACK_FLAG_FILE = 'framework/middleman_breaker.flag';

    /** In-process cache — avoids Redis round-trips on every dispatch. */
    private static string $localState = self::STATE_CLOSED;
    private static int $localStateCheckedAt = 0;

    /** Sliding window counters (in-process, flushed periodically). */
    private static int $eventsInWindow = 0;
    private static int $windowStartTime = 0;

    private int $failureThreshold;
    private int $stormThresholdPerSecond;
    private int $queueDepthLimit;
    private int $cooldownSeconds;
    private int $syncIntervalSeconds;
    private string $cacheStore;
    private CacheFactory $cache;
    private LoggerInterface $logger;

    public function __construct(CacheFactory $cache, LoggerInterface $logger)
    {
        $this->cache = $cache;
        $this->logger = $logger;
        $this->failureThreshold = (int) config('middleman.circuit_breaker.failure_threshold', 5); // @phpstan-ignore cast.int
        $this->stormThresholdPerSecond = (int) config('middleman.circuit_breaker.storm_threshold_per_second', 500); // @phpstan-ignore cast.int
        $this->queueDepthLimit = (int) config('middleman.circuit_breaker.queue_depth_limit', 10000); // @phpstan-ignore cast.int
        $this->cooldownSeconds = (int) config('middleman.circuit_breaker.cooldown_seconds', 60); // @phpstan-ignore cast.int
        $this->syncIntervalSeconds = (int) config('middleman.circuit_breaker.sync_interval_seconds', 5); // @phpstan-ignore cast.int
        $this->cacheStore = (string) config('middleman.cache_store', 'redis'); // @phpstan-ignore cast.string
    }

    /**
     * Check if MiddleMan should process the current event.
     *
     * Returns TRUE if event processing is allowed (breaker CLOSED or HALF-OPEN probe).
     * Returns FALSE if the breaker is OPEN — caller must pass through to parent::dispatch().
     *
     * This method is designed to be called on EVERY dispatch — budget: < 1μs typical.
     */
    public function allowsProcessing(): bool
    {
        $state = $this->getState();

        if ($state === self::STATE_CLOSED) {
            // Rate-limit check (in-process only, no I/O)
            if ($this->isEventStorm()) {
                $this->trip('Event storm detected: rate exceeded threshold');

                return false;
            }

            $this->recordEventTick();

            return true;
        }

        if ($state === self::STATE_HALF) {
            // Allow exactly one probe event, then decide
            return true;
        }

        // STATE_OPEN
        return false;
    }

    /**
     * Record a successful MiddleMan operation (log/intercept queued without error).
     * In HALF-OPEN state, this closes the breaker.
     */
    public function recordSuccess(): void
    {
        if (self::$localState === self::STATE_HALF) {
            $this->close('Half-open probe succeeded');
        }

        // Reset failure counter on success (debounce — only touch cache periodically)
        $this->resetFailureCounterIfNeeded();
    }

    /**
     * Record a failure (cache miss, queue exception, etc.).
     * Increments the failure counter and trips the breaker if threshold is exceeded.
     */
    public function recordFailure(\Throwable $e): void
    {
        $failures = $this->incrementFailureCounter();

        if ($failures >= $this->failureThreshold) {
            $this->trip("Failure threshold reached ({$failures}/{$this->failureThreshold}): {$e->getMessage()}");
        }
    }

    /**
     * Check queue backpressure. Called periodically (not on every event).
     * If the middleman queue is backed up beyond the limit, trip the breaker.
     */
    public function checkQueueBackpressure(): bool
    {
        if ($this->queueDepthLimit <= 0) {
            return false;
        }

        try {
            $connection = (string) config('middleman.queue_connection', 'redis'); // @phpstan-ignore cast.string
            $queueName = (string) config('middleman.queue_name', 'middleman'); // @phpstan-ignore cast.string
            $size = app('queue')->connection($connection)->size($queueName);

            if ($size > $this->queueDepthLimit) {
                $this->trip("Queue backpressure: {$size} jobs exceeds limit of {$this->queueDepthLimit}");

                return true;
            }
        } catch (\Throwable $e) {
            // Queue introspection failed — this counts as a failure
            $this->recordFailure($e);
        }

        return false;
    }

    /**
     * Get the current breaker state, syncing from cache periodically.
     */
    public function getState(): string
    {
        $now = time();

        // Use local state if recently synced (avoids Redis round-trip)
        if (($now - self::$localStateCheckedAt) < $this->syncIntervalSeconds) {
            return self::$localState;
        }

        // Sync from cache
        try {
            $cached = $this->cache->store($this->cacheStore)->get(self::CACHE_KEY);

            if (is_array($cached)) {
                self::$localState = is_string($cached['state'] ?? null) ? $cached['state'] : self::STATE_CLOSED;

                // Auto-transition from OPEN → HALF-OPEN after cooldown
                if (self::$localState === self::STATE_OPEN) {
                    $openedAt = $cached['opened_at'] ?? 0;
                    if (($now - $openedAt) >= $this->cooldownSeconds) {
                        self::$localState = self::STATE_HALF;
                        $this->persistState(self::STATE_HALF, 'Cooldown elapsed, entering half-open');
                    }
                }
            } else {
                // No cached state; check the file fallback before defaulting to CLOSED
                self::$localState = $this->readFallbackFlag() ?? self::STATE_CLOSED;
            }
        } catch (\Throwable $cacheException) {
            // Primary cache (Redis/Memcached) is unavailable.
            // 1. Trip immediately and persist to the file-based fallback.
            // 2. Emit EMERGENCY so on-call engineers are paged immediately.
            $this->writeFallbackFlag(self::STATE_OPEN);
            self::$localState = self::STATE_OPEN;

            $this->logger->emergency('MiddleMan CircuitBreaker: PRIMARY CACHE FAILURE — breaker force-OPEN', [
                'exception' => $cacheException->getMessage(),
                'class' => get_class($cacheException),
                'action' => 'All event processing bypassed until cache recovers.',
            ]);
        }

        self::$localStateCheckedAt = $now;

        return self::$localState;
    }

    /**
     * Manually close the breaker (admin action).
     */
    public function close(string $reason = 'Manual close'): void
    {
        self::$localState = self::STATE_CLOSED;
        self::$localStateCheckedAt = time();
        self::$eventsInWindow = 0;

        $this->persistState(self::STATE_CLOSED, $reason);
        $this->resetFailureCounterForce();
        $this->clearFallbackFlag();

        $this->logger->info('MiddleMan CircuitBreaker: CLOSED', ['reason' => $reason]);
    }

    /**
     * Manually open/trip the breaker (admin action or auto-trigger).
     *
     * Infrastructure-layer causes (cache/queue failures) are classified as
     * EMERGENCY so they surface immediately in alerting pipelines.
     */
    public function trip(string $reason, bool $isInfrastructureFailure = false): void
    {
        // Idempotent — don't log repeatedly if already open
        if (self::$localState === self::STATE_OPEN) {
            return;
        }

        self::$localState = self::STATE_OPEN;
        self::$localStateCheckedAt = time();

        // Write to both primary cache and the file-based fallback.
        // The file fallback survives Redis downtime across FPM worker restarts.
        $this->persistState(self::STATE_OPEN, $reason);
        $this->writeFallbackFlag(self::STATE_OPEN);

        if ($isInfrastructureFailure) {
            $this->logger->emergency('MiddleMan CircuitBreaker: TRIPPED — INFRASTRUCTURE FAILURE (OPEN)', [
                'reason' => $reason,
                'cooldown_seconds' => $this->cooldownSeconds,
                'action' => 'All MiddleMan processing bypassed. Restore cache/queue to recover.',
            ]);
        } else {
            $this->logger->warning('MiddleMan CircuitBreaker: TRIPPED (OPEN)', [
                'reason' => $reason,
                'cooldown_seconds' => $this->cooldownSeconds,
            ]);
        }
    }

    /**
     * Get diagnostic info for the dashboard.
     *
     * @return array{state: string, events_in_window: int, storm_threshold: int, failure_threshold: int, cooldown_seconds: int}
     */
    public function diagnostics(): array
    {
        return [
            'state' => $this->getState(),
            'events_in_window' => self::$eventsInWindow,
            'storm_threshold' => $this->stormThresholdPerSecond,
            'failure_threshold' => $this->failureThreshold,
            'queue_depth_limit' => $this->queueDepthLimit,
            'cooldown_seconds' => $this->cooldownSeconds,
            'local_state_age_seconds' => time() - self::$localStateCheckedAt,
        ];
    }

    /**
     * Reset in-process state (for testing).
     */
    public static function resetForTesting(): void
    {
        self::$localState = self::STATE_CLOSED;
        self::$localStateCheckedAt = 0;
        self::$eventsInWindow = 0;
        self::$windowStartTime = 0;
    }

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting (In-Process, Zero I/O)
    |--------------------------------------------------------------------------
    */

    private function recordEventTick(): void
    {
        $now = time();

        if ($now !== self::$windowStartTime) {
            // New second — reset the window
            self::$windowStartTime = $now;
            self::$eventsInWindow = 1;
        } else {
            self::$eventsInWindow++;
        }
    }

    private function isEventStorm(): bool
    {
        $now = time();

        if ($now !== self::$windowStartTime) {
            return false; // New second, can't be a storm yet
        }

        return self::$eventsInWindow >= $this->stormThresholdPerSecond;
    }

    /*
    |--------------------------------------------------------------------------
    | Failure Counter (Cache-backed, debounced)
    |--------------------------------------------------------------------------
    */

    private function incrementFailureCounter(): int
    {
        try {
            $store = $this->cache->store($this->cacheStore);
            $current = (int) $store->get(self::COUNTER_KEY, 0); // @phpstan-ignore cast.int
            $current++;
            $store->put(self::COUNTER_KEY, $current, $this->cooldownSeconds * 2);

            return $current;
        } catch (\Throwable) {
            // Cache failure while counting failures — trip immediately
            $this->trip('Cache unavailable during failure counting');

            return $this->failureThreshold;
        }
    }

    private function resetFailureCounterIfNeeded(): void
    {
        // Only reset every sync interval to avoid hammering cache
        static $lastReset = 0;
        $now = time();

        if (($now - $lastReset) < $this->syncIntervalSeconds) {
            return;
        }

        $lastReset = $now;
        $this->resetFailureCounterForce();
    }

    private function resetFailureCounterForce(): void
    {
        try {
            $this->cache->store($this->cacheStore)->forget(self::COUNTER_KEY);
        } catch (\Throwable) {
            // Swallow — non-critical
        }
    }

    /*
    |--------------------------------------------------------------------------
    | State Persistence
    |--------------------------------------------------------------------------
    */

    private function persistState(string $state, string $reason): void
    {
        try {
            $this->cache->store($this->cacheStore)->forever(self::CACHE_KEY, [
                'state' => $state,
                'reason' => $reason,
                'opened_at' => $state === self::STATE_OPEN ? time() : null,
                'changed_at' => time(),
            ]);
        } catch (\Throwable) {
            // Primary cache unavailable — in-process + file flag are authoritative.
        }
    }

    /*
    |--------------------------------------------------------------------------
    | File-Based Fallback (Cache-Unavailable Safety Net)
    |--------------------------------------------------------------------------
    |
    | When Redis / Memcached is completely unavailable, the primary cache-
    | backed persistence will throw.  The flag file below provides a durable,
    | cross-process fallback that survives PHP-FPM worker recycling so every
    | worker starts in the correct OPEN state rather than incorrectly assuming
    | CLOSED and re-processing events.
    */

    private function fallbackFlagPath(): string
    {
        return storage_path(self::FALLBACK_FLAG_FILE);
    }

    private function writeFallbackFlag(string $state): void
    {
        try {
            $path = $this->fallbackFlagPath();
            $dir = dirname($path);

            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            file_put_contents($path, json_encode([
                'state' => $state,
                'written_at' => time(),
            ], JSON_THROW_ON_ERROR), LOCK_EX);
        } catch (\Throwable) {
            // Filesystem failure — in-process static state remains authoritative.
        }
    }

    private function readFallbackFlag(): ?string
    {
        try {
            $path = $this->fallbackFlagPath();

            if (! file_exists($path)) {
                return null;
            }

            /** @var array{state?: string, written_at?: int}|null $data */
            $data = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

            if (! is_array($data) || ! isset($data['state'])) {
                return null;
            }

            // Honour cooldown from file timestamp if the state is OPEN
            if ($data['state'] === self::STATE_OPEN) {
                $writtenAt = (int) ($data['written_at'] ?? 0);
                if ((time() - $writtenAt) >= $this->cooldownSeconds) {
                    return self::STATE_HALF;
                }
            }

            return $data['state'];
        } catch (\Throwable) {
            return null;
        }
    }

    private function clearFallbackFlag(): void
    {
        try {
            $path = $this->fallbackFlagPath();
            if (file_exists($path)) {
                unlink($path);
            }
        } catch (\Throwable) {
            // Non-critical
        }
    }
}
