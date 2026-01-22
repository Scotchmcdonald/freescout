<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Milestone extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_type',
        'project_id',
        'title',
        'description',
        'sequence_order',
        'status',
        'progress_percentage',
        'target_date',
        'started_at',
        'completed_at',
        'assigned_to',
        'metadata',
        'notes',
        'blockers',
    ];

    protected $casts = [
        'progress_percentage' => 'decimal:2',
        'target_date' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'metadata' => 'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /*
    |--------------------------------------------------------------------------
    | Status Check Methods
    |--------------------------------------------------------------------------
    */

    public function isAchieved(): bool
    {
        return $this->status === 'achieved';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function isBlocked(): bool
    {
        return $this->status === 'blocked';
    }

    public function isSkipped(): bool
    {
        return $this->status === 'skipped';
    }

    public function isOverdue(): bool
    {
        if (!$this->target_date) {
            return false;
        }

        return !$this->isAchieved() && $this->target_date->isPast();
    }

    /*
    |--------------------------------------------------------------------------
    | Status Info Methods (for UI display)
    |--------------------------------------------------------------------------
    */

    public function getStatusInfo(): array
    {
        $statusMap = [
            'achieved' => [
                'label' => 'Achieved',
                'color' => 'bg-green-100 text-green-800',
                'icon' => 'check-circle',
                'ring' => 'bg-green-500',
            ],
            'in_progress' => [
                'label' => 'In Progress',
                'color' => 'bg-blue-100 text-blue-800',
                'icon' => 'clock',
                'ring' => 'bg-blue-500',
            ],
            'pending' => [
                'label' => 'Pending',
                'color' => 'bg-gray-100 text-gray-800',
                'icon' => 'clock',
                'ring' => 'bg-gray-300',
            ],
            'blocked' => [
                'label' => 'Blocked',
                'color' => 'bg-red-100 text-red-800',
                'icon' => 'x-circle',
                'ring' => 'bg-red-500',
            ],
            'skipped' => [
                'label' => 'Skipped',
                'color' => 'bg-yellow-100 text-yellow-800',
                'icon' => 'arrow-right',
                'ring' => 'bg-yellow-500',
            ],
        ];

        return $statusMap[$this->status] ?? $statusMap['pending'];
    }

    /*
    |--------------------------------------------------------------------------
    | Progress Methods
    |--------------------------------------------------------------------------
    */

    public function updateProgress(float $percentage): void
    {
        $this->update([
            'progress_percentage' => min(100, max(0, $percentage)),
        ]);

        // Auto-update status based on progress
        if ($percentage >= 100 && $this->status !== 'achieved') {
            $this->markAsAchieved();
        } elseif ($percentage > 0 && $this->status === 'pending') {
            $this->update(['status' => 'in_progress', 'started_at' => now()]);
        }
    }

    public function markAsAchieved(): void
    {
        $this->update([
            'status' => 'achieved',
            'progress_percentage' => 100.00,
            'completed_at' => now(),
        ]);
    }

    public function markAsBlocked(string $reason = null): void
    {
        $this->update([
            'status' => 'blocked',
            'blockers' => $reason,
        ]);
    }

    public function markAsInProgress(): void
    {
        $this->update([
            'status' => 'in_progress',
            'started_at' => $this->started_at ?? now(),
        ]);
    }

    public function unblock(): void
    {
        $this->update([
            'status' => 'in_progress',
            'blockers' => null,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeAchieved($query)
    {
        return $query->where('status', 'achieved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeBlocked($query)
    {
        return $query->where('status', 'blocked');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'in_progress']);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', '!=', 'achieved')
            ->where('target_date', '<', now());
    }

    public function scopeForProject($query, string $projectType, int $projectId)
    {
        return $query->where('project_type', $projectType)
            ->where('project_id', $projectId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sequence_order');
    }

    /*
    |--------------------------------------------------------------------------
    | Attribute Accessors
    |--------------------------------------------------------------------------
    */

    public function getDaysUntilTargetAttribute(): ?int
    {
        if (!$this->target_date) {
            return null;
        }

        return now()->diffInDays($this->target_date, false);
    }

    public function getDurationAttribute(): ?string
    {
        if (!$this->started_at) {
            return null;
        }

        $end = $this->completed_at ?? now();
        $diff = $this->started_at->diff($end);

        if ($diff->days > 0) {
            return $diff->days . ' day' . ($diff->days > 1 ? 's' : '');
        }

        if ($diff->h > 0) {
            return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '');
        }

        return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '');
    }
}
