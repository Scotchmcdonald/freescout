<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $customer_id
 * @property int $channel
 * @property string $channel_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Customer $customer
 */
class CustomerChannel extends Model
{
    protected $table = 'customer_channel';

    protected $fillable = [
        'customer_id',
        'channel',
        'channel_id',
    ];

    // Channel type constants
    public const CHANNEL_EMAIL = 1;

    public const CHANNEL_PHONE = 2;

    public const CHANNEL_CHAT = 3;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'customer_id' => 'integer',
            'channel' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the customer that owns this channel.
     *
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
