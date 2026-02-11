<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $resource_type
 * @property string $resource_id
 * @property string $channel_id
 * @property string|null $token
 * @property string $webhook_url
 * @property Carbon $expiration_time
 * @property bool $is_active
 * @property Carbon|null $last_notification_at
 * @property int $notification_count
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class GooglePushChannel extends Model
{
    use HasFactory;

    protected $fillable = [
        'resource_type',
        'resource_id',
        'channel_id',
        'token',
        'webhook_url',
        'expiration_time',
        'is_active',
        'last_notification_at',
        'notification_count',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expiration_time' => 'datetime',
            'last_notification_at' => 'datetime',
            'is_active' => 'boolean',
            'notification_count' => 'integer',
            'metadata' => 'json',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Check if channel is expired.
     */
    public function isExpired(): bool
    {
        return $this->expiration_time->isPast();
    }

    /**
     * Check if channel is expiring soon (within 24 hours).
     */
    public function isExpiringSoon(): bool
    {
        return $this->expiration_time->isFuture() && 
               $this->expiration_time->lte(now()->addHours(24));
    }

    /**
     * Get health status.
     *
     * @return array{status: string, color: string, message: string}
     */
    public function getHealthStatus(): array
    {
        if (!$this->is_active) {
            return [
                'status' => 'inactive',
                'color' => 'gray',
                'message' => 'Channel is inactive',
            ];
        }

        if ($this->isExpired()) {
            return [
                'status' => 'expired',
                'color' => 'danger',
                'message' => 'Channel expired ' . $this->expiration_time->diffForHumans(),
            ];
        }

        if ($this->isExpiringSoon()) {
            return [
                'status' => 'expiring',
                'color' => 'warning',
                'message' => 'Expires ' . $this->expiration_time->diffForHumans(),
            ];
        }

        if ($this->last_notification_at && $this->last_notification_at->lt(now()->subHours(24))) {
            return [
                'status' => 'stale',
                'color' => 'warning',
                'message' => 'No notifications in 24h',
            ];
        }

        return [
            'status' => 'healthy',
            'color' => 'success',
            'message' => 'Active and receiving notifications',
        ];
    }

    /**
     * Get time until expiration in human-readable format.
     */
    public function getExpiresInAttribute(): string
    {
        if ($this->isExpired()) {
            return 'Expired';
        }

        return $this->expiration_time->diffForHumans(null, CarbonInterface::DIFF_ABSOLUTE);
    }

    /**
     * Scope to get active channels.
     */
    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Scope to get expired channels.
     */
    public function scopeExpired(\Illuminate\Database\Eloquent\Builder $query): void
    {
        $query->where('expiration_time', '<', now());
    }

    /**
     * Scope to get expiring soon channels.
     */
    public function scopeExpiringSoon(\Illuminate\Database\Eloquent\Builder $query): void
    {
        $query->where('expiration_time', '>', now())
                    ->where('expiration_time', '<=', now()->addHours(24));
    }
}
