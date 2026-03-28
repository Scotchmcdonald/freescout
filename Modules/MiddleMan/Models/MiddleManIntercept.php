<?php

declare(strict_types=1);

namespace Modules\MiddleMan\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MiddleManIntercept extends Model
{
    protected $table = 'middleman_intercepts';

    protected $fillable = [
        'event_class',
        'event_name',
        'payload',
        'metadata',
        'status',
        'sort_order',
        'intercepted_at',
        'fired_at',
        'fired_by',
    ];

    protected function casts(): array
    {
        return [
            'payload'        => 'array',
            'metadata'       => 'array',
            'intercepted_at' => 'datetime',
            'fired_at'       => 'datetime',
            'sort_order'     => 'integer',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Constants
    |--------------------------------------------------------------------------
    */

    public const STATUS_PENDING   = 'pending';
    public const STATUS_FIRED     = 'fired';
    public const STATUS_DISCARDED = 'discarded';

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function firedByUser(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'fired_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('intercepted_at');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function markFired(int $userId): void
    {
        $this->update([
            'status'   => self::STATUS_FIRED,
            'fired_at' => now(),
            'fired_by' => $userId,
        ]);
    }

    public function markDiscarded(): void
    {
        $this->update(['status' => self::STATUS_DISCARDED]);
    }
}
