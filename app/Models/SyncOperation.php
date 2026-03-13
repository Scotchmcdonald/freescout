<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncOperation extends Model
{
    protected $fillable = [
        'operation_type',
        'source',
        'status',
        'total_items',
        'processed_items',
        'failed_items',
        'success_items',
        'started_at',
        'completed_at',
        'last_progress_at',
        'error_message',
        'checkpoint_data',
        'failures',
        'items_per_second',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'last_progress_at' => 'datetime',
        'checkpoint_data' => 'array',
        'failures' => 'array',
        'items_per_second' => 'decimal:2',
    ];

    /**
     * Start a new sync operation
     */
    public static function start(string $operationType, string $source, int $totalItems = 0): self
    {
        return self::create([
            'operation_type' => $operationType,
            'source' => $source,
            'status' => 'running',
            'total_items' => $totalItems,
            'started_at' => now(),
            'last_progress_at' => now(),
        ]);
    }

    /**
     * Update progress
     */
    public function updateProgress(int $processedItems, int $successItems, int $failedItems): void
    {
        $this->update([
            'processed_items' => $processedItems,
            'success_items' => $successItems,
            'failed_items' => $failedItems,
            'last_progress_at' => now(),
            'items_per_second' => $this->calculateItemsPerSecond(),
        ]);
    }

    /**
     * Record a failure for a specific item
     */
    public function recordFailure(string $itemIdentifier, string $reason): void
    {
        $failures = $this->failures ?? [];
        $failures[] = [
            'item' => $itemIdentifier,
            'reason' => $reason,
            'failed_at' => now()->toIso8601String(),
        ];

        $this->update([
            'failures' => $failures,
            'failed_items' => count($failures),
        ]);
    }

    /**
     * Save checkpoint for resume capability
     *
     * @param  array<string, mixed>  $checkpointData
     */
    public function saveCheckpoint(array $checkpointData): void
    {
        $this->update([
            'checkpoint_data' => $checkpointData,
            'last_progress_at' => now(),
        ]);
    }

    /**
     * Mark as completed
     */
    public function markCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'items_per_second' => $this->calculateItemsPerSecond(),
        ]);
    }

    /**
     * Mark as failed
     */
    public function markFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark as stalled (no progress for >5 minutes)
     */
    public function markStalled(): void
    {
        $this->update(['status' => 'stalled']);
    }

    /**
     * Resume a stalled or paused operation
     */
    public function resume(): void
    {
        $this->update([
            'status' => 'running',
            'last_progress_at' => now(),
        ]);
    }

    /**
     * Calculate items per second throughput
     */
    protected function calculateItemsPerSecond(): float
    {
        if (! $this->started_at || $this->processed_items === 0) {
            return 0;
        }

        $secondsElapsed = now()->diffInSeconds($this->started_at);
        if ($secondsElapsed == 0) {
            return 0;
        }

        return round($this->processed_items / $secondsElapsed, 2);
    }

    /**
     * Get progress percentage
     */
    public function getProgressPercentageAttribute(): int
    {
        if ($this->total_items === 0) {
            return 0;
        }

        return (int) round(($this->processed_items / $this->total_items) * 100);
    }

    /**
     * Check if stalled (no progress in last 5 minutes)
     */
    public function isStalled(): bool
    {
        if (! $this->last_progress_at || $this->status !== 'running') {
            return false;
        }

        return now()->diffInMinutes($this->last_progress_at) >= 5;
    }

    /**
     * Get estimated time remaining
     */
    public function getEstimatedTimeRemainingAttribute(): ?string
    {
        if ($this->items_per_second <= 0 || $this->total_items === 0) {
            return null;
        }

        $remainingItems = $this->total_items - $this->processed_items;
        $secondsRemaining = $remainingItems / $this->items_per_second;

        if ($secondsRemaining < 60) {
            return round($secondsRemaining).'s';
        } elseif ($secondsRemaining < 3600) {
            return round($secondsRemaining / 60).'m';
        } else {
            return round($secondsRemaining / 3600, 1).'h';
        }
    }

    /**
     * Scope to get recent operations
     *
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     */
    public function scopeRecent(\Illuminate\Database\Eloquent\Builder $query, int $hours = 24): void
    {
        $query->where('started_at', '>=', now()->subHours($hours))
            ->orderBy('started_at', 'desc');
    }

    /**
     * Scope to get active operations
     *
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     */
    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): void
    {
        $query->whereIn('status', ['running', 'paused']);
    }
}
