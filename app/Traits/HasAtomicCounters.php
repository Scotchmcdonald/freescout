<?php

declare(strict_types=1);

namespace App\Traits;

use App\Services\AtomicCounterService;

/**
 * HasAtomicCounters - Model trait for thread-safe counter operations
 * 
 * Usage:
 * class ClientAssetCounter extends Model {
 *     use HasAtomicCounters;
 * }
 * 
 * $counter->incrementCounter('count', 1);
 * $counter->decrementCounter('active_count', 5);
 */
trait HasAtomicCounters
{
    /**
     * Atomically increment a counter column
     * 
     * @param string $column Counter column name
     * @param int $amount Amount to increment by
     * @return int New counter value
     */
    public function incrementCounter(string $column, int $amount = 1): int
    {
        $service = app(AtomicCounterService::class);
        
        return $service->increment(
            table: $this->getTable(),
            where: [$this->getKeyName() => $this->getKey()],
            column: $column,
            amount: $amount
        );
    }
    
    /**
     * Atomically decrement a counter column
     * 
     * @param string $column Counter column name
     * @param int $amount Amount to decrement by
     * @return int New counter value
     */
    public function decrementCounter(string $column, int $amount = 1): int
    {
        $service = app(AtomicCounterService::class);
        
        return $service->decrement(
            table: $this->getTable(),
            where: [$this->getKeyName() => $this->getKey()],
            column: $column,
            amount: $amount
        );
    }
    
    /**
     * Get current counter value
     * 
     * @param string $column Counter column name
     * @return int Current value
     */
    public function getCounterValue(string $column): int
    {
        $service = app(AtomicCounterService::class);
        
        return $service->get(
            table: $this->getTable(),
            where: [$this->getKeyName() => $this->getKey()],
            column: $column
        );
    }
    
    /**
     * Set counter to specific value
     * 
     * @param string $column Counter column name
     * @param int $value New value
     * @return void
     */
    public function setCounterValue(string $column, int $value): void
    {
        $service = app(AtomicCounterService::class);
        
        $service->set(
            table: $this->getTable(),
            where: [$this->getKeyName() => $this->getKey()],
            column: $column,
            value: $value
        );
    }
}
