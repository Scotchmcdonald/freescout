<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $conversation_id
 * @property int|null $user_id
 * @property int|null $customer_id
 * @property int|null $created_by_user_id
 * @property int|null $created_by_customer_id
 * @property int|null $edited_by_user_id
 * @property \Illuminate\Support\Carbon|null $edited_at
 * @property int $type
 * @property int $status
 * @property int $state
 * @property int|null $action_type
 * @property int|null $source_via
 * @property int|null $source_type
 * @property string|null $body
 * @property array<int, string>|null $to
 * @property array<int, string>|null $cc
 * @property array<int, string>|null $bcc
 * @property string|null $from
 * @property string|array<string, mixed>|null $headers
 * @property string|null $message_id
 * @property \Illuminate\Support\Carbon|null $opened_at
 * @property array<string, mixed>|null $meta
 * @property bool $first
 * @property bool $has_attachments
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Conversation $conversation
 * @property-read \App\Models\User|null $user
 * @property-read \App\Models\Customer|null $customer
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Attachment> $attachments
 *
 * @method static \Illuminate\Database\Eloquent\Builder<Thread>|Thread create(array<string, mixed> $attributes = [])
 *
 * @mixin \Illuminate\Database\Eloquent\Builder<Thread>
 */
class Thread extends Model
{
    /** @use HasFactory<\Database\Factories\ThreadFactory> */
    use HasFactory;
    use SoftDeletes;

    // Thread type constants
    public const TYPE_MESSAGE = 1;

    public const TYPE_NOTE = 2;

    public const TYPE_CUSTOMER = 3;

    public const TYPE_LINEITEM = 4;

    public const TYPE_CHAT = 8;

    public const TYPE_BOUNCE = 9; // For bounce detection

    public const TYPE_DRAFT = 5;

    // Thread state constants
    public const STATE_DRAFT = 1;

    public const STATE_PUBLISHED = 2;

    public const STATE_HIDDEN = 3;

    public const STATE_REVIEW = 4;

    protected $fillable = [
        'conversation_id',
        'user_id',
        'customer_id',
        'created_by_user_id',
        'created_by_customer_id',
        'edited_by_user_id',
        'edited_at',
        'type',
        'status',
        'state',
        'action_type',
        'source_via',
        'source_type',
        'body',
        'to',
        'cc',
        'bcc',
        'from',
        'headers',
        'message_id',
        'opened_at',
        'meta',
        'first',
        'has_attachments',
        'imported',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => 'integer',
            'status' => 'integer',
            'state' => 'integer',
            'action_type' => 'integer',
            'source_via' => 'integer',
            'source_type' => 'integer',
            'to' => 'json',
            'cc' => 'json',
            'bcc' => 'json',
            'meta' => 'array',
            'opened_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'imported' => 'boolean',
        ];
    }

    /**
     * Get the conversation that owns the thread.
     *
     * @return BelongsTo<Conversation, $this>
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Get the user that created the thread.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Get the customer associated with the thread.
     *
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'created_by_customer_id');
    }

    /**
     * Get the user who created the thread (for email replies from users).
     *
     * @return BelongsTo<User, $this>
     */
    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Get the user who edited the thread.
     *
     * @return BelongsTo<User, $this>
     */
    public function editedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by_user_id');
    }

    /**
     * Get the attachments for the thread.
     *
     * @return HasMany<Attachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    /**
     * Get the send logs for the thread.
     *
     * @return HasMany<SendLog, $this>
     */
    public function sendLogs(): HasMany
    {
        return $this->hasMany(SendLog::class);
    }

    /**
     * Check if this is a message from customer.
     */
    public function isCustomerMessage(): bool
    {
        return $this->type === self::TYPE_CUSTOMER;
    }

    /**
     * Check if this is a message from user.
     */
    public function isUserMessage(): bool
    {
        return $this->type === self::TYPE_MESSAGE;
    }

    /**
     * Check if this is a note.
     */
    public function isNote(): bool
    {
        return $this->type === self::TYPE_NOTE;
    }

    /**
     * Check if thread is from an auto-responder.
     * Matches original FreeScout implementation.
     */
    public function isAutoResponder(): bool
    {
        $headers = is_array($this->headers) ? json_encode($this->headers) : $this->headers;
        $headers = $headers !== false ? $headers : null;

        return \App\Misc\MailHelper::isAutoResponder($headers);
    }

    /**
     * Check if thread is a bounce message.
     * Matches original FreeScout implementation.
     */
    public function isBounce(): bool
    {
        // Check send_status meta for bounce information
        $sendStatus = $this->meta['send_status'] ?? [];
        if (! is_array($sendStatus)) {
            $sendStatus = [];
        }

        return ! empty($sendStatus['is_bounce']);
    }

    /**
     * Get the user who created the thread.
     */
    public function getCreatedBy(): ?User
    {
        return $this->createdByUser;
    }

    /**
     * Get status name.
     */
    public function getStatusName(): string
    {
        return match ($this->status) {
            Conversation::STATUS_ACTIVE => __('Active'),
            Conversation::STATUS_PENDING => __('Pending'),
            Conversation::STATUS_CLOSED => __('Closed'),
            Conversation::STATUS_SPAM => __('Spam'),
            default => __('Unknown'),
        };
    }

    /**
     * Get action text.
     */
    public function getActionText(string $text = '', bool $html = true, bool $short = false, ?User $user = null, string $person_name = ''): string
    {
        return __('Action performed');
    }

    /**
     * Get assignee name.
     */
    public function getAssigneeName(bool $short = false, ?User $user = null): string
    {
        // Assuming the assigned user ID is stored in meta or source_type?
        // Or maybe it's the 'user_id' of the thread?
        // For 'assigned to' events, the thread usually links to the assigned user.
        // Let's assume 'user_id' points to the assigned user for this thread type.
        if ($this->user) {
            return $this->user->getFullName($short);
        }
        return __('Unknown');
    }
}
