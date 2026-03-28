<?php

declare(strict_types=1);

namespace Modules\MiddleMan\Models;

use Illuminate\Database\Eloquent\Model;

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
            'payload'          => 'array',
            'metadata'         => 'array',
            'fired_at'         => 'datetime',
            'created_at'       => 'datetime',
            'is_replay'        => 'boolean',
            'has_schema_drift' => 'boolean',
        ];
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

    public function scopeRecent($query, int $minutes = 60)
    {
        return $query->where('fired_at', '>=', now()->subMinutes($minutes));
    }

    public function scopeForCorrelation($query, string $correlationId)
    {
        return $query->where('correlation_id', $correlationId);
    }

    public function scopeWithDrift($query)
    {
        return $query->where('has_schema_drift', true);
    }

    public function scopeReplays($query)
    {
        return $query->where('is_replay', true);
    }
}
