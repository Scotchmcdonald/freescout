<?php

declare(strict_types=1);

namespace Modules\AppHealth\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $snapshot_date
 * @property string $overall_status
 * @property string $recommendation
 * @property int $breach_count
 * @property array<string, mixed>|null $payload
 * @property Carbon|null $evaluated_at
 */
class ScalingScorecardSnapshot extends Model
{
    /** @use HasFactory<\Illuminate\Database\Eloquent\Factories\Factory<static>> */
    use HasFactory;

    protected $table = 'app_health_scaling_scorecard_snapshots';

    protected $fillable = [
        'snapshot_date',
        'overall_status',
        'recommendation',
        'breach_count',
        'payload',
        'evaluated_at',
    ];

    protected $casts = [
        'snapshot_date' => 'date',
        'payload' => 'array',
        'evaluated_at' => 'datetime',
    ];
}
