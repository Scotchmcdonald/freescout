<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Channel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'settings',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'type' => 'integer',
            'settings' => 'json',
            'active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the customers using this channel.
     */
    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'customer_channel')
            ->withTimestamps();
    }

    /**
     * Check if channel is active.
     */
    public function isActive(): bool
    {
        return $this->active === true;
    }
}
