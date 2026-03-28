<?php

declare(strict_types=1);

namespace Modules\MiddleMan\Services;

use Illuminate\Events\Dispatcher;
use Illuminate\Support\Str;
use Modules\MiddleMan\Jobs\WriteInterceptEntryJob;
use Modules\MiddleMan\Jobs\WriteLogEntryJob;

/**
 * Custom event dispatcher that adds Logging, Interception, and Listener Muting
 * capabilities on top of Laravel's default dispatcher.
 *
 * When middleman is disabled, this class is never bound — the default
 * Laravel dispatcher is used with zero overhead.
 *
 * When active, dispatch() checks the in-memory RuleEngine:
 *   - No match:    parent::dispatch() immediately (microsecond overhead)
 *   - Log match:   async job → parent::dispatch() (event continues)
 *   - Intercept:   async job → return null (event halted)
 *
 * Also supports:
 *   - Correlation/Causation tracking via MiddleManContext
 *   - Surgical listener muting via RuleEngine mute list
 */
class MiddleManDispatcher extends Dispatcher
{
    private RuleEngine $ruleEngine;
    private EventSerializer $serializer;
    private ?MiddleManContext $context = null;
    private ?CircuitBreaker $circuitBreaker = null;
    private bool $bypassing = false;

    public function setMiddleManServices(RuleEngine $ruleEngine, EventSerializer $serializer): void
    {
        $this->ruleEngine = $ruleEngine;
        $this->serializer = $serializer;
    }

    public function setContext(MiddleManContext $context): void
    {
        $this->context = $context;
    }

    public function setCircuitBreaker(CircuitBreaker $circuitBreaker): void
    {
        $this->circuitBreaker = $circuitBreaker;
    }

    /**
     * @param  string|object  $event
     * @param  mixed  $payload
     * @param  bool  $halt
     * @return array<int, mixed>|null
     */
    public function dispatch($event, $payload = [], $halt = false)
    {
        // Bypass mode: used when firing intercepted events to prevent infinite loops
        if ($this->bypassing) {
            return parent::dispatch($event, $payload, $halt);
        }

        // Circuit Breaker: if tripped, pass through immediately (zero overhead)
        if ($this->circuitBreaker !== null && ! $this->circuitBreaker->allowsProcessing()) {
            return parent::dispatch($event, $payload, $halt);
        }

        // Resolve the event class name for object events
        $eventClass = is_object($event) ? get_class($event) : $event;

        // Skip framework/internal events (strings like "eloquent.*", "creating:*")
        if (is_string($event) && ! class_exists($event)) {
            return parent::dispatch($event, $payload, $halt);
        }

        // Generate a unique ID for this dispatch and push causation context
        $eventId = Str::uuid()->toString();
        $this->context?->pushCausation($eventId);

        try {
            // Check interception FIRST — if intercepted, event is halted entirely
            if (isset($this->ruleEngine) && $this->ruleEngine->shouldIntercept($eventClass)) {
                $this->dispatchInterception($event, $eventClass, $eventId);
                $this->circuitBreaker?->recordSuccess();

                return null;
            }

            // Check logging — if logged, event continues normally after queuing the log
            if (isset($this->ruleEngine) && $this->ruleEngine->shouldLog($eventClass)) {
                $this->dispatchLog($event, $eventClass, $eventId);
                $this->circuitBreaker?->recordSuccess();
            }

            return parent::dispatch($event, $payload, $halt);
        } finally {
            $this->context?->popCausation();
        }
    }

    /**
     * Override getListeners to support surgical listener muting.
     * Filters out any listeners that match the muted listener patterns.
     *
     * @param  string  $eventName
     * @return array<int, mixed>
     */
    public function getListeners($eventName)
    {
        $listeners = parent::getListeners($eventName);

        if (! isset($this->ruleEngine)) {
            return $listeners;
        }

        $mutedPatterns = $this->ruleEngine->getMutedListeners();
        if ($mutedPatterns === []) {
            return $listeners;
        }

        $filtered = [];
        foreach ($listeners as $listener) {
            $listenerName = $this->resolveListenerName($listener);
            if ($listenerName !== null && $this->ruleEngine->isListenerMuted($listenerName)) {
                // Log the muted execution to a background queue
                $this->logMutedListener($eventName, $listenerName);
                continue;
            }
            $filtered[] = $listener;
        }

        return $filtered;
    }

    /**
     * Dispatch an event in bypass mode — skips all MiddleMan rules.
     * Used by controllers to fire intercepted events without re-interception.
     */
    public function dispatchBypassing(object $event): mixed
    {
        $this->bypassing = true;

        try {
            return parent::dispatch($event);
        } finally {
            $this->bypassing = false;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Internal Dispatchers
    |--------------------------------------------------------------------------
    */

    private function dispatchLog(string|object $event, string $eventClass, string $eventId): void
    {
        if (! is_object($event)) {
            return;
        }

        try {
            $serialized = $this->serializer->serialize($event);

            // Merge tracing context into metadata
            $metadata = $serialized['metadata'];
            $metadata['event_id'] = $eventId;
            if ($this->context !== null) {
                $metadata = array_merge($metadata, $this->context->envelope());
            }

            WriteLogEntryJob::dispatch(
                $eventClass,
                class_basename($eventClass),
                $serialized['payload'],
                $metadata,
                now()->toIso8601String(),
            )->onConnection((string) config('middleman.queue_connection', 'redis')) // @phpstan-ignore cast.string
                ->onQueue((string) config('middleman.queue_name', 'middleman')); // @phpstan-ignore cast.string
        } catch (\Throwable $e) {
            // Never let MiddleMan break the application.
            // Distinguish infrastructure failures (Redis/cache) from transient errors
            // so the circuit breaker emits the correct severity.
            if ($this->isInfrastructureException($e)) {
                $this->circuitBreaker?->trip(
                    'Cache/queue infrastructure failure during log dispatch: '.$e->getMessage(),
                    isInfrastructureFailure: true,
                );
            } else {
                logger()->warning('MiddleMan: Failed to queue log entry', [
                    'event' => $eventClass,
                    'error' => $e->getMessage(),
                ]);
                $this->circuitBreaker?->recordFailure($e);
            }
        }
    }

    private function dispatchInterception(string|object $event, string $eventClass, string $eventId): void
    {
        if (! is_object($event)) {
            return;
        }

        try {
            $serialized = $this->serializer->serialize($event);

            // Merge tracing context into metadata
            $metadata = $serialized['metadata'];
            $metadata['event_id'] = $eventId;
            if ($this->context !== null) {
                $metadata = array_merge($metadata, $this->context->envelope());
            }

            WriteInterceptEntryJob::dispatch(
                $eventClass,
                class_basename($eventClass),
                $serialized['payload'],
                $metadata,
                now()->toIso8601String(),
            )->onConnection((string) config('middleman.queue_connection', 'redis')) // @phpstan-ignore cast.string
                ->onQueue((string) config('middleman.queue_name', 'middleman')); // @phpstan-ignore cast.string
        } catch (\Throwable $e) {
            // If interception fails, let the event through normally.
            if ($this->isInfrastructureException($e)) {
                $this->circuitBreaker?->trip(
                    'Cache/queue infrastructure failure during intercept dispatch: '.$e->getMessage(),
                    isInfrastructureFailure: true,
                );
            } else {
                logger()->error('MiddleMan: Failed to intercept event, allowing passthrough', [
                    'event' => $eventClass,
                    'error' => $e->getMessage(),
                ]);
                $this->circuitBreaker?->recordFailure($e);
            }
        }
    }

    /**
     * Returns true for exceptions that indicate the cache or queue layer
     * is unavailable rather than a transient application-level error.
     */
    private function isInfrastructureException(\Throwable $e): bool
    {
        // Redis connection failures (predis / phpredis)
        if ($e instanceof \RedisException) {
            return true;
        }

        $class = get_class($e);
        if (str_contains($class, 'Predis\\') || str_contains($class, 'Redis')) {
            return true;
        }

        // Connection-reset/refused patterns from various Redis/Memcached drivers
        $message = strtolower($e->getMessage());
        foreach (['connection refused', 'connection reset', 'no connection to redis', 'redis server gone away', 'memcache'] as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | Listener Muting Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Resolve a listener to its class name string (if possible).
     */
    private function resolveListenerName(mixed $listener): ?string
    {
        if (is_string($listener)) {
            return $listener;
        }

        if (is_array($listener) && count($listener) === 2) {
            $class = is_object($listener[0]) ? get_class($listener[0]) : $listener[0];

            return is_string($class) ? $class.'@'.$listener[1] : null;
        }

        // For closures that wrap class@method strings (Laravel's listener format),
        // we cannot reliably extract the name — return null to allow them through.
        return null;
    }

    /**
     * Log that a listener was muted for an event (async, fire-and-forget).
     */
    private function logMutedListener(string $eventName, string $listenerName): void
    {
        try {
            logger()->info('MiddleMan: Muted listener', [
                'event' => $eventName,
                'listener' => $listenerName,
            ]);
        } catch (\Throwable) {
            // Never break the app
        }
    }
}
