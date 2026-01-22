<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'alert_type',
        'channels',
        'frequency',
        'thresholds',
        'is_active',
    ];

    protected $casts = [
        'channels' => 'array',
        'thresholds' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the available alert types definition.
     */
    public static function getAlertTypes(): array
    {
        return [
            'unusual_variance' => [
                'label' => 'Unusual Billing Variance',
                'description' => 'When a client invoice varies by more than 20% from the previous month.',
                'category' => 'finance',
                'icon' => 'currency-dollar',
                'color' => 'warning',
            ],
            'circuit_breaker' => [
                'label' => 'Circuit Breaker Trip',
                'description' => 'When an external service (Google/Action1) error rate exceeds safe thresholds.',
                'category' => 'infrastructure',
                'icon' => 'lightning-bolt',
                'color' => 'danger',
            ],
            'asset_conflict' => [
                'label' => 'Asset Data Conflict',
                'description' => 'When data sources report conflicting information for the same asset.',
                'category' => 'asset',
                'icon' => 'exclamation',
                'color' => 'yellow',
            ],
            'module_update' => [
                'label' => 'Module Update Available',
                'description' => 'When a new version of an installed module is available in the catalog.',
                'category' => 'system',
                'icon' => 'cube',
                'color' => 'info',
            ],
            'high_error_rate' => [
                'label' => 'High System Error Rate',
                'description' => 'When global application error rate spikes above 5%.',
                'category' => 'system',
                'icon' => 'chart-bar',
                'color' => 'danger',
            ],
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hasChannel(string $channel): bool
    {
        return in_array($channel, $this->channels ?? []);
    }
}
