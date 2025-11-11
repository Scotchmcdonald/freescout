<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int $id
 * @property int $mailbox_id
 * @property int $user_id
 * @property int $access
 * @property bool $after_send
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class MailboxUser extends Pivot
{
    protected $table = 'mailbox_user';

    protected $fillable = [
        'mailbox_id',
        'user_id',
        'access',
        'after_send',
    ];

    public $timestamps = true;

    // Access level constants
    public const ACCESS_VIEW = 10;

    public const ACCESS_REPLY = 20;

    public const ACCESS_ADMIN = 30;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'mailbox_id' => 'integer',
            'user_id' => 'integer',
            'access' => 'integer',
            'after_send' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
