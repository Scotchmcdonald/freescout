<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property bool $is_default
 * @property int $status
 * @property string|array<int, string>|null $aliases
 * @property bool $aliases_reply
 * @property string|null $from_name
 * @property string|null $from_name_custom
 * @property int $ticket_status
 * @property int $ticket_assignee
 * @property string|null $template
 * @property string|null $signature
 * @property string|null $before_reply
 * @property int $out_method
 * @property string|null $out_server
 * @property int|null $out_port
 * @property string|null $out_username
 * @property string|null $out_password
 * @property string|null $out_encryption
 * @property string|null $in_server
 * @property int|null $in_port
 * @property string|null $in_username
 * @property string|null $in_password
 * @property string|null $in_protocol
 * @property string|null $in_encryption
 * @property bool $in_validate_cert
 * @property string|array<int, string>|null $in_imap_folders
 * @property string|null $imap_sent_folder
 * @property string|null $auto_bcc
 * @property bool $auto_reply_enabled
 * @property string|null $auto_reply_subject
 * @property string|null $auto_reply_message
 * @property bool $office_hours_enabled
 * @property bool $ratings
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Conversation> $conversations
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Folder> $folders
 *
 * @method static \Illuminate\Database\Eloquent\Builder<Mailbox>|Mailbox find(int $id, array<int, string> $columns = ['*'])
 * @method static \Illuminate\Database\Eloquent\Builder<Mailbox>|Mailbox where(string $column, mixed $operator = null, mixed $value = null, string $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder<Mailbox>|Mailbox whereNotNull(string $column, string $boolean = 'and')
 *
 * @mixin \Illuminate\Database\Eloquent\Builder<Mailbox>
 */
class Mailbox extends Model
{
    /** @use HasFactory<\Database\Factories\MailboxFactory> */
    use HasFactory;

    const FROM_NAME_CUSTOM = 2;
    const TICKET_STATUS_ACTIVE = 1;
    const TICKET_ASSIGNEE_ANYONE = 1;
    const OUT_ENCRYPTION_TLS = 2;

    protected $fillable = [
        'name',
        'email',
        'is_default',
        'status',
        'aliases',
        'aliases_reply',
        'from_name',
        'from_name_custom',
        'ticket_status',
        'ticket_assignee',
        'template',
        'signature',
        'before_reply',
        'out_method',
        'out_server',
        'out_port',
        'out_username',
        'out_password',
        'out_encryption',
        'in_server',
        'in_port',
        'in_username',
        'in_password',
        'in_protocol',
        'in_encryption',
        'in_validate_cert',
        'in_imap_folders',
        'imap_sent_folder',
        'auto_bcc',
        'auto_reply_enabled',
        'auto_reply_subject',
        'auto_reply_message',
        'office_hours_enabled',
        'ratings',
        'ratings_placement',
        'ratings_text',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'status' => 'integer',
            'aliases_reply' => 'boolean',
            'ticket_status' => 'integer',
            'ticket_assignee' => 'integer',
            'template' => 'integer',
            'out_method' => 'integer',
            'out_port' => 'integer',
            'out_encryption' => 'integer',
            'in_port' => 'integer',
            'in_protocol' => 'integer',
            'in_encryption' => 'integer',
            'in_validate_cert' => 'boolean',
            'auto_reply_enabled' => 'boolean',
            'office_hours_enabled' => 'boolean',
            'ratings' => 'boolean',
            'ratings_placement' => 'integer',
            'meta' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the users that have access to this mailbox.
     *
     * @return BelongsToMany<User, $this, MailboxUser>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'mailbox_user')
            ->using(MailboxUser::class)
            ->withPivot('access', 'after_send')
            ->withTimestamps();
    }

    /**
     * Get the folders for this mailbox.
     *
     * @return HasMany<Folder, $this>
     */
    public function folders(): HasMany
    {
        return $this->hasMany(Folder::class);
    }

    /**
     * Get the conversations for this mailbox.
     *
     * @return HasMany<Conversation, $this>
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    /**
     * Get the "From" name and email for outgoing mail.
     *
     * @param  User|null  $user  User sending the email (optional)
     * @return array{address: string, name: string}
     */
    public function getMailFrom(?User $user = null): array
    {
        $from = [
            'address' => $this->email,
            'name' => $this->from_name ?? $this->name,
        ];

        // Use custom from name if set
        if ($this->from_name_custom) {
            $from['name'] = $this->from_name_custom;
        }

        return $from;
    }

    /**
     * Get mailbox URL.
     */
    public function url(): string
    {
        return route('mailboxes.view', ['mailbox' => $this->id]);
    }

    /**
     * Check if user has access to this mailbox.
     */
    public function userHasAccess(int $userId): bool
    {
        return $this->users()->wherePivot('user_id', $userId)->exists();
    }

    /**
     * Check if the mailbox has the given alias.
     */
    public function hasAlias(string $email): bool
    {
        $aliases = $this->getAliasesArray();
        return in_array($email, $aliases);
    }

    /**
     * Get aliases as an array.
     *
     * @return array<int, string>
     */
    public function getAliasesArray(): array
    {
        if (empty($this->aliases)) {
            return [];
        }
        if (is_array($this->aliases)) {
            return $this->aliases;
        }
        return explode(',', (string)$this->aliases);
    }

    /**
     * Get mailbox aliases as an associative array.
     *
     * @return array<string, string>
     */
    public function getAliases(bool $includeMailboxEmail = true, bool $checkAliasesReply = false): array
    {
        if ($checkAliasesReply && !$this->aliases_reply) {
            return [];
        }

        $emails = [];
        if ($includeMailboxEmail) {
            $emails[$this->email] = $this->name;
        }

        if ($this->aliases) {
            $aliasesList = is_array($this->aliases) ? $this->aliases : explode(',', (string)$this->aliases);
            foreach ($aliasesList as $alias) {
                $name = '';
                $alias = trim($alias);

                // Parse alias format: email(Name) using a safer pattern
                if (preg_match('/^([^(]+)\(([^)]*)\)$/', $alias, $m)) {
                    $alias = trim($m[1]);
                    $name = $m[2] ?? '';
                }

                $alias = filter_var(trim($alias), FILTER_VALIDATE_EMAIL);
                if ($alias) {
                    $emails[$alias] = $name;
                }
            }
        }

        return $emails;
    }

    /**
     * Remove mailbox email and aliases from the list of emails.
     *
     * @param array<string> $list
     * @return array<string>
     */
    public function removeMailboxEmailsFromList(array $list): array
    {
        $mailboxEmails = array_keys($this->getAliases(true, false));
        
        return array_filter($list, function ($email) use ($mailboxEmails) {
            return !in_array(strtolower(trim($email)), array_map('strtolower', $mailboxEmails));
        });
    }

    /**
     * Check if fetching is enabled.
     */
    public function isFetchingEnabled(): bool
    {
        return !empty($this->in_server);
    }

    /**
     * Check if sending is enabled.
     */
    public function isSendingEnabled(): bool
    {
        return !empty($this->out_server);
    }
}
