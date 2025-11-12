<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $number
 * @property int $threads_count
 * @property int $type
 * @property int $folder_id
 * @property int $mailbox_id
 * @property int|null $user_id
 * @property int $customer_id
 * @property int $status
 * @property int $state
 * @property string $subject
 * @property string $customer_email
 * @property array|null $cc
 * @property array|null $bcc
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
 * @property array|null $read_by_user
 * @property array|null $meta
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Mailbox $mailbox
 * @property-read \App\Models\Customer $customer
 * @property-read \App\Models\User|null $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Thread> $threads
 *
 * @method static \Illuminate\Database\Eloquent\Builder<Conversation>|Conversation create(array<string, mixed> $attributes = [])
 *
 * @mixin \Illuminate\Database\Eloquent\Builder<Conversation>
 */
class Conversation extends Model
{
    use HasFactory;

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
