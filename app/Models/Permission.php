<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $name
 * @property string|null $label
 * @property string|null $module
 * @property string|null $group
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Role> $roles
 */
class Permission extends Model
{
    protected $fillable = ['name', 'label', 'module', 'group', 'sort_order'];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsToMany<Role, $this> */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * Register a new permission if it doesn't exist.
     * Updates module/group/sort_order if the permission already exists but those fields differ.
     */
    public static function register(
        string $name,
        ?string $label = null,
        ?string $module = null,
        ?string $group = null,
        int $sortOrder = 0,
    ): self {
        $permission = self::firstOrCreate(
            ['name' => $name],
            [
                'label' => $label ?? ucwords(str_replace('_', ' ', $name)),
                'module' => $module,
                'group' => $group,
                'sort_order' => $sortOrder,
            ]
        );

        // Update metadata if it was already created but fields are missing/different
        $changed = false;
        if ($module !== null && $permission->module !== $module) {
            $permission->module = $module;
            $changed = true;
        }
        if ($group !== null && $permission->group !== $group) {
            $permission->group = $group;
            $changed = true;
        }
        if ($sortOrder > 0 && $permission->sort_order !== $sortOrder) {
            $permission->sort_order = $sortOrder;
            $changed = true;
        }
        if ($changed) {
            $permission->save();
        }

        return $permission;
    }
}
