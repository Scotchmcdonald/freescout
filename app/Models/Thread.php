<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
 * @property string|null $to
 * @property array|null $cc
 * @property array|null $bcc
 * @property string|null $from
 * @property array|null $headers
 * @property string|null $message_id
 * @property \Illuminate\Support\Carbon|null $opened_at
 * @property array|null $meta
 * @property bool $first
 * @property bool $has_attachments
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * 
 * @property-read \App\Models\Conversation $conversation
 * @property-read \App\Models\User|null $user
 * @property-read \App\Models\Customer|null $customer
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Attachment> $attachments
 */
class Thread extends Model
{
    use HasFactory;

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
    ];

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
        ];
    }

    /**
     * Get the conversation that owns the thread.
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Get the user that created the thread.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Get the customer associated with the thread.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'created_by_customer_id');
    }

    /**
     * Get the user who created the thread (for email replies from users).
     */
    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Get the user who edited the thread.
     */
    public function editedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by_user_id');
    }

    /**
     * Get the attachments for the thread.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    /**
     * Check if this is a message from customer.
     */
    public function isCustomerMessage(): bool
    {
        return $this->type === 4;
    }

    /**
     * Check if this is a message from user.
     */
    public function isUserMessage(): bool
    {
        return $this->type === 1;
    }

    /**
     * Check if this is a note.
     */
    public function isNote(): bool
    {
        return $this->type === 2;
    }

    /**
     * Check if thread is from an auto-responder.
     * Matches original FreeScout implementation.
     */
    public function isAutoResponder(): bool
    {
        return \App\Misc\MailHelper::isAutoResponder($this->headers);
    }

    /**
     * Check if thread is a bounce message.
     * Matches original FreeScout implementation.
     */
    public function isBounce(): bool
    {
        // Check send_status meta for bounce information
        $sendStatus = $this->meta['send_status'] ?? [];

        return ! empty($sendStatus['is_bounce']);
    }
}
