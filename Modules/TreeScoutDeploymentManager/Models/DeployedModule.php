<?php

namespace Modules\TreeScoutDeploymentManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tracks a specific module installed within a DeploymentRecord.
 *
 * @property int    $id
 * @property int    $deployment_record_id
 * @property string $module_name
 * @property string|null $module_version
 * @property string $status
 * @property \Carbon\Carbon|null $installed_at
 * @property \Carbon\Carbon|null $last_updated_at
 */
class DeployedModule extends Model
{
    protected $table = 'tsdm_deployed_modules';

    protected $fillable = [
        'deployment_record_id',
        'module_name',
        'module_version',
        'status',
        'installed_at',
        'last_updated_at',
    ];

    protected $casts = [
        'installed_at'    => 'datetime',
        'last_updated_at' => 'datetime',
    ];

    // ------------------------------------------------------------------
    // Relationships
    // ------------------------------------------------------------------

    /** @return BelongsTo<DeploymentRecord, DeployedModule> */
    public function deploymentRecord(): BelongsTo
    {
        return $this->belongsTo(DeploymentRecord::class, 'deployment_record_id');
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    public function statusColor(): string
    {
        return match ($this->status) {
            'active'   => 'success',
            'disabled' => 'warning',
            'error'    => 'danger',
            default    => 'gray',
        };
    }
}
