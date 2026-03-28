<?php

declare(strict_types=1);

namespace Modules\MiddleMan\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MiddleManAuditEntry extends Model
{
    public $timestamps = false;

    protected $table = 'middleman_audit_trail';

    protected $fillable = [
        'user_id',
        'action',
        'subject_type',
        'subject_id',
        'details',
    ];

    protected function casts(): array
    {
        return [
            'details'    => 'array',
            'created_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Constants — Action Types
    |--------------------------------------------------------------------------
    */

    public const ACTION_RULE_CREATED       = 'rule_created';
    public const ACTION_RULE_DELETED       = 'rule_deleted';
    public const ACTION_LOGGING_TOGGLED    = 'logging_toggled';
    public const ACTION_INTERCEPT_TOGGLED  = 'intercept_toggled';
    public const ACTION_INTERCEPT_FIRED    = 'intercept_fired';
    public const ACTION_INTERCEPT_DISCARDED = 'intercept_discarded';
    public const ACTION_PAYLOAD_EDITED     = 'payload_edited';
    public const ACTION_EVENT_MARSHALLED   = 'event_marshalled';
    public const ACTION_BATCH_FIRED        = 'batch_fired';
    public const ACTION_ORDER_CHANGED      = 'order_changed';

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Factory
    |--------------------------------------------------------------------------
    */

    public static function record(int $userId, string $action, ?string $subjectType = null, ?int $subjectId = null, ?array $details = null): static
    {
        return static::create([
            'user_id'      => $userId,
            'action'       => $action,
            'subject_type' => $subjectType,
            'subject_id'   => $subjectId,
            'details'      => $details,
        ]);
    }
}
