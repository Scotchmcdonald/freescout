<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $name
 * @property string|null $label
 * @property bool $is_super_admin
 * @property string $scope
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Permission> $permissions
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $users
 */
class Role extends Model
{
    protected $fillable = ['name', 'label', 'is_super_admin', 'scope', 'sort_order'];

    protected function casts(): array
    {
        return [
            'is_super_admin' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsToMany<Permission, $this> */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    /** @return BelongsToMany<User, $this> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    /**
     * Check if this role grants super admin (wildcard) access.
     */
    public function isSuperAdmin(): bool
    {
        return $this->is_super_admin;
    }

    /**
     * Check if this is an internal (MSP) role.
     */
    public function isInternal(): bool
    {
        return $this->scope === 'internal';
    }

    /**
     * Check if this is a client-facing role.
     */
    public function isClient(): bool
    {
        return $this->scope === 'client';
    }

    /**
     * Check if this role has a specific permission.
     */
    public function hasPermission(string $permissionName): bool
    {
        return $this->permissions()->where('name', $permissionName)->exists();
    }
}
