<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string $password
 * @property int $role
 * @property int $status
 * @property string|null $remember_token
 * @property string|null $timezone
 * @property string|null $photo_url
 * @property int|null $type
 * @property int|null $invite_state
 * @property string|null $locale
 * @property string|null $job_title
 * @property string|null $phone
 * @property int|null $time_format
 * @property bool $enable_kb_shortcuts
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Mailbox> $mailboxes
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Folder> $folders
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Conversation> $conversations
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Thread> $threads
 *
 * @method static \Illuminate\Database\Eloquent\Builder<User>|User create(array<string, mixed> $attributes = [])
 *
 * @mixin \Illuminate\Database\Eloquent\Builder<User>
 */
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use Notifiable;

    // Role constants
    public const ROLE_USER = 1;

    public const ROLE_ADMIN = 2;

    // Status constants
    public const STATUS_ACTIVE = 1;

    public const STATUS_INACTIVE = 2;

    public const STATUS_DELETED = 3;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'role',
        'timezone',
        'photo_url',
        'type',
        'status',
        'invite_state',
        'invite_hash',
        'locale',
        'job_title',
        'phone',
        'time_format',
        'enable_kb_shortcuts',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
        'invite_hash',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => 'integer',
            'type' => 'integer',
            'status' => 'integer',
            'invite_state' => 'integer',
            'enable_kb_shortcuts' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the mailboxes that the user has access to.
     *
     * @return BelongsToMany<Mailbox, $this>
     */
    public function mailboxes(): BelongsToMany
    {
        return $this->belongsToMany(Mailbox::class, 'mailbox_user')
            ->using(MailboxUser::class)
            ->withPivot('access', 'after_send')
            ->withTimestamps();
    }

    /**
     * Get the folders created by this user.
     *
     * @return HasMany<Folder, $this>
     */
    public function folders(): HasMany
    {
        return $this->hasMany(Folder::class);
    }

    /**
     * Get the conversations assigned to this user.
     *
     * @return HasMany<Conversation, $this>
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    /**
     * Get the conversations this user is following.
     *
     * @return BelongsToMany<Conversation, $this>
     */
    public function followedConversations(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class, 'followers')
            ->withTimestamps();
    }

    /**
     * Get the threads created by this user.
     *
     * @return HasMany<Thread, $this>
     */
    public function threads(): HasMany
    {
        return $this->hasMany(Thread::class);
    }

    /**
     * Get the subscriptions for this user.
     *
     * @return HasMany<Subscription, $this>
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Get the user's full name.
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Check if user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * Check if user is active.
     */
    public function isActive(): bool
    {
        return $this->status === 1; // Active status
    }

    /**
     * Get user's full name (first + last) or email if name not available.
     */
    public function getFullName(): string
    {
        $fullName = trim(($this->first_name ?? '').' '.($this->last_name ?? ''));

        return $fullName !== '' ? $fullName : $this->email;
    }

    /**
     * Get user's first name.
     */
    public function getFirstName(): string
    {
        return $this->first_name ?? '';
    }

    /**
     * Get the name attribute (accessor for compatibility).
     */
    public function getNameAttribute(): string
    {
        return $this->getFullName();
    }

    /**
     * Get user's photo URL (Gravatar).
     */
    public function getPhotoUrl(): string
    {
        $hash = md5(strtolower(trim($this->email)));

        return "https://www.gravatar.com/avatar/{$hash}?d=mp&f=y";
    }

    /**
     * Check if user has access to a mailbox at minimum level.
     */
    public function hasAccessToMailbox(int $mailboxId, int $minLevel = MailboxUser::ACCESS_VIEW): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $pivot = $this->mailboxes()->where('mailbox_id', $mailboxId)->first()?->pivot;

        return $pivot && $pivot->access >= $minLevel;
    }
}
