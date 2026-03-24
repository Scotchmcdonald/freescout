<?php

declare(strict_types=1);

namespace Tests\Traits;

use Illuminate\Support\Facades\DB;

/**
 * AssertsIdempotency - Test assertions for event idempotency
 *
 * Provides assertions to verify that events are processed exactly once.
 */
trait AssertsIdempotency
{
    /**
     * Assert that an event was processed exactly once by a handler
     *
     * @param  string  $eventId  Event ID to check
     * @param  string  $handlerClass  Handler class name
     */
    protected function assertEventProcessedOnce(string $eventId, string $handlerClass): void
    {
        $count = DB::table('processed_events')
            ->where('event_id', $eventId)
            ->where('handler_class', $handlerClass)
            ->count();

        $this->assertEquals(
            1,
            $count,
            "Expected event {$eventId} to be processed exactly once by {$handlerClass}, but found {$count} records"
        );
    }

    /**
     * Assert that an event was NOT processed by a handler
     *
     * @param  string  $eventId  Event ID to check
     * @param  string  $handlerClass  Handler class name
     */
    protected function assertEventNotProcessed(string $eventId, string $handlerClass): void
    {
        $exists = DB::table('processed_events')
            ->where('event_id', $eventId)
            ->where('handler_class', $handlerClass)
            ->exists();

        $this->assertFalse(
            $exists,
            "Expected event {$eventId} to NOT be processed by {$handlerClass}, but it was"
        );
    }

    /**
     * Assert that an event was processed by multiple handlers
     *
     * @param  string  $eventId  Event ID to check
     * @param  array  $handlerClasses  Array of handler class names
     */
    protected function assertEventProcessedByAll(string $eventId, array $handlerClasses): void
    {
        foreach ($handlerClasses as $handlerClass) {
            $this->assertEventProcessedOnce($eventId, $handlerClass);
        }
    }

    /**
     * Get the count of times an event was processed
     *
     * @param  string  $eventId  Event ID to check
     * @param  string|null  $handlerClass  Optional handler class filter
     */
    protected function getEventProcessedCount(string $eventId, ?string $handlerClass = null): int
    {
        $query = DB::table('processed_events')
            ->where('event_id', $eventId);

        if ($handlerClass !== null) {
            $query->where('handler_class', $handlerClass);
        }

        return $query->count();
    }

    /**
     * Assert that an event processing was idempotent
     * (multiple handler calls resulted in single processing)
     *
     * @param  callable  $eventDispatcher  Function that dispatches the event
     * @param  string  $eventId  Event ID to check
     * @param  string  $handlerClass  Handler class name
     * @param  int  $dispatchCount  Number of times to dispatch
     */
    protected function assertIdempotentProcessing(
        callable $eventDispatcher,
        string $eventId,
        string $handlerClass,
        int $dispatchCount = 3
    ): void {
        // Dispatch event multiple times
        for ($i = 0; $i < $dispatchCount; $i++) {
            $eventDispatcher();
        }

        // Should still only be processed once
        $this->assertEventProcessedOnce($eventId, $handlerClass);
    }

    /**
     * Clear processed events for testing
     *
     * @param  string|null  $eventId  Optional event ID to clear
     */
    protected function clearProcessedEvents(?string $eventId = null): void
    {
        $query = DB::table('processed_events');

        if ($eventId !== null) {
            $query->where('event_id', $eventId);
        }

        $query->delete();
    }
}
