<?php

namespace Modules\TreeScoutDeploymentManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * A One-Time Activation Code (OTAC).
 *
 * When a client server calls the public /api/tsdm/activate endpoint with a valid code,
 * this model is marked `used_at` and a short-lived Git token is issued.
 *
 * @property int    $id
 * @property string $activation_code
 * @property int    $deployment_record_id
 * @property int|null $issued_by_user_id
 * @property array|null $requested_scopes
 * @property \Carbon\Carbon $expires_at
 * @property \Carbon\Carbon|null $used_at
 * @property string|null $redeemed_from_ip
 * @property string|null $issued_token_encrypted
 * @property string|null $label
 */
class DeploymentActivation extends Model
{
    protected $table = 'tsdm_deployment_activations';

    protected $fillable = [
        'activation_code',
        'deployment_record_id',
        'issued_by_user_id',
        'requested_scopes',
        'expires_at',
        'used_at',
        'redeemed_from_ip',
        'issued_token_encrypted',
        'label',
    ];

    protected $casts = [
        'expires_at'       => 'datetime',
        'used_at'          => 'datetime',
        'requested_scopes' => 'array',
    ];

    // ------------------------------------------------------------------
    // Factory
    // ------------------------------------------------------------------

    /**
     * Generate a new activation code in the format TREE-XXXX-XXXX.
     */
    public static function generateCode(): string
    {
        return 'TREE-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4));
    }

    // ------------------------------------------------------------------
    // Relationships
    // ------------------------------------------------------------------

    /** @return BelongsTo<DeploymentRecord, DeploymentActivation> */
    public function deploymentRecord(): BelongsTo
    {
        return $this->belongsTo(DeploymentRecord::class, 'deployment_record_id');
    }

    // ------------------------------------------------------------------
    // Scopes
    // ------------------------------------------------------------------

    /** @param \Illuminate\Database\Eloquent\Builder<DeploymentActivation> $query */
    public function scopeValid($query): void
    {
        $query->whereNull('used_at')->where('expires_at', '>', now());
    }

    /** @param \Illuminate\Database\Eloquent\Builder<DeploymentActivation> $query */
    public function scopeExpired($query): void
    {
        $query->whereNull('used_at')->where('expires_at', '<=', now());
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    public function isValid(): bool
    {
        return is_null($this->used_at) && $this->expires_at->isFuture();
    }

    public function isUsed(): bool
    {
        return !is_null($this->used_at);
    }

    public function isExpired(): bool
    {
        return is_null($this->used_at) && $this->expires_at->isPast();
    }

    public function statusLabel(): string
    {
        if ($this->isUsed()) {
            return 'Used';
        }
        if ($this->isExpired()) {
            return 'Expired';
        }
        return 'Valid';
    }

    public function statusColor(): string
    {
        if ($this->isUsed()) {
            return 'gray';
        }
        if ($this->isExpired()) {
            return 'danger';
        }
        return 'success';
    }
}
