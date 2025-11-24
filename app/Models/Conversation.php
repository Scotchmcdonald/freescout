<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $number
 * @property int $threads_count
 * @property int $type
 * @property int $folder_id
 * @property int $mailbox_id
 * @property int|null $user_id
 * @property int|null $customer_id
 * @property int $status
 * @property int $state
 * @property string $subject
 * @property string $customer_email
 * @property array<int, string>|null $cc
 * @property array<int, string>|null $bcc
 * @property string|null $preview
 * @property bool $imported
 * @property bool $has_attachments
 * @property int|null $created_by_user_id
 * @property int|null $created_by_customer_id
 * @property int|null $source_via
 * @property int|null $source_type
 * @property int|null $channel
 * @property int|null $closed_by_user_id
 * @property \Illuminate\Support\Carbon|null $closed_at
 * @property \Illuminate\Support\Carbon|null $user_updated_at
 * @property \Illuminate\Support\Carbon|null $last_reply
 * @property \Illuminate\Support\Carbon|null $last_reply_at
 * @property int|null $last_reply_from
 * @property bool $read_by_user
 * @property array<string, mixed>|null $meta
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Mailbox|null $mailbox
 * @property-read \App\Models\Customer|null $customer
 * @property-read \App\Models\User|null $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Thread> $threads
 *
 * @method static \Illuminate\Database\Eloquent\Builder<Conversation>|Conversation create(array<string, mixed> $attributes = [])
 *
 * @mixin \Illuminate\Database\Eloquent\Builder<Conversation>
 */
class Conversation extends Model
{
    /** @use HasFactory<\Database\Factories\ConversationFactory> */
    use HasFactory;
    use SoftDeletes;

    // Type constants
    public const TYPE_EMAIL = 1;
    public const TYPE_PHONE = 2;
    public const TYPE_CHAT = 3;

    // Status constants
    public const STATUS_ACTIVE = 1;

    public const STATUS_PENDING = 2;

    public const STATUS_CLOSED = 3;

    public const STATUS_SPAM = 4;

    // State constants
    public const STATE_DRAFT = 1;
    public const STATE_PUBLISHED = 2;

    // Source via constants (who created)
    public const PERSON_CUSTOMER = 1;
    public const PERSON_USER = 2;

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

    /**
     * @return array<string, string>
     */
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
            'meta' => 'json',
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
     *
     * @return BelongsTo<Folder, $this>
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    /**
     * Get the mailbox that owns the conversation.
     *
     * @return BelongsTo<Mailbox, $this>
     */
    public function mailbox(): BelongsTo
    {
        return $this->belongsTo(Mailbox::class);
    }

    /**
     * Get the user assigned to the conversation.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the customer associated with the conversation.
     *
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the user who created the conversation.
     *
     * @return BelongsTo<User, $this>
     */
    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Get the user who closed the conversation.
     *
     * @return BelongsTo<User, $this>
     */
    public function closedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by_user_id');
    }

    /**
     * Get the threads for the conversation.
     *
     * @return HasMany<Thread, $this>
     */
    public function threads(): HasMany
    {
        return $this->hasMany(Thread::class)->orderBy('created_at');
    }

    /**
     * Get the users following this conversation.
     *
     * @return BelongsToMany<User, $this>
     */
    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'followers')
            ->withTimestamps();
    }

    /**
     * Get the folders this conversation belongs to.
     *
     * @return BelongsToMany<Folder, $this>
     */
    public function folders(): BelongsToMany
    {
        return $this->belongsToMany(Folder::class, 'conversation_folder')
            ->withTimestamps();
    }

    /**
     * Get aliases as an array.
     *
     * @return array<string>
     */
    public function getCcArray(): array
    {
        return $this->cc ?? [];
    }

    /**
     * Get conversation URL.
     */
    public function url(): string
    {
        return route('conversations.show', ['conversation' => $this->id]);
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
     * Check if conversation is phone type.
     */
    public function isPhone(): bool
    {
        return $this->type === self::TYPE_PHONE;
    }

    /**
     * Check if conversation is chat type.
     */
    public function isChat(): bool
    {
        return $this->type === self::TYPE_CHAT;
    }

    /**
     * Get status name.
     */
    public function getStatusName(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => __('Active'),
            self::STATUS_PENDING => __('Pending'),
            self::STATUS_CLOSED => __('Closed'),
            self::STATUS_SPAM => __('Spam'),
            default => __('Unknown'),
        };
    }

    /**
     * Get status color.
     */
    public function getStatusColor(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => '#3f8abf', // Blue
            self::STATUS_PENDING => '#e6b216', // Yellow/Orange
            self::STATUS_CLOSED => '#5cb85c', // Green
            self::STATUS_SPAM => '#d9534f', // Red
            default => '#777777', // Grey
        };
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
            self::STATUS_CLOSED => 1, // Keep in Inbox/Assigned (or handle differently)
            self::STATUS_SPAM => 4, // Spam
            default => 2, // Unassigned as fallback
        };

        // Find the appropriate folder
        $folder = Folder::where('mailbox_id', $this->mailbox_id)
            ->where('type', $folderType)
            ->when($folderType === 1 && $this->user_id, function ($query) {
                return $query->where('user_id', $this->user_id);
            })
            ->first();

        if ($folder && $this->folder_id !== $folder->id) {
            $this->folder_id = $folder->id;
            $this->save();
        }
    }

    /**
     * Check if user is following this conversation.
     */
    public function isUserFollowing(?User $user = null): bool
    {
        if (! $user) {
            $user = auth()->user();
        }

        if (! $user) {
            return false;
        }

        return $this->followers()->where('user_id', $user->id)->exists();
    }

    /**
     * Change conversation user (assignee) with logging.
     */
    public function changeUser(?int $userId, ?User $byUser = null): bool
    {
        $oldUserId = $this->user_id;
        $this->user_id = $userId;
        $this->user_updated_at = now();
        $saved = $this->save();

        if ($saved && $oldUserId !== $userId) {
            // Log the change
            activity()
                ->causedBy($byUser)
                ->performedOn($this)
                ->withProperties([
                    'old_user_id' => $oldUserId,
                    'new_user_id' => $userId,
                ])
                ->log('conversation_user_changed');
        }

        return $saved;
    }

    /**
     * Change conversation status with logging.
     */
    public function changeStatus(int $status, ?User $byUser = null): bool
    {
        $oldStatus = $this->status;
        $this->status = $status;

        // Update closed_at and closed_by if closing
        if ($status === self::STATUS_CLOSED && $oldStatus !== self::STATUS_CLOSED) {
            $this->closed_at = now();
            $this->closed_by_user_id = $byUser?->id;
        }

        $saved = $this->save();

        if ($saved && $oldStatus !== $status) {
            // Log the change
            activity()
                ->causedBy($byUser)
                ->performedOn($this)
                ->withProperties([
                    'old_status' => $oldStatus,
                    'new_status' => $status,
                ])
                ->log('conversation_status_changed');

            // Update folder based on new status
            $this->updateFolder();
        }

        return $saved;
    }

    /**
     * Delete conversation to Deleted folder (soft delete).
     */
    public function deleteToFolder(?User $byUser = null): bool
    {
        // Find or create Deleted folder
        $deletedFolder = Folder::where('mailbox_id', $this->mailbox_id)
            ->where('type', Folder::TYPE_DELETED)
            ->first();

        if (! $deletedFolder) {
            return false;
        }

        $this->state = 3; // Deleted state
        $this->folder_id = $deletedFolder->id;

        $saved = $this->save();

        if ($saved) {
            activity()
                ->causedBy($byUser)
                ->performedOn($this)
                ->log('conversation_deleted');
        }

        return $saved;
    }

    /**
     * Restore conversation from Deleted folder.
     */
    public function restoreFromDeleted(?User $byUser = null): bool
    {
        // Find Inbox folder
        $inboxFolder = Folder::where('mailbox_id', $this->mailbox_id)
            ->where('type', Folder::TYPE_INBOX)
            ->first();

        if (! $inboxFolder) {
            return false;
        }

        $this->state = self::STATE_PUBLISHED;
        $this->folder_id = $inboxFolder->id;

        $saved = $this->save();

        if ($saved) {
            activity()
                ->causedBy($byUser)
                ->performedOn($this)
                ->log('conversation_restored');

            // Update to proper folder
            $this->updateFolder();
        }

        return $saved;
    }

    /**
     * Move conversation to different mailbox.
     */
    public function moveToMailbox(int $mailboxId, ?User $byUser = null): bool
    {
        $oldMailboxId = $this->mailbox_id;

        // Find inbox folder in new mailbox
        $inboxFolder = Folder::where('mailbox_id', $mailboxId)
            ->where('type', Folder::TYPE_INBOX)
            ->first();

        if (! $inboxFolder) {
            return false;
        }

        $this->mailbox_id = $mailboxId;
        $this->folder_id = $inboxFolder->id;

        $saved = $this->save();

        if ($saved) {
            activity()
                ->causedBy($byUser)
                ->performedOn($this)
                ->withProperties([
                    'old_mailbox_id' => $oldMailboxId,
                    'new_mailbox_id' => $mailboxId,
                ])
                ->log('conversation_moved');
        }

        return $saved;
    }

    /**
     * Get BCC array.
     *
     * @return array<string>
     */
    public function getBccArray(): array
    {
        return $this->bcc ?? [];
    }

    /**
     * Sanitize email array.
     *
     * @param  array<string>  $emails
     * @return array<string>
     */
    public static function sanitizeEmails(array $emails): array
    {
        $result = [];
        foreach ($emails as $email) {
            $trimmed = trim($email);
            if (filter_var($trimmed, FILTER_VALIDATE_EMAIL)) {
                $result[] = $trimmed;
            }
        }

        return $result;
    }
}
