<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
// use Modules\ContractManager\Models\Contract; // Core Blindness: Injected via ContractManagerServiceProvider
use Modules\PIB\Models\Invoice;

/**
 * @property-read \Modules\ContractManager\Models\Contract $contract
 */
class Milestone extends Model
{
    /** @use HasFactory<\Illuminate\Database\Eloquent\Factories\Factory<static>> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_type',
        'project_id',
        'title',
        'description',
        'sequence_order',
        'status',
        'progress_percentage',
        'billing_amount',
        'client_approved',
        'client_approved_at',
        'contract_id',
        'invoice_id',
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
        'billing_amount' => 'decimal:2',
        'client_approved' => 'boolean',
        'client_approved_at' => 'datetime',
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

    /** @return BelongsTo<User, $this> */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    // Contract relationship is injected via ContractManagerServiceProvider
    // public function contract(): BelongsTo
    // {
    //     return $this->belongsTo(Contract::class, 'contract_id');
    // }

    // Invoice relationship is injected via PIBServiceProvider to maintain Core Blindness
    // public function invoice(): BelongsTo
    // {
    //     return $this->belongsTo(Invoice::class, 'invoice_id');
    // }

    /*
    |--------------------------------------------------------------------------
    | Billing Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if this milestone can generate a partial invoice.
     * Requires: status achieved, client approved, no existing invoice, and a billing amount.
     */
    public function canGenerateInvoice(): bool
    {
        return $this->isAchieved()
            && $this->client_approved
            && $this->billing_amount > 0
            && $this->invoice_id === null;
    }

    /**
     * Approve milestone for billing (client approval step).
     */
    public function approveForBilling(): void
    {
        $this->update([
            'client_approved' => true,
            'client_approved_at' => now(),
        ]);
    }

    /**
     * Generate a partial invoice for this milestone.
     */
    public function generateInvoice(): ?Invoice
    {
        if (! $this->canGenerateInvoice()) {
            return null;
        }

        $invoice = Invoice::create([
            'client_id' => $this->contract->client_id,
            'company_id' => $this->contract->client->company_id ?? 1,
            'contract_id' => $this->contract_id,
            'invoice_number' => 'INV-MS-' . strtoupper(uniqid()),
            'status' => 'draft',
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'subtotal' => $this->billing_amount,
            'tax_amount' => 0,
            'total_amount' => $this->billing_amount,
            'special_notes' => "Milestone: {$this->title}",
        ]);

        $this->update(['invoice_id' => $invoice->id]);

        return $invoice;
    }

    /**
     * Get the sum of billing amounts for all milestones on the same contract.
     */
    public static function projectTotal(int $contractId): float
    {
        return (float) static::where('contract_id', $contractId)->sum('billing_amount');
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

    /** @return array<string, mixed> */
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

    /**
     * @param \Illuminate\Database\Eloquent\Builder<static> $query
     */
    public function scopeAchieved(\Illuminate\Database\Eloquent\Builder $query): void
    {
        $query->where('status', 'achieved');
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder<static> $query
     */
    public function scopePending(\Illuminate\Database\Eloquent\Builder $query): void
    {
        $query->where('status', 'pending');
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder<static> $query
     */
    public function scopeInProgress(\Illuminate\Database\Eloquent\Builder $query): void
    {
        $query->where('status', 'in_progress');
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder<static> $query
     */
    public function scopeBlocked(\Illuminate\Database\Eloquent\Builder $query): void
    {
        $query->where('status', 'blocked');
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder<static> $query
     */
    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): void
    {
        $query->whereIn('status', ['pending', 'in_progress']);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder<static> $query
     */
    public function scopeOverdue(\Illuminate\Database\Eloquent\Builder $query): void
    {
        $query->where('status', '!=', 'achieved')
            ->where('target_date', '<', now());
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder<static> $query
     */
    public function scopeForProject(\Illuminate\Database\Eloquent\Builder $query, string $projectType, int $projectId): void
    {
        $query->where('project_type', $projectType)
            ->where('project_id', $projectId);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder<static> $query
     */
    public function scopeOrdered(\Illuminate\Database\Eloquent\Builder $query): void
    {
        $query->orderBy('sequence_order');
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

        return (int) now()->diffInDays($this->target_date, false);
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
