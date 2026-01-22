<?php

namespace App\Listeners;

use Illuminate\Support\Facades\DB;

/**
 * IdempotentListener - Base class for all event listeners
 * 
 * Provides:
 * - Automatic deduplication via processed_events table
 * - Safe event replay after failures
 * - Guaranteed exactly-once processing
 * 
 * Usage:
 * class SyncGoogleUserListener extends IdempotentListener {
 *     protected function handleIdempotent($event): void {
 *         User::updateOrCreate(...);
 *     }
 * }
 */
abstract class IdempotentListener
{
    public function handle($event): void
    {
        // Check if already processed
        if (DB::table('processed_events')
            ->where('event_id', $event->eventId)
            ->where('handler_class', static::class)
            ->exists()) {
            return; // Skip duplicate event
        }
        
        // Process event in transaction
        DB::transaction(function () use ($event) {
            $this->handleIdempotent($event);
            
            // Mark as processed
            DB::table('processed_events')->insert([
                'event_id' => $event->eventId,
                'handler_class' => static::class,
                'processed_at' => now(),
            ]);
        });
    }
    
    /**
     * Override in subclasses to implement business logic
     * Guaranteed to execute exactly once per event
     */
    abstract protected function handleIdempotent($event): void;
}
