<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
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
        return $this->belongsToMany(Mailbox::class)
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
        $fullName = trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));

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
}
