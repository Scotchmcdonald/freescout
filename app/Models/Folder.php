<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Folder extends Model
{
    use HasFactory;

    // Folder types
    public const TYPE_INBOX = 1;
    public const TYPE_SENT = 2;
    public const TYPE_DRAFTS = 3;
    public const TYPE_SPAM = 4;
    public const TYPE_TRASH = 5;
    public const TYPE_ASSIGNED = 20;
    public const TYPE_MINE = 25;
    public const TYPE_STARRED = 30;

    protected $fillable = [
        'mailbox_id',
        'user_id',
        'type',
        'name',
        'total_count',
        'active_count',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'type' => 'integer',
            'total_count' => 'integer',
            'active_count' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the mailbox that owns the folder.
     * 
     * @return BelongsTo<Mailbox, $this>
     */
    public function mailbox(): BelongsTo
    {
        return $this->belongsTo(Mailbox::class);
    }

    /**
     * Get the user that owns the folder.
     * 
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the conversations in this folder.
     * 
     * @return HasMany<Conversation, $this>
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    /**
     * Get the conversations through the pivot table.
     * 
     * @return BelongsToMany<Conversation, $this>
     */
    public function conversationsViaFolder(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class, 'conversation_folder')
            ->withTimestamps();
    }

    /**
     * Check if this is an inbox folder.
     */
    public function isInbox(): bool
    {
        return $this->type === self::TYPE_INBOX;
    }

    /**
     * Check if this is a sent folder.
     */
    public function isSent(): bool
    {
        return $this->type === self::TYPE_SENT;
    }

    /**
     * Check if this is a drafts folder.
     */
    public function isDrafts(): bool
    {
        return $this->type === self::TYPE_DRAFTS;
    }

    /**
     * Check if this is a spam folder.
     */
    public function isSpam(): bool
    {
        return $this->type === self::TYPE_SPAM;
    }

    /**
     * Check if this is a trash folder.
     */
    public function isTrash(): bool
    {
        return $this->type === self::TYPE_TRASH;
    }
}
