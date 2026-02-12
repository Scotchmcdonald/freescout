<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @method static \Illuminate\Database\Eloquent\Builder|ReconciliationRun recent(int $days = 30)
 * @method static \Illuminate\Database\Eloquent\Builder|ReconciliationRun completed()
 * @method static \Illuminate\Database\Eloquent\Builder|ReconciliationRun running()
 * @method static \Illuminate\Database\Eloquent\Builder|ReconciliationRun withCriticalIssues()
 */
class ReconciliationRun extends Model
{
    protected $fillable = [
        'run_type',
        'status',
        'started_at',
        'completed_at',
        'scope',
        'items_checked',
        'total_discrepancies',
        'auto_corrected',
        'manual_review_required',
        'critical_issues',
        'success_rate',
        'summary',
        'metadata',
        'duration_seconds',
        'triggered_by',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'scope' => 'json',
        'metadata' => 'json',
        'items_checked' => 'integer',
        'total_discrepancies' => 'integer',
        'auto_corrected' => 'integer',
        'manual_review_required' => 'integer',
        'critical_issues' => 'integer',
        'success_rate' => 'decimal:2',
        'duration_seconds' => 'integer',
    ];

    /**
     * Get all discrepancies for this run.
     *
     * @return HasMany<ReconciliationDiscrepancy, $this>
     */
    public function discrepancies(): HasMany
    {
        return $this->hasMany(ReconciliationDiscrepancy::class);
    }

    /**
     * Get pending discrepancies requiring manual review.
     *
     * @return HasMany<ReconciliationDiscrepancy, $this>
     */
    public function pendingDiscrepancies(): HasMany
    {
        return $this->discrepancies()->where('resolution_status', 'pending');
    }

    /**
     * Get critical discrepancies.
     *
     * @return HasMany<ReconciliationDiscrepancy, $this>
     */
    public function criticalDiscrepancies(): HasMany
    {
        return $this->discrepancies()->where('severity', 'critical');
    }

    /**
     * Check if the run is complete.
     */
    public function isComplete(): bool
    {
        return in_array($this->status, ['completed', 'failed', 'partial']);
    }

    /**
     * Check if the run is currently running.
     */
    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    /**
     * Check if the run was successful.
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'completed' && $this->critical_issues === 0;
    }

    /**
     * Calculate success rate based on items checked and discrepancies found.
     */
    public function calculateSuccessRate(): float
    {
        if ($this->items_checked === 0) {
            return 0;
        }

        $correctItems = $this->items_checked - $this->total_discrepancies;
        return round(($correctItems / $this->items_checked) * 100, 2);
    }

    /**
     * Get status information with color and message.
     *
     * @return array<string, mixed>
     */
    public function getStatusInfo(): array
    {
        return match ($this->status) {
            'running' => [
                'status' => 'running',
                'color' => 'primary',
                'message' => 'Reconciliation in progress',
            ],
            'completed' => [
                'status' => 'completed',
                'color' => $this->critical_issues > 0 ? 'warning' : 'success',
                'message' => $this->critical_issues > 0
                    ? 'Completed with critical issues'
                    : 'Completed successfully',
            ],
            'failed' => [
                'status' => 'failed',
                'color' => 'danger',
                'message' => 'Reconciliation failed',
            ],
            'partial' => [
                'status' => 'partial',
                'color' => 'warning',
                'message' => 'Completed with errors',
            ],
            default => [
                'status' => 'unknown',
                'color' => 'gray',
                'message' => 'Unknown status',
            ],
        };
    }

    /**
     * Get human-readable duration.
     */
    public function getDurationAttribute(): ?string
    {
        if (!$this->duration_seconds) {
            return null;
        }

        $minutes = floor($this->duration_seconds / 60);
        $seconds = $this->duration_seconds % 60;

        if ($minutes > 0) {
            return "{$minutes}m {$seconds}s";
        }

        return "{$seconds}s";
    }

    /**
     * Query scope for completed runs.
     *
     * @param \Illuminate\Database\Eloquent\Builder<static> $query
     */
    public function scopeCompleted(\Illuminate\Database\Eloquent\Builder $query): void
    {
        $query->where('status', 'completed');
    }

    /**
     * Query scope for failed runs.
     *
     * @param \Illuminate\Database\Eloquent\Builder<static> $query
     */
    public function scopeFailed(\Illuminate\Database\Eloquent\Builder $query): void
    {
        $query->where('status', 'failed');
    }

    /**
     * Query scope for running reconciliations.
     *
     * @param \Illuminate\Database\Eloquent\Builder<static> $query
     */
    public function scopeRunning(\Illuminate\Database\Eloquent\Builder $query): void
    {
        $query->where('status', 'running');
    }

    /**
     * Query scope for recent runs.
     *
     * @param \Illuminate\Database\Eloquent\Builder<static> $query
     */
    public function scopeRecent(\Illuminate\Database\Eloquent\Builder $query, int $days = 30): void
    {
        $query->where('started_at', '>=', now()->subDays($days));
    }

    /**
     * Query scope for runs with critical issues.
     *
     * @param \Illuminate\Database\Eloquent\Builder<static> $query
     */
    public function scopeWithCriticalIssues(\Illuminate\Database\Eloquent\Builder $query): void
    {
        $query->where('critical_issues', '>', 0);
    }
}
