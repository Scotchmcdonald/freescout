<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'medium',
        'event',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'medium' => 'integer',
            'event' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns the subscription.
     * 
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if this is an email subscription.
     */
    public function isEmail(): bool
    {
        return $this->medium === 1;
    }

    /**
     * Check if this is a browser subscription.
     */
    public function isBrowser(): bool
    {
        return $this->medium === 2;
    }

    /**
     * Check if this is a mobile subscription.
     */
    public function isMobile(): bool
    {
        return $this->medium === 3;
    }
}
