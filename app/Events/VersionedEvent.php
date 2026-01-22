<?php

namespace App\Events;

use Illuminate\Support\Str;

/**
 * VersionedEvent - Base class for all platform events
 * 
 * Provides:
 * - Unique event ID for idempotency tracking
 * - Schema versioning with automatic migration
 * - Immutable data transfer via readonly DTOs
 * 
 * Usage:
 * class GoogleUserSynced extends VersionedEvent {
 *     const CURRENT_VERSION = 2;
 *     
 *     public function __construct(
 *         public readonly GoogleUserSyncedData $data,
 *         ?string $eventId = null
 *     ) {
 *         parent::__construct($data, $eventId);
 *     }
 * }
 */
abstract class VersionedEvent
{
    public string $eventId;
    public int $version;
    public mixed $data;
    
    const CURRENT_VERSION = 1;
    
    public function __construct(mixed $data, ?string $eventId = null, ?int $version = null)
    {
        $this->eventId = $eventId ?? (string) Str::uuid();
        $this->version = $version ?? static::CURRENT_VERSION;
        
        // Auto-migrate if receiving older version
        if ($this->version < static::CURRENT_VERSION) {
            $this->data = static::migrateUp($data, $this->version);
            $this->version = static::CURRENT_VERSION;
        } else {
            $this->data = $data;
        }
    }
    
    /**
     * Override in subclasses to migrate older event versions
     * 
     * Example:
     * protected static function migrateUp(mixed $data, int $fromVersion): mixed {
     *     if ($fromVersion === 1) {
     *         $data->newField = 'default_value';
     *     }
     *     return $data;
     * }
     */
    protected static function migrateUp(mixed $data, int $fromVersion): mixed
    {
        return $data; // Default: no migration needed
    }
}
