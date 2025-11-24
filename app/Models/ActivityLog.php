<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

class ActivityLog extends SpatieActivity
{
    /** @use HasFactory<\Database\Factories\ActivityLogFactory> */
    use HasFactory;

    protected $table = 'activity_log';

    // Log names
    public const NAME_USER = 'users';
    public const NAME_OUT_EMAILS = 'out_emails';
    public const NAME_EMAILS_SENDING = 'send_errors';
    public const NAME_EMAILS_FETCHING = 'fetch_errors';
    public const NAME_SYSTEM = 'system';
    public const NAME_APP_LOGS = 'app';

    // Log descriptions
    public const DESCRIPTION_USER_LOGIN = 'login';
    public const DESCRIPTION_USER_LOGOUT = 'logout';
    public const DESCRIPTION_USER_REGISTER = 'register';
    public const DESCRIPTION_USER_LOCKED = 'locked';
    public const DESCRIPTION_USER_LOGIN_FAILED = 'login_failed';
    public const DESCRIPTION_USER_PASSWORD_RESET = 'password_reset';
    public const DESCRIPTION_USER_DELETED = 'user_deleted';

    protected $fillable = [
        'log_name',
        'description',
        'subject_type',
        'subject_id',
        'causer_type',
        'causer_id',
        'properties',
        'batch_uuid',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the user who caused this activity (convenience accessor).
     */
    public function user(): ?\App\Models\User
    {
        if ($this->causer_type === \App\Models\User::class && $this->causer instanceof \App\Models\User) {
            return $this->causer;
        }

        return null;
    }
}
