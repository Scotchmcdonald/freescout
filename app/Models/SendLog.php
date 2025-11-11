<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $thread_id
 * @property int|null $customer_id
 * @property int|null $user_id
 * @property string|null $message_id
 * @property string $email
 * @property int $status
 * @property string|null $status_message
 * @property int $opens
 * @property int $clicks
 * @property \Illuminate\Support\Carbon|null $opened_at
 * @property \Illuminate\Support\Carbon|null $clicked_at
 * @property array|null $meta
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Thread $thread
 * @property-read \App\Models\Customer|null $customer
 * @property-read \App\Models\User|null $user
 *
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class SendLog extends Model
{
    use HasFactory;

    // Status constants
    public const STATUS_ACCEPTED = 1; // accepted (for delivery)

    public const STATUS_SEND_ERROR = 2;

    public const STATUS_DELIVERY_SUCCESS = 4;

    public const STATUS_DELIVERY_ERROR = 5; // rejected, failed

    public const STATUS_OPENED = 6;

    public const STATUS_CLICKED = 7;

    public const STATUS_UNSUBSCRIBED = 8;

    public const STATUS_COMPLAINED = 9;

    public const STATUS_SEND_INTERMEDIATE_ERROR = 10;

    public const STATUS_SENT = 1; // alias for ACCEPTED

    // Mail type constants
    public const MAIL_TYPE_EMAIL_TO_CUSTOMER = 1;

    public const MAIL_TYPE_USER_NOTIFICATION = 2;

    public const MAIL_TYPE_AUTO_REPLY = 3;

    public const MAIL_TYPE_INVITE = 4;

    public const MAIL_TYPE_PASSWORD_CHANGED = 5;

    public const MAIL_TYPE_WRONG_USER_EMAIL_MESSAGE = 6;

    public const MAIL_TYPE_TEST = 7;

    public const MAIL_TYPE_ALERT = 8;

    protected $fillable = [
        'thread_id',
        'customer_id',
        'user_id',
        'message_id',
        'email',
        'mail_type',
        'status',
        'status_message',
        'opens',
        'clicks',
        'opened_at',
        'clicked_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'thread_id' => 'integer',
            'customer_id' => 'integer',
            'user_id' => 'integer',
            'status' => 'integer',
            'opens' => 'integer',
            'clicks' => 'integer',
            'opened_at' => 'datetime',
            'clicked_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the thread associated with this send log.
     */
    public function thread(): BelongsTo
    {
        return $this->belongsTo(Thread::class);
    }

    /**
     * Get the customer associated with this send log.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the user associated with this send log.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if email was sent successfully.
     */
    public function isSent(): bool
    {
        return $this->status === 1;
    }

    /**
     * Check if email failed to send.
     */
    public function isFailed(): bool
    {
        return $this->status === 2;
    }

    /**
     * Check if email was opened.
     */
    public function wasOpened(): bool
    {
        return $this->opens > 0;
    }

    /**
     * Check if any links were clicked.
     */
    public function wasClicked(): bool
    {
        return $this->clicks > 0;
    }
}
