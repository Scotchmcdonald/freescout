<?php

namespace Modules\TreeScoutDeploymentManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Represents one client "installation" — a server running the app under a specific client account.
 *
 * @property int    $id
 * @property int    $client_id
 * @property string $name
 * @property string $environment
 * @property string $git_provider
 * @property string|null $git_project_id
 * @property string|null $server_ip
 * @property string|null $server_fingerprint
 * @property string $status
 * @property \Carbon\Carbon|null $last_seen_at
 * @property string|null $app_version
 * @property string|null $notes
 */
class DeploymentRecord extends Model
{
    use SoftDeletes;

    protected $table = 'tsdm_deployment_records';

    protected $fillable = [
        'client_id',
        'name',
        'environment',
        'git_provider',
        'git_project_id',
        'server_ip',
        'server_fingerprint',
        'status',
        'last_seen_at',
        'app_version',
        'notes',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    // ------------------------------------------------------------------
    // Relationships
    // ------------------------------------------------------------------

    /** @return HasMany<DeploymentActivation> */
    public function activations(): HasMany
    {
        return $this->hasMany(DeploymentActivation::class, 'deployment_record_id');
    }

    /** @return HasMany<DeployedModule> */
    public function deployedModules(): HasMany
    {
        return $this->hasMany(DeployedModule::class, 'deployment_record_id');
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isSuspended(): bool
    {
        return in_array($this->status, ['suspended', 'revoked']);
    }

    /**
     * Returns the most recent unused activation code (if any).
     */
    public function pendingActivation(): ?DeploymentActivation
    {
        return $this->activations()
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();
    }

    /**
     * Status badge colour for Blade templates.
     */
    public function statusColor(): string
    {
        return match ($this->status) {
            'active'    => 'success',
            'pending'   => 'warning',
            'suspended' => 'danger',
            'revoked'   => 'danger',
            default     => 'gray',
        };
    }
}
