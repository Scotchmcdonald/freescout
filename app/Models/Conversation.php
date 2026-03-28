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
 * @property \Illuminate\Support\Carbon|null $follow_up_date
 * @property \Illuminate\Support\Carbon|null $follow_up_reminded_at
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
    public const STATE_DELETED = 3;

    // Source via constants (who created)
    public const PERSON_CUSTOMER = 1;
    public const PERSON_USER = 2;

    public const SOURCE_VIA_USER = 2;

    public const SOURCE_VIA_CUSTOMER = 1;

    // Source type constants
    public const SOURCE_TYPE_WEB = 1;
    public const SOURCE_TYPE_EMAIL = 2;
    public const SOURCE_TYPE_API = 3;

    // Search modes
    public const SEARCH_MODE_CONV = 'conversations';
    public const SEARCH_MODE_CUSTOMERS = 'customers';

    // User assignment constant
    public const USER_UNASSIGNED = 'unassigned';

    // Viewer cache constants
    public const VIEWER_CACHE_KEY = 'conv_view';
    public const VIEWER_CACHE_TTL = 300; // 5 minutes
    public const VIEWER_STALE_TIMEOUT = 120; // 2 minutes

    /**
     * Search filters.
     *
     * @var array<int, string>
     */
    public static array $search_filters = [
        'assigned',
        'customer',
        'mailbox',
        'status',
        'state',
        'subject',
        'attachments',
        'type',
        'body',
        'number',
        'following',
        'id',
        'after',
        'before',
    ];

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
        'sender_email',
        'sender_name',
        'client_user_id',
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
        'follow_up_date',
        'follow_up_reminded_at',
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
            'follow_up_date' => 'datetime',
            'follow_up_reminded_at' => 'datetime',
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

        return $this->followers()->where('users.id', $user->id)->exists();
    }

    /**
     * Check if conversation has a follow-up reminder scheduled.
     */
    public function hasFollowUpScheduled(): bool
    {
        return $this->follow_up_date !== null;
    }

    /**
     * Check if the follow-up reminder is overdue.
     */
    public function isFollowUpOverdue(): bool
    {
        return $this->follow_up_date !== null
            && $this->follow_up_date->isPast()
            && $this->follow_up_reminded_at === null;
    }

    /**
     * Check if the follow-up reminder has been sent.
     */
    public function hasFollowUpBeenReminded(): bool
    {
        return $this->follow_up_reminded_at !== null;
    }

    /**
     * Get a human-readable follow-up status.
     */
    public function getFollowUpStatus(): ?string
    {
        if (! $this->hasFollowUpScheduled()) {
            return null;
        }

        if ($this->hasFollowUpBeenReminded()) {
            $remindedAt = $this->follow_up_reminded_at;
            if ($remindedAt) {
                return __('Reminded on :date', ['date' => $remindedAt->format('M j, Y')]);
            }
        }

        if ($this->isFollowUpOverdue()) {
            $followUpDate = $this->follow_up_date;
            if ($followUpDate) {
                return __('Overdue since :date', ['date' => $followUpDate->format('M j, Y')]);
            }
        }

        $followUpDate = $this->follow_up_date;
        if ($followUpDate) {
            return __('Scheduled for :date', ['date' => $followUpDate->format('M j, Y')]);
        }

        return null;
    }

    /**
     * Clear the follow-up reminder.
     */
    public function clearFollowUp(): void
    {
        $this->update([
            'follow_up_date' => null,
            'follow_up_reminded_at' => null,
        ]);
    }

    /**
     * Set a follow-up reminder for this conversation.
     */
    public function setFollowUp(\Illuminate\Support\Carbon|string|null $date = null): void
    {
        if ($date === null) {
            $defaultDays = config('app.default_follow_up_days', 3);
            if (is_int($defaultDays) || (is_string($defaultDays) && is_numeric($defaultDays))) {
                $date = now()->addDays((int) $defaultDays)->startOfDay();
            } else {
                $date = now()->addDays(3)->startOfDay();
            }
        }

        $this->update([
            'follow_up_date' => $date,
            'follow_up_reminded_at' => null,
        ]);
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
            ->where('type', Folder::TYPE_TRASH)
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

    /**
     * Star a conversation for a user.
     */
    public function star(User $user): void
    {
        if (! $this->starredByUsers()->wherePivot('user_id', $user->id)->exists()) {
            $this->starredByUsers()->attach($user->id);
        }
    }

    /**
     * Unstar a conversation for a user.
     */
    public function unstar(User $user): void
    {
        $this->starredByUsers()->detach($user->id);
    }

    /**
     * Check if starred by a user.
     */
    public function isStarredBy(User $user): bool
    {
        return $this->starredByUsers()->wherePivot('user_id', $user->id)->exists();
    }

    /**
     * Get starred by users relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<User, $this>
     */
    public function starredByUsers(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_user_stars', 'conversation_id', 'user_id');
    }

    /**
     * Change the customer for this conversation.
     */
    public function changeCustomer(?string $email, ?int $customerId, ?User $user): void
    {
        $oldCustomerId = $this->customer_id;

        if ($customerId) {
            /** @var \App\Models\Customer|null $customer */
            $customer = Customer::find($customerId);
            if ($customer) {
                $this->customer_id = $customerId;
                $mainEmail = $customer->getMainEmail();
                if ($mainEmail !== null) {
                    $this->customer_email = $mainEmail;
                }
            }
        } elseif ($email) {
            // Find or create customer by email
            $customer = Customer::create($email);
            if ($customer) {
                $this->customer_id = $customer->id;
                $this->customer_email = $email;
            }
        }

        $this->save();

        // Log activity
        if ($user && $oldCustomerId !== $this->customer_id) {
            ActivityLog::query()->create([
                'log_name' => ActivityLog::TYPE_CONVERSATION,
                'causer_type' => User::class,
                'causer_id' => $user->id,
                'subject_type' => self::class,
                'subject_id' => $this->id,
                'description' => 'Customer changed',
            ]);

            // Fire event
            \Eventy::action('conversation.customer_changed', $this, $user, $oldCustomerId);
        }
    }

    /**
     * Search conversations with filters.
     *
     * @param  string  $query  Search query
     * @param  array<string, mixed>  $filters  Search filters
     * @param  User|null  $user  User performing the search
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public static function search(string $query, array $filters = [], ?User $user = null): \Illuminate\Database\Eloquent\Builder
    {
        $builder = static::query()
            ->select('conversations.*');

        // Apply mailbox filter based on user access
        if ($user) {
            $mailboxIds = [];
            if (! empty($filters['mailbox'])) {
                // Verify user has access to the mailbox
                $mailboxFilter = is_numeric($filters['mailbox']) ? (int) $filters['mailbox'] : $filters['mailbox'];
                if ($user->isAdmin() || (is_int($mailboxFilter) && $user->mailboxes->contains($mailboxFilter))) {
                    $builder->where('conversations.mailbox_id', $mailboxFilter);
                } elseif (! $user->isAdmin()) {
                    $mailboxIds = $user->mailboxes->pluck('id')->toArray();
                    if (! empty($mailboxIds)) {
                        $builder->whereIn('conversations.mailbox_id', $mailboxIds);
                    } else {
                        $builder->whereRaw('1 = 0');
                    }
                }
            } elseif (! $user->isAdmin()) {
                $mailboxIds = $user->mailboxes->pluck('id')->toArray();
                if (! empty($mailboxIds)) {
                    $builder->whereIn('conversations.mailbox_id', $mailboxIds);
                } else {
                    $builder->whereRaw('1 = 0');
                }
            }
        }

        // Apply search query
        if ($query) {
            $escapedQuery = addcslashes($query, '%_');
            $like = '%'.mb_strtolower($escapedQuery).'%';
            $queryInt = min((int) $query, PHP_INT_MAX);

            $builder->leftJoin('customers', 'conversations.customer_id', '=', 'customers.id')
                ->leftJoin('threads', 'conversations.id', '=', 'threads.conversation_id')
                ->where(function ($q) use ($like, $queryInt) {
                    $q->where('conversations.subject', 'like', $like)
                        ->orWhere('conversations.customer_email', 'like', $like)
                        ->orWhere('conversations.number', $queryInt)
                        ->orWhere('conversations.id', $queryInt)
                        ->orWhere('customers.first_name', 'like', $like)
                        ->orWhere('customers.last_name', 'like', $like)
                        ->orWhere('customers.phones', 'like', $like)
                        ->orWhere('threads.body', 'like', $like);
                })
                ->groupBy('conversations.id');
        }

        // Apply search filters
        if (! empty($filters['assigned'])) {
            if ($filters['assigned'] === self::USER_UNASSIGNED) {
                $builder->whereNull('conversations.user_id');
            } else {
                $builder->where('conversations.user_id', $filters['assigned']);
            }
        }

        if (! empty($filters['customer'])) {
            $builder->where('conversations.customer_id', $filters['customer']);
        }

        if (! empty($filters['status'])) {
            $statuses = is_array($filters['status']) ? $filters['status'] : [$filters['status']];
            $builder->whereIn('conversations.status', $statuses);
        }

        if (! empty($filters['state'])) {
            $states = is_array($filters['state']) ? $filters['state'] : [$filters['state']];
            $builder->whereIn('conversations.state', $states);
        }

        if (! empty($filters['type'])) {
            $builder->where('conversations.type', $filters['type']);
        }

        if (! empty($filters['attachments'])) {
            $builder->where('conversations.has_attachments', true);
        }

        if (! empty($filters['after'])) {
            $builder->where('conversations.created_at', '>=', $filters['after']);
        }

        if (! empty($filters['before'])) {
            $builder->where('conversations.created_at', '<=', $filters['before']);
        }

        // Allow modules to modify search query
        \Eventy::filter('search.conversations.query', $builder, $query, $filters, $user);

        return $builder->orderBy('conversations.created_at', 'desc');
    }

    /**
     * Get status label.
     */
    public function getStatusLabel(): string
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
     * Get type label.
     */
    public function getTypeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_EMAIL => __('Email'),
            self::TYPE_PHONE => __('Phone'),
            self::TYPE_CHAT => __('Chat'),
            default => __('Unknown'),
        };
    }

    /**
     * Get real-time viewers info for conversations.
     * Shows who is currently viewing or replying to conversations.
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, Conversation>|array<int, Conversation>  $conversations
     * @param  array<int, string>  $fields
     * @param  array<int, int>  $excludeUserIds
     * @return array<int, array{user: User|null, user_id: int, replying: bool}>
     */
    public static function getViewersInfo(\Illuminate\Database\Eloquent\Collection|array $conversations, array $fields = ['id', 'first_name', 'last_name'], array $excludeUserIds = []): array
    {
        /** @var array<int, array<int, array{r: bool, t: int}>> $viewersCache */
        $viewersCache = cache()->get(self::VIEWER_CACHE_KEY, []);

        $viewers = [];
        $userIds = [];

        foreach ($conversations as $conversation) {
            $firstUserId = null;

            if (! empty($viewersCache[$conversation->id])) {
                // Get replying viewers first (higher priority)
                foreach ($viewersCache[$conversation->id] as $userId => $viewer) {
                    if (! $firstUserId) {
                        $firstUserId = $userId;
                    }

                    if ($viewer['r'] && ! in_array($userId, $excludeUserIds)) {
                        $viewers[$conversation->id] = [
                            'user' => null,
                            'user_id' => $userId,
                            'replying' => true,
                        ];
                        $userIds[] = $userId;
                        break;
                    }
                }

                // Get first non-replying viewer if no replying viewer found
                if (empty($viewers[$conversation->id]) && $firstUserId && ! in_array($firstUserId, $excludeUserIds)) {
                    $viewers[$conversation->id] = [
                        'user' => null,
                        'user_id' => $firstUserId,
                        'replying' => false,
                    ];
                    $userIds[] = $firstUserId;
                }
            }
        }

        // Get all viewing users in one query
        if ($userIds) {
            $userIds = array_unique($userIds);
            $users = User::select($fields)->whereIn('id', $userIds)->get();

            foreach ($viewers as $convId => $viewer) {
                foreach ($users as $user) {
                    if ($user->id === $viewer['user_id']) {
                        $viewers[$convId]['user'] = $user;
                    }
                }
            }
        }

        return $viewers;
    }

    /**
     * Set conversation as being viewed by a user.
     */
    public static function setViewer(int $conversationId, int $userId, bool $replying = false): void
    {
        /** @var array<int, array<int, array{r: bool, t: int}>> $viewersCache */
        $viewersCache = cache()->get(self::VIEWER_CACHE_KEY, []);

        $viewersCache[$conversationId][$userId] = [
            'r' => $replying,
            't' => time(),
        ];

        cache()->put(self::VIEWER_CACHE_KEY, $viewersCache, self::VIEWER_CACHE_TTL);
    }

    /**
     * Remove viewer from conversation.
     */
    public static function removeViewer(int $conversationId, int $userId): void
    {
        /** @var array<int, array<int, array{r: bool, t: int}>> $viewersCache */
        $viewersCache = cache()->get(self::VIEWER_CACHE_KEY, []);

        if (isset($viewersCache[$conversationId][$userId])) {
            unset($viewersCache[$conversationId][$userId]);

            if (empty($viewersCache[$conversationId])) {
                unset($viewersCache[$conversationId]);
            }

            cache()->put(self::VIEWER_CACHE_KEY, $viewersCache, self::VIEWER_CACHE_TTL);
        }
    }

    /**
     * Clean up stale viewers (older than configured timeout).
     */
    public static function cleanupViewers(): void
    {
        /** @var array<int, array<int, array{r: bool, t: int}>> $viewersCache */
        $viewersCache = cache()->get(self::VIEWER_CACHE_KEY, []);

        $staleTime = time() - self::VIEWER_STALE_TIMEOUT;

        foreach ($viewersCache as $convId => $viewers) {
            foreach ($viewers as $userId => $data) {
                if ($data['t'] < $staleTime) {
                    unset($viewersCache[$convId][$userId]);
                }
            }

            if (empty($viewersCache[$convId])) {
                unset($viewersCache[$convId]);
            }
        }

        if (! empty($viewersCache)) {
            cache()->put(self::VIEWER_CACHE_KEY, $viewersCache, self::VIEWER_CACHE_TTL);
        } else {
            cache()->forget(self::VIEWER_CACHE_KEY);
        }
    }
}
