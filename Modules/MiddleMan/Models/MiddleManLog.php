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
    ];

    protected function casts(): array
    {
        return [
            'payload'  => 'array',
            'metadata' => 'array',
            'fired_at' => 'datetime',
            'created_at' => 'datetime',
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
}
