<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasFactory;

    // Medium constants
    public const MEDIUM_EMAIL = 1;
    public const MEDIUM_BROWSER = 2;
    public const MEDIUM_MOBILE = 3;

    // Event constants
    public const EVENT_NEW_CONVERSATION = 1;
    public const EVENT_USER_ASSIGNED = 2;
    public const EVENT_NEW_REPLY = 3;
    public const EVENT_CUSTOMER_REPLY = 4;
    public const EVENT_FOLLOWED = 5;

    protected $fillable = [
        'user_id',
        'medium',
        'event',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'medium' => 'integer',
            'event' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns the subscription.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if this is an email subscription.
     */
    public function isEmail(): bool
    {
        return $this->medium === self::MEDIUM_EMAIL;
    }

    /**
     * Check if this is a browser subscription.
     */
    public function isBrowser(): bool
    {
        return $this->medium === self::MEDIUM_BROWSER;
    }

    /**
     * Check if this is a mobile subscription.
     */
    public function isMobile(): bool
    {
        return $this->medium === self::MEDIUM_MOBILE;
    }
}
