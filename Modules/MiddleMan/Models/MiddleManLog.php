<?php

declare(strict_types=1);

namespace Modules\MiddleMan\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $event_class
 * @property string $event_name
 * @property array<string, mixed>|null $payload
 * @property array<string, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $fired_at
 * @property string|null $correlation_id
 * @property string|null $causation_id
 * @property bool $is_replay
 * @property bool $has_schema_drift
 * @property \Illuminate\Support\Carbon|null $created_at
 */
class MiddleManLog extends Model
{
    public $timestamps = false;

    protected $table = 'middleman_logs';

    protected $fillable = [
        'event_class',
        'event_name',
        'payload',
        'metadata',
        'fired_at',
        'correlation_id',
        'causation_id',
        'is_replay',
        'has_schema_drift',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'metadata' => 'array',
            'fired_at' => 'datetime',
            'created_at' => 'datetime',
            'is_replay' => 'boolean',
            'has_schema_drift' => 'boolean',
        ];
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

    /** @param Builder<self> $query
     * @return Builder<self> */
    public function scopeRecent(Builder $query, int $minutes = 60): Builder
    {
        return $query->where('fired_at', '>=', now()->subMinutes($minutes));
    }

    /** @param Builder<self> $query
     * @return Builder<self> */
    public function scopeForCorrelation(Builder $query, string $correlationId): Builder
    {
        return $query->where('correlation_id', $correlationId);
    }

    /** @param Builder<self> $query
     * @return Builder<self> */
    public function scopeWithDrift(Builder $query): Builder
    {
        return $query->where('has_schema_drift', true);
    }

    /** @param Builder<self> $query
     * @return Builder<self> */
    public function scopeReplays(Builder $query): Builder
    {
        return $query->where('is_replay', true);
    }
}
