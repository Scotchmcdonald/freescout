<?php

declare(strict_types=1);

namespace Modules\MiddleMan\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Saved marshal presets — reusable parameter sets for the Marshal UI.
 *
 * Each preset stores a named combination of constructor parameters
 * for a specific event class, allowing developers to quickly re-fire
 * common test scenarios.
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeForEvent($query, string $eventClass)
    {
        return $query->where('event_class', $eventClass);
    }
}
