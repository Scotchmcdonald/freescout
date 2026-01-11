<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
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
 * @property string|null $theme
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

    /**
     * Determine if the user has verified their email address.
     * The initial deployment admin (matching ADMIN_EMAIL) bypasses verification.
     */
    public function hasVerifiedEmail(): bool
    {
        // Allow the deployment admin to bypass verification
        $deploymentAdminEmail = config('app.admin_email');
        if ($deploymentAdminEmail && $this->email === $deploymentAdminEmail && $this->role === self::ROLE_ADMIN) {
            return true;
        }

        return ! is_null($this->email_verified_at);
    }

    // Role constants
    public const ROLE_USER = 1;

    public const ROLE_ADMIN = 2;

    public const ROLE_REPORTER = 3;

    // Status constants
    public const STATUS_ACTIVE = 1;

    public const STATUS_INACTIVE = 2;

    public const STATUS_DELETED = 3;

    // Invite state constants
    public const INVITE_STATE_ACTIVATED = 1;

    public const INVITE_STATE_SENT = 2;

    public const INVITE_STATE_NOT_INVITED = 3;

    /**
     * Global user permissions.
     */
    public const PERM_DELETE_CONVERSATIONS = 1;
    public const PERM_EDIT_CONVERSATIONS = 2;
    public const PERM_EDIT_SAVED_REPLIES = 3;
    public const PERM_EDIT_TAGS = 4;
    public const PERM_EDIT_CUSTOM_FOLDERS = 5;
    public const PERM_EDIT_USERS = 10;

    /**
     * The companies that the user belongs to.
     */
    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(\Modules\Billing\Models\Company::class, 'company_user')
            ->withPivot('role_id', 'status')
            ->withTimestamps();
    }

    /**
     * Check if the user has access to the given company.
     *
     * @param int|string|\Modules\Billing\Models\Company $company
     */
    public function hasCompanyAccess($company): bool
    {
        // MSP Admin has access to all companies (Global Role)
        if ($this->role === self::ROLE_ADMIN) {
            return true;
        }

        $companyId = $company instanceof \Modules\Billing\Models\Company ? $company->id : $company;

        return $this->companies()
            ->where('company_id', $companyId)
            ->wherePivot('status', 'approved')
            ->exists();
    }

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
        'theme',
        'dark_mode',
        'job_title',
        'phone',
        'emails',
        'time_format',
        'enable_kb_shortcuts',
        'locked',
        'google_id',
        'avatar',
        'permissions', // Added missing fillable
        'email_verified_at',
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
            'permissions' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'dark_mode' => 'boolean',
        ];
    }

    /**
     * Get the mailboxes that the user has access to.
     *
     * @return BelongsToMany<Mailbox, $this, MailboxUser>
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
     * Get the saved searches for this user.
     *
     * @return HasMany<SavedSearch, $this>
     */
    public function savedSearches(): HasMany
    {
        return $this->hasMany(SavedSearch::class);
    }

    /**
     * Get the user's full name.
     * 
     * @return Attribute<string, never>
     */
    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn() => trim("{$this->first_name} {$this->last_name}")
        );
    }

    /**
     * Check if user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * Check if user is a reporter.
     */
    public function isReporter(): bool
    {
        return $this->role === self::ROLE_REPORTER;
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
    public function getFullName(bool $short = false): string
    {
        if ($short) {
            return $this->first_name ?? explode('@', $this->email)[0];
        }
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
     * 
     * @return Attribute<string, never>
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->getFullName()
        );
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

        $mailbox = $this->mailboxes()->find($mailboxId);
        
        if ($mailbox === null) {
            return false;
        }

        /** @var MailboxUser $pivot */
        $pivot = $mailbox->pivot;

        return $pivot->access >= $minLevel;
    }

    /**
     * Get URL for user setup/invitation.
     */
    public function urlSetup(): string
    {
        return route('user_setup', ['hash' => $this->invite_hash]);
    }

    /**
     * Format date according to user's timezone.
     *
     * @param  \DateTime|string|null  $date
     * @param  string  $format
     * @param  User|null  $user
     * @return string
     */
    public static function dateFormat($date, $format = 'M j, Y', $user = null): string
    {
        if (! $date) {
            return '';
        }

        if (! $date instanceof \DateTimeInterface) {
            try {
                $date = \Carbon\Carbon::parse($date);
            } catch (\Exception $e) {
                return '';
            }
        }

        if ($user && $user->timezone) {
            try {
                $date->setTimezone(new \DateTimeZone($user->timezone));
            } catch (\Exception $e) {
                // Invalid timezone, ignore
            }
        }

        return $date->format($format);
    }

    /**
     * Send the password changed notification.
     */
    public function sendPasswordChanged(): void
    {
        \Illuminate\Support\Facades\Mail::to($this)->send(new \App\Mail\PasswordChanged($this));
    }

    /**
     * Get mailboxes user can view.
     *
     * @param bool $checkPermission
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Mailbox>
     */
    public function mailboxesCanView(bool $checkPermission = false)
    {
        if ($this->isAdmin()) {
            return Mailbox::all();
        }

        return $this->mailboxes;
    }

    /**
     * Check if user has permission.
     *
     * @param int|string $permission
     * @param bool $checkOwnPermissions
     * @return bool
     */
    public function hasPermission($permission, bool $checkOwnPermissions = true): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        // New RBAC String Permissions
        if (is_string($permission)) {
            // Get all role IDs for this user across all approved companies
            $roleIds = $this->companies()
                ->wherePivot('status', 'approved')
                ->pluck('company_user.role_id')
                ->filter()
                ->unique();
            
            if ($roleIds->isEmpty()) {
                return false;
            }

            return \App\Models\Permission::where('name', $permission)
                ->whereHas('roles', function ($query) use ($roleIds) {
                    $query->whereIn('roles.id', $roleIds);
                })->exists();
        }

        // Legacy Integer Permissions
        $hasPermission = false;

        $globalPermissions = self::getGlobalUserPermissions();

        if (!empty($globalPermissions) && in_array($permission, $globalPermissions)) {
            $hasPermission = true;
        }

        if ($checkOwnPermissions && !empty($this->permissions)) {
            if (isset($this->permissions[$permission])) {
                $hasPermission = (bool)$this->permissions[$permission];
            }
        }

        return $hasPermission;
    }

    /**
     * Get global user permissions.
     * 
     * @return array<int>
     */
    public static function getGlobalUserPermissions(): array
    {
        $permissions = [];
        $permissionsConfig = config('app.user_permissions');

        if ($permissionsConfig && is_string($permissionsConfig)) {
            $permissionsJson = base64_decode($permissionsConfig);
            $decoded = json_decode($permissionsJson, true);
            if (is_array($decoded)) {
                $permissions = array_filter($decoded, 'is_int');
            }
        }

        return $permissions;
    }

    /**
     * Scope to exclude deleted users.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<User>  $query
     * @return \Illuminate\Database\Eloquent\Builder<User>
     */
    public function scopeNonDeleted($query)
    {
        return $query->where('status', '!=', self::STATUS_DELETED);
    }

    /**
     * Follow a conversation.
     */
    public function followConversation(Conversation $conversation): void
    {
        if (! $this->followedConversations()->wherePivot('conversation_id', $conversation->id)->exists()) {
            $this->followedConversations()->attach($conversation->id);
        }
    }

    /**
     * Unfollow a conversation.
     */
    public function unfollowConversation(Conversation $conversation): void
    {
        $this->followedConversations()->detach($conversation->id);
    }

    /**
     * Check if user is following a conversation.
     */
    public function isFollowingConversation(Conversation $conversation): bool
    {
        return $this->followedConversations()->wherePivot('conversation_id', $conversation->id)->exists();
    }

    /**
     * Generate a random password.
     */
    public static function generateRandomPassword(int $length = 16): string
    {
        return \Illuminate\Support\Str::random($length);
    }

    /**
     * Send user invitation email.
     *
     * @throws \Exception
     */
    public function sendInvite(bool $throwException = false): bool
    {
        try {
            \Illuminate\Support\Facades\Mail::to($this)->send(new \App\Mail\UserInvite($this));
            $this->update(['invite_state' => self::INVITE_STATE_SENT]);

            return true;
        } catch (\Exception $e) {
            if ($throwException) {
                throw $e;
            }

            return false;
        }
    }

    /**
     * Check if user is deleted.
     */
    public function isDeleted(): bool
    {
        return $this->status === self::STATUS_DELETED;
    }

    /**
     * Can manage mailbox.
     */
    public function canManageMailbox(int $mailboxId): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->hasAccessToMailbox($mailboxId, MailboxUser::ACCESS_ADMIN);
    }
}
