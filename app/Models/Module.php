<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    use HasFactory;

    protected $fillable = [
        'alias',
        'name',
        'active',
        'version',
        'description',
        'author',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'settings' => 'json',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Check if module is active.
     */
    public function isActive(): bool
    {
        return $this->active === true;
    }

    /**
     * Activate the module.
     */
    public function activate(): bool
    {
        $this->active = true;

        return $this->save();
    }

    /**
     * Deactivate the module.
     */
    public function deactivate(): bool
    {
        $this->active = false;

        return $this->save();
    }
}
