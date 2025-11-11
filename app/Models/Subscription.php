<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasFactory;

    // Mediums
    public const MEDIUM_EMAIL = 1;
    public const MEDIUM_BROWSER = 2;
    public const MEDIUM_MOBILE = 3;

    // Events - Notify me when…
    public const EVENT_NEW_CONVERSATION = 1;
    public const EVENT_CONVERSATION_ASSIGNED_TO_ME = 2;
    public const EVENT_CONVERSATION_ASSIGNED = 6;
    public const EVENT_FOLLOWED_CONVERSATION_UPDATED = 13;

    // Events - Notify me when a customer replies…
    public const EVENT_CUSTOMER_REPLIED_TO_MY = 3;
    public const EVENT_CUSTOMER_REPLIED_TO_UNASSIGNED = 4;
    public const EVENT_CUSTOMER_REPLIED_TO_ASSIGNED = 7;

    // Events - Notify me when another user replies or adds a note…
    public const EVENT_USER_REPLIED_TO_MY = 5;
    public const EVENT_USER_REPLIED_TO_UNASSIGNED = 8;
    public const EVENT_USER_REPLIED_TO_ASSIGNED = 9;

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
        return $this->medium === 1;
    }

    /**
     * Check if this is a browser subscription.
     */
    public function isBrowser(): bool
    {
        return $this->medium === 2;
    }

    /**
     * Check if this is a mobile subscription.
     */
    public function isMobile(): bool
    {
        return $this->medium === 3;
    }
}
