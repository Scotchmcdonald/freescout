<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReconciliationDiscrepancy extends Model
{
    protected $fillable = [
        'reconciliation_run_id',
        'entity_type',
        'entity_id',
        'field_name',
        'expected_value',
        'actual_value',
        'source_system',
        'severity',
        'resolution_status',
        'resolution_action',
        'resolved_at',
        'resolved_by',
        'resolution_notes',
        'metadata',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'metadata' => 'json',
    ];

    /**
     * Get the reconciliation run this discrepancy belongs to.
     */
    public function run(): BelongsTo
    {
        return $this->belongsTo(ReconciliationRun::class, 'reconciliation_run_id');
    }

    /**
     * Get the user who resolved this discrepancy.
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Check if discrepancy is resolved.
     */
    public function isResolved(): bool
    {
        return in_array($this->resolution_status, ['auto_corrected', 'resolved', 'ignored']);
    }

    /**
     * Check if discrepancy requires manual review.
     */
    public function requiresManualReview(): bool
    {
        return in_array($this->resolution_status, ['pending', 'manual_review']);
    }

    /**
     * Check if discrepancy is critical.
     */
    public function isCritical(): bool
    {
        return $this->severity === 'critical';
    }

    /**
     * Get severity information with color and icon.
     */
    public function getSeverityInfo(): array
    {
        return match ($this->severity) {
            'critical' => [
                'severity' => 'critical',
                'color' => 'danger',
                'label' => 'Critical',
                'icon' => 'exclamation-circle',
            ],
            'high' => [
                'severity' => 'high',
                'color' => 'warning',
                'label' => 'High',
                'icon' => 'exclamation-triangle',
            ],
            'medium' => [
                'severity' => 'medium',
                'color' => 'warning',
                'label' => 'Medium',
                'icon' => 'information-circle',
            ],
            'low' => [
                'severity' => 'low',
                'color' => 'gray',
                'label' => 'Low',
                'icon' => 'information-circle',
            ],
            default => [
                'severity' => 'unknown',
                'color' => 'gray',
                'label' => 'Unknown',
                'icon' => 'question-mark-circle',
            ],
        };
    }

    /**
     * Get resolution status information.
     */
    public function getResolutionInfo(): array
    {
        return match ($this->resolution_status) {
            'pending' => [
                'status' => 'pending',
                'color' => 'gray',
                'label' => 'Pending Review',
            ],
            'auto_corrected' => [
                'status' => 'auto_corrected',
                'color' => 'success',
                'label' => 'Auto-Corrected',
            ],
            'manual_review' => [
                'status' => 'manual_review',
                'color' => 'warning',
                'label' => 'Manual Review',
            ],
            'resolved' => [
                'status' => 'resolved',
                'color' => 'success',
                'label' => 'Resolved',
            ],
            'ignored' => [
                'status' => 'ignored',
                'color' => 'gray',
                'label' => 'Ignored',
            ],
            default => [
                'status' => 'unknown',
                'color' => 'gray',
                'label' => 'Unknown',
            ],
        };
    }

    /**
     * Query scope for pending discrepancies.
     */
    public function scopePending($query)
    {
        return $query->where('resolution_status', 'pending');
    }

    /**
     * Query scope for resolved discrepancies.
     */
    public function scopeResolved($query)
    {
        return $query->whereIn('resolution_status', ['auto_corrected', 'resolved', 'ignored']);
    }

    /**
     * Query scope for critical discrepancies.
     */
    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    /**
     * Query scope for unresolved critical issues.
     */
    public function scopeUnresolvedCritical($query)
    {
        return $query->where('severity', 'critical')
                     ->whereIn('resolution_status', ['pending', 'manual_review']);
    }
}
