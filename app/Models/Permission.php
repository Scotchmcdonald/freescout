<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    protected $fillable = ['name', 'label'];

    /** @return BelongsToMany<Role, $this> */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * Register a new permission if it doesn't exist.
     *
     * @param string $name
     * @param string|null $label
     * @return Permission
     */
    public static function register(string $name, ?string $label = null): self
    {
        return self::firstOrCreate(
            ['name' => $name],
            ['label' => $label ?? ucwords(str_replace('_', ' ', $name))]
        );
    }
}
