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
    public const NAME_CONVERSATION = 'conversation';
    public const TYPE_CONVERSATION = 'conversation';

    /**
     * Available log categories.
     *
     * @var array<string>
     */
    public static array $available_logs = [
        'default',
        self::NAME_USER,
        self::NAME_OUT_EMAILS,
        self::NAME_EMAILS_SENDING,
        self::NAME_EMAILS_FETCHING,
        self::NAME_SYSTEM,
        self::NAME_APP_LOGS,
        self::NAME_CONVERSATION,
    ];

    // Log descriptions
    public const DESCRIPTION_USER_LOGIN = 'login';
    public const DESCRIPTION_USER_LOGOUT = 'logout';
    public const DESCRIPTION_USER_REGISTER = 'register';
    public const DESCRIPTION_USER_LOCKED = 'locked';
    public const DESCRIPTION_USER_LOGIN_FAILED = 'login_failed';
    public const DESCRIPTION_USER_PASSWORD_RESET = 'password_reset';
    public const DESCRIPTION_USER_DELETED = 'user_deleted';
    public const DESCRIPTION_USER_CREATED = 'user_created';
    public const DESCRIPTION_CONVERSATION_STATUS_CHANGED = 'conversation_status_changed';
    public const DESCRIPTION_CONVERSATION_USER_CHANGED = 'conversation_user_changed';
    public const DESCRIPTION_CONVERSATION_DELETED = 'conversation_deleted';
    public const DESCRIPTION_EMAIL_SEND_ERROR_TO_CUSTOMER = 'email_send_error_to_customer';
    public const DESCRIPTION_EMAIL_SEND_ERROR_TO_USER = 'email_send_error_to_user';
    public const DESCRIPTION_EMAIL_SEND_ERROR = 'email_send_error';
    public const DESCRIPTION_EMAILS_SENDING_ERROR_TO_CUSTOMER = 'error_sending_email_to_customer';
    public const DESCRIPTION_EMAILS_SENDING_ERROR_TO_USER = 'error_sending_email_to_user';
    public const DESCRIPTION_EMAILS_SENDING_ERROR_INVITE = 'error_sending_invite_to_user';
    public const DESCRIPTION_EMAILS_SENDING_ERROR_PASSWORD_CHANGED = 'error_sending_password_changed';
    public const DESCRIPTION_EMAILS_SENDING_ERROR_ALERT = 'error_sending_alert';
    public const DESCRIPTION_EMAILS_SENDING_ERROR_WRONG_EMAIL = 'error_sending_wrong_email';
    public const DESCRIPTION_EMAILS_FETCHING_ERROR = 'error_fetching_email';
    public const DESCRIPTION_SYSTEM_ERROR = 'system_error';
    public const DESCRIPTION_EMAILS_SENDING_ERROR_AUTO_REPLY = 'error_sending_auto_reply';
    public const DESCRIPTION_EMAILS_SENDING_ERROR_USER_NOTIFICATION = 'error_sending_user_notification';
    public const DESCRIPTION_EMAILS_SENDING_ERROR_SYSTEM = 'error_sending_system_email';
    public const DESCRIPTION_EMAILS_SENDING_ERROR_FORWARD = 'error_sending_forward';

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

    /**
     * Get human-readable event description.
     */
    public function getEventDescription(): string
    {
        return match ($this->description) {
            self::DESCRIPTION_USER_LOGIN => __('Logged in'),
            self::DESCRIPTION_USER_LOGOUT => __('Logged out'),
            self::DESCRIPTION_USER_REGISTER => __('Registered'),
            self::DESCRIPTION_USER_LOCKED => __('Locked out'),
            self::DESCRIPTION_USER_LOGIN_FAILED => __('Failed login'),
            self::DESCRIPTION_USER_PASSWORD_RESET => __('Reset password'),
            self::DESCRIPTION_EMAILS_SENDING_ERROR_TO_CUSTOMER => __('Error sending email to customer'),
            self::DESCRIPTION_EMAILS_SENDING_ERROR_TO_USER => __('Error sending email to user'),
            self::DESCRIPTION_EMAILS_SENDING_ERROR_INVITE => __('Error sending invitation email to user'),
            self::DESCRIPTION_EMAILS_SENDING_ERROR_PASSWORD_CHANGED => __('Error sending password changed notification to user'),
            self::DESCRIPTION_EMAILS_SENDING_ERROR_ALERT => __('Error sending alert'),
            self::DESCRIPTION_EMAILS_SENDING_ERROR_WRONG_EMAIL => __('Error sending email to the user who replied to notification from wrong email'),
            self::DESCRIPTION_EMAILS_FETCHING_ERROR => __('Error fetching email'),
            self::DESCRIPTION_SYSTEM_ERROR => __('System error'),
            self::DESCRIPTION_USER_DELETED => __('Deleted user'),
            self::DESCRIPTION_USER_CREATED => __('User created'),
            self::DESCRIPTION_CONVERSATION_STATUS_CHANGED => __('Conversation status changed'),
            self::DESCRIPTION_EMAILS_SENDING_ERROR_AUTO_REPLY => __('Error sending auto reply'),
            self::DESCRIPTION_EMAILS_SENDING_ERROR_USER_NOTIFICATION => __('Error sending user notification'),
            self::DESCRIPTION_EMAILS_SENDING_ERROR_SYSTEM => __('Error sending system email'),
            self::DESCRIPTION_EMAILS_SENDING_ERROR_FORWARD => __('Error sending forward'),
            default => (string) ($this->description ?? ''),
        };
    }

    /**
     * Get title for the log record.
     */
    public static function getLogTitle(string $logName): string
    {
        return match ($logName) {
            self::NAME_USER => __('Users'),
            self::NAME_OUT_EMAILS => __('Outgoing Emails'),
            self::NAME_EMAILS_SENDING => __('Send Errors'),
            self::NAME_EMAILS_FETCHING => __('Fetch Errors'),
            self::NAME_SYSTEM => __('System'),
            self::NAME_APP_LOGS => __('App Logs'),
            self::NAME_CONVERSATION => __('Conversations'),
            default => ucwords(str_replace('_', ' ', $logName)),
        };
    }

    /**
     * Format column title for display.
     */
    public static function formatColTitle(string $col): string
    {
        $col = str_replace('_', ' ', $col);

        return ucwords($col);
    }

    /**
     * Get distinct log names from database.
     *
     * @return array<string>
     */
    public static function getLogNames(): array
    {
        return self::select('log_name')->distinct()->pluck('log_name')->toArray();
    }

    /**
     * Get available log names.
     *
     * @return array<string>
     */
    public static function getAvailableLogs(bool $checkExisting = true): array
    {
        $availableLogs = self::$available_logs;
        if ($checkExisting) {
            $availableLogs = array_merge($availableLogs, self::getLogNames());
        }

        return array_values(array_unique($availableLogs));
    }
}
