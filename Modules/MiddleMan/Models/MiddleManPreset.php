<?php

declare(strict_types=1);

namespace Modules\MiddleMan\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Saved marshal presets — reusable parameter sets for the Marshal UI.
 *
 * Each preset stores a named combination of constructor parameters
 * for a specific event class, allowing developers to quickly re-fire
 * common test scenarios.
 *
 * @property int $id
 * @property string $event_class
 * @property string $name
 * @property array<string, mixed>|null $payload
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class MiddleManPreset extends Model
{
    protected $table = 'middleman_presets';

    protected $fillable = [
        'event_class',
        'name',
        'payload',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /** @return BelongsTo<\App\Models\User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /** @param Builder<self> $query
     * @return Builder<self> */
    public function scopeForEvent(Builder $query, string $eventClass): Builder
    {
        return $query->where('event_class', $eventClass);
    }
}
