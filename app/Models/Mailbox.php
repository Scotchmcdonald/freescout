<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mailbox extends Model
{
    use HasFactory;

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
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('after_send')
            ->withTimestamps();
    }

    /**
     * Get the folders for this mailbox.
     */
    public function folders(): HasMany
    {
        return $this->hasMany(Folder::class);
    }

    /**
     * Get the conversations for this mailbox.
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    /**
     * Get the "From" name and email for outgoing mail.
     * 
     * @param User|null $user User sending the email (optional)
     * @return array ['address' => string, 'name' => string]
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
}
