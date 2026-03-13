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
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Role> $roles
 *
 * @method static \Illuminate\Database\Eloquent\Builder<User>|User create(array<string, mixed> $attributes = [])
 * @method \Illuminate\Database\Eloquent\Relations\HasMany<\Modules\Alerts\Models\NotificationSubscription, $this> notificationSubscriptions()
 *
 * @mixin \Illuminate\Database\Eloquent\Builder<User>
 */
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use \Lab404\Impersonate\Models\Impersonate;
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

    public const ROLE_FINANCE = 4;

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
     *
     * @deprecated These legacy integer permissions will be replaced by RBAC string permissions.
     */
    public const PERM_DELETE_CONVERSATIONS = 1;
    public const PERM_EDIT_CONVERSATIONS = 2;
    public const PERM_EDIT_SAVED_REPLIES = 3;
    public const PERM_EDIT_TAGS = 4;
    public const PERM_EDIT_CUSTOM_FOLDERS = 5;
    public const PERM_EDIT_USERS = 10;

    /**
     * Cached RBAC super-admin check result.
     * Reset when roles are modified.
     */
    protected ?bool $cachedIsSuperAdmin = null;

    /**
     * Cached RBAC role IDs for permission checks.
     *
     * @var \Illuminate\Support\Collection<int, int>|null
     */
    protected $cachedRoleIds = null;

    /**
     * The RBAC roles assigned to this user.
     *
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    /**
     * The companies that the user belongs to.
     *
     * @return BelongsToMany<\Modules\Crm\Models\Company, $this>
     */
    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(\Modules\Crm\Models\Company::class, 'company_user')
            ->withPivot('role_id', 'status', 'is_primary', 'is_approver', 'approval_limit', 'manager_id')
            ->withTimestamps();
    }

    /**
     * Check if the user has access to the given company.
     *
     * @param  int|string|\Modules\Crm\Models\Company  $company
     */
    public function hasCompanyAccess($company): bool
    {
        // MSP Admin has access to all companies (Global Role)
        if ($this->role === self::ROLE_ADMIN) {
            return true;
        }

        $companyId = $company instanceof \Modules\Crm\Models\Company ? $company->id : $company;

        return $this->companies()
            ->where('companies.id', $companyId)
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
        'is_demo',
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
            'is_demo' => 'boolean',
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
     * Get the user's tour progress records.
     *
     * @return HasMany<\Modules\KnowledgeBase\Models\UserTourProgress, $this>
     */
    public function tourProgress(): HasMany
    {
        return $this->hasMany('Modules\KnowledgeBase\Models\UserTourProgress');
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
            get: fn () => trim("{$this->first_name} {$this->last_name}")
        );
    }

    /**
     * Check if user is a super admin via RBAC.
     *
     * This replaces the legacy `role === ROLE_ADMIN` check. A user is considered
     * a super admin if any of their assigned RBAC roles has `is_super_admin = true`.
     * Falls back to the legacy column during the transition period.
     */
    public function isAdmin(): bool
    {
        if ($this->cachedIsSuperAdmin !== null) {
            return $this->cachedIsSuperAdmin;
        }

        // Skip the DB query for model instances not persisted to the database.
        // An unsaved user cannot have RBAC roles assigned, so fall through
        // immediately to the legacy role check.
        if (! $this->exists) {
            $result = $this->role === self::ROLE_ADMIN;
            $this->cachedIsSuperAdmin = $result;

            return $result;
        }

        // Primary: Check RBAC roles for super_admin flag
        $hasSuperAdminRole = $this->roles()->where('is_super_admin', true)->exists();

        if ($hasSuperAdminRole) {
            $this->cachedIsSuperAdmin = true;

            return true;
        }

        // Fallback: Legacy column (transition period — will be removed)
        /** @deprecated Remove this fallback once all users are migrated to RBAC roles */
        if ($this->role === self::ROLE_ADMIN) {
            $this->cachedIsSuperAdmin = true;

            return true;
        }

        $this->cachedIsSuperAdmin = false;

        return false;
    }

    /**
     * Check if the user has a specific RBAC role by name.
     */
    public function hasRole(string $roleName): bool
    {
        return $this->roles()->where('name', $roleName)->exists();
    }

    /**
     * Check if the user has any of the given RBAC roles.
     *
     * @param  string|array<string>  $roles
     */
    public function hasAnyRole(string|array $roles): bool
    {
        $roles = is_array($roles) ? $roles : [$roles];

        return $this->roles()->whereIn('name', $roles)->exists();
    }

    /**
     * Clear cached RBAC state. Call after modifying user roles.
     */
    public function clearRbacCache(): void
    {
        $this->cachedIsSuperAdmin = null;
        $this->cachedRoleIds = null;
    }

    /**
     * Get all RBAC role IDs for this user (cached).
     *
     * @return \Illuminate\Support\Collection<int, int>
     */
    protected function getRbacRoleIds(): \Illuminate\Support\Collection
    {
        if ($this->cachedRoleIds !== null) {
            return $this->cachedRoleIds;
        }

        // Non-persisted models have no RBAC roles in the database.
        if (! $this->exists) {
            /** @var \Illuminate\Support\Collection<int, int> $empty */
            $empty = collect();
            $this->cachedRoleIds = $empty;

            return $this->cachedRoleIds;
        }

        /** @var \Illuminate\Support\Collection<int, int> $ids */
        $ids = $this->roles()->pluck('roles.id');
        $this->cachedRoleIds = $ids;

        return $this->cachedRoleIds;
    }

    /**
     * Check if user is internal staff.
     */
    public function isInternalStaff(): bool
    {
        return (int) $this->type === 1;
    }

    /**
     * Check if user has admin-equivalent access.
     */
    public function hasAdminAccess(): bool
    {
        return $this->isAdmin() || $this->isInternalStaff();
    }

    /**
     * Check if this is a client-type user (external).
     * Internal users (admin, agent, finance, etc.) are NOT clients.
     */
    public function isClient(): bool
    {
        if ($this->hasAnyRole(['Client Admin', 'Client User', 'Client Finance'])) {
            return true;
        }

        return (int) $this->type === 2;
    }

    /**
     * Check if user is a finance user.
     */
    public function isFinance(): bool
    {
        return $this->role === self::ROLE_FINANCE;
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
            get: fn () => $this->getFullName()
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
     * For string permissions: checks via RBAC (role_user → permission_role → permissions).
     * For integer permissions: legacy check via JSON column (deprecated).
     *
     * @param  int|string  $permission
     */
    public function hasPermission($permission, bool $checkOwnPermissions = true): bool
    {
        // Super admins bypass all permission checks
        if ($this->isAdmin()) {
            return true;
        }

        // New RBAC String Permissions — uses role_user pivot
        if (is_string($permission)) {
            $roleIds = $this->getRbacRoleIds();

            if ($roleIds->isEmpty() && ! empty($this->role)) {
                $roleIds = collect([(int) $this->role]);
            }

            if ($roleIds->isEmpty()) {
                return false;
            }

            return Permission::where('name', $permission)
                ->whereHas('roles', function ($query) use ($roleIds) {
                    $query->whereIn('roles.id', $roleIds);
                })->exists();
        }

        // Legacy Integer Permissions (deprecated — will be removed)
        $hasPermission = false;

        $globalPermissions = self::getGlobalUserPermissions();

        if (! empty($globalPermissions) && in_array($permission, $globalPermissions)) {
            $hasPermission = true;
        }

        if ($checkOwnPermissions && ! empty($this->permissions)) {
            if (isset($this->permissions[$permission])) {
                $hasPermission = (bool) $this->permissions[$permission];
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
     * Get the user's primary company.
     *
     * Replaces legacy `$user->client` — the Company IS the business entity.
     * Prefers the company marked is_primary, falls back to first attached.
     */
    public function company(): ?\Modules\Crm\Models\Company
    {
        return $this->companies()->wherePivot('is_primary', true)->first()
            ?? $this->companies()->first();
    }

    /**
     * Get the primary company ID for this user.
     */
    public function getCompanyIdAttribute(): ?int
    {
        return $this->company()?->id;
    }

    /**
     * Get the legacy client ID for this user (for Phase 1 temporary mapping).
     *
     * @deprecated Use company_id instead. Will be removed after Phase 3.
     */
    public function getClientIdAttribute(): ?int
    {
        // During transition, client_id maps through the company relationship
        return $this->company_id;
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
