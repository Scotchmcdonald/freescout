<?php

declare(strict_types=1);

namespace Modules\MiddleMan\Services;

use Illuminate\Events\Dispatcher;
use Modules\MiddleMan\Jobs\WriteInterceptEntryJob;
use Modules\MiddleMan\Jobs\WriteLogEntryJob;

/**
 * Custom event dispatcher that adds Logging and Interception capabilities
 * on top of Laravel's default dispatcher.
 *
 * When middleman is disabled, this class is never bound — the default
 * Laravel dispatcher is used with zero overhead.
 *
 * When active, dispatch() checks the in-memory RuleEngine:
 *   - No match:    parent::dispatch() immediately (microsecond overhead)
 *   - Log match:   async job → parent::dispatch() (event continues)
 *   - Intercept:   async job → return null (event halted)
 */
class MiddleManDispatcher extends Dispatcher
{
    private RuleEngine $ruleEngine;
    private EventSerializer $serializer;
    private bool $bypassing = false;

    public function setMiddleManServices(RuleEngine $ruleEngine, EventSerializer $serializer): void
    {
        $this->ruleEngine = $ruleEngine;
        $this->serializer = $serializer;
    }

    /**
     * @param  string|object  $event
     * @param  mixed  $payload
     * @param  bool  $halt
     * @return array|null
     */
    public function dispatch($event, $payload = [], $halt = false)
    {
        // Bypass mode: used when firing intercepted events to prevent infinite loops
        if ($this->bypassing) {
            return parent::dispatch($event, $payload, $halt);
        }

        // Resolve the event class name for object events
        $eventClass = is_object($event) ? get_class($event) : $event;

        // Skip framework/internal events (strings like "eloquent.*", "creating:*")
        if (is_string($event) && ! class_exists($event)) {
            return parent::dispatch($event, $payload, $halt);
        }

        // Check interception FIRST — if intercepted, event is halted entirely
        if (isset($this->ruleEngine) && $this->ruleEngine->shouldIntercept($eventClass)) {
            $this->dispatchInterception($event, $eventClass);
            return null;
        }

        // Check logging — if logged, event continues normally after queuing the log
        if (isset($this->ruleEngine) && $this->ruleEngine->shouldLog($eventClass)) {
            $this->dispatchLog($event, $eventClass);
        }

        return parent::dispatch($event, $payload, $halt);
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

    private function dispatchLog(string|object $event, string $eventClass): void
    {
        if (! is_object($event)) {
            return;
        }

        try {
            $serialized = $this->serializer->serialize($event);

            WriteLogEntryJob::dispatch(
                $eventClass,
                class_basename($eventClass),
                $serialized['payload'],
                $serialized['metadata'],
                now()->toIso8601String(),
            )->onConnection(config('middleman.queue_connection', 'redis'))
             ->onQueue(config('middleman.queue_name', 'middleman'));
        } catch (\Throwable $e) {
            // Never let MiddleMan break the application — log failure silently
            logger()->warning('MiddleMan: Failed to queue log entry', [
                'event' => $eventClass,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function dispatchInterception(string|object $event, string $eventClass): void
    {
        if (! is_object($event)) {
            return;
        }

        try {
            $serialized = $this->serializer->serialize($event);

            WriteInterceptEntryJob::dispatch(
                $eventClass,
                class_basename($eventClass),
                $serialized['payload'],
                $serialized['metadata'],
                now()->toIso8601String(),
            )->onConnection(config('middleman.queue_connection', 'redis'))
             ->onQueue(config('middleman.queue_name', 'middleman'));
        } catch (\Throwable $e) {
            // If interception fails, let the event through normally
            logger()->error('MiddleMan: Failed to intercept event, allowing passthrough', [
                'event' => $eventClass,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
