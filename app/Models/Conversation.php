<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    use HasFactory;

    // Status constants
    public const STATUS_ACTIVE = 1;

    public const STATUS_PENDING = 2;

    public const STATUS_CLOSED = 3;

    public const STATUS_SPAM = 4;

    protected $fillable = [
        'number',
        'threads_count',
        'type',
        'folder_id',
        'mailbox_id',
        'user_id',
        'customer_id',
        'status',
        'state',
        'subject',
        'customer_email',
        'cc',
        'bcc',
        'preview',
        'imported',
        'has_attachments',
        'created_by_user_id',
        'created_by_customer_id',
        'source_via',
        'source_type',
        'channel',
        'closed_by_user_id',
        'closed_at',
        'user_updated_at',
        'last_reply_at',
        'last_reply_from',
        'read_by_user',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'threads_count' => 'integer',
            'type' => 'integer',
            'status' => 'integer',
            'state' => 'integer',
            'cc' => 'json',
            'bcc' => 'json',
            'imported' => 'boolean',
            'has_attachments' => 'boolean',
            'source_via' => 'integer',
            'source_type' => 'integer',
            'channel' => 'integer',
            'last_reply_from' => 'integer',
            'read_by_user' => 'boolean',
            'closed_at' => 'datetime',
            'user_updated_at' => 'datetime',
            'last_reply_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the folder that owns the conversation.
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    /**
     * Get the mailbox that owns the conversation.
     */
    public function mailbox(): BelongsTo
    {
        return $this->belongsTo(Mailbox::class);
    }

    /**
     * Get the user assigned to the conversation.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the customer associated with the conversation.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the user who created the conversation.
     */
    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Get the user who closed the conversation.
     */
    public function closedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by_user_id');
    }

    /**
     * Get the threads for the conversation.
     */
    public function threads(): HasMany
    {
        return $this->hasMany(Thread::class)->orderBy('created_at');
    }

    /**
     * Get the users following this conversation.
     */
    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'followers')
            ->withTimestamps();
    }

    /**
     * Get the folders this conversation belongs to.
     */
    public function folders(): BelongsToMany
    {
        return $this->belongsToMany(Folder::class, 'conversation_folder')
            ->withTimestamps();
    }

    /**
     * Check if conversation is active.
     */
    public function isActive(): bool
    {
        return $this->status === 1;
    }

    /**
     * Check if conversation is closed.
     */
    public function isClosed(): bool
    {
        return $this->status === 3;
    }

    /**
     * Update the conversation's folder based on status and assignee.
     */
    public function updateFolder(): void
    {
        // Determine the appropriate folder based on status and user assignment
        $folderType = match ($this->status) {
            self::STATUS_ACTIVE => $this->user_id ? 1 : 2, // Assigned or Unassigned
            self::STATUS_PENDING => 2, // Unassigned
            self::STATUS_CLOSED => 4, // Deleted/Closed
            self::STATUS_SPAM => 30, // Spam
            default => 2, // Unassigned as fallback
        };

        // Find the appropriate folder
        $folder = Folder::where('mailbox_id', $this->mailbox_id)
            ->where('type', $folderType)
            ->when($folderType === 1 && $this->user_id, function ($query) {
                return $query->where('user_id', $this->user_id);
            })
            ->first();

        if ($folder) {
            $this->folder_id = $folder->id;
            $this->save();
        }
    }
}
