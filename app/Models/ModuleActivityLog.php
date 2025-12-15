<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModuleActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'module_name',
        'action',
        'metadata',
        'ip_address',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who performed the action.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for filtering by action type.
     * 
     * @param \Illuminate\Database\Eloquent\Builder<ModuleActivityLog> $query
     * @return \Illuminate\Database\Eloquent\Builder<ModuleActivityLog>
     */
    public function scopeOfAction(\Illuminate\Database\Eloquent\Builder $query, string $action): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('action', $action);
    }

    /**
     * Scope for filtering by module.
     * 
     * @param \Illuminate\Database\Eloquent\Builder<ModuleActivityLog> $query
     * @return \Illuminate\Database\Eloquent\Builder<ModuleActivityLog>
     */
    public function scopeOfModule(\Illuminate\Database\Eloquent\Builder $query, string $moduleName): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('module_name', $moduleName);
    }
}
