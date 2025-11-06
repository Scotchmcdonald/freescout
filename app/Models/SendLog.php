<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SendLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'thread_id',
        'customer_id',
        'user_id',
        'message_id',
        'email',
        'status',
        'status_message',
        'opens',
        'clicks',
        'opened_at',
        'clicked_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'thread_id' => 'integer',
            'customer_id' => 'integer',
            'user_id' => 'integer',
            'status' => 'integer',
            'opens' => 'integer',
            'clicks' => 'integer',
            'opened_at' => 'datetime',
            'clicked_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the thread associated with this send log.
     */
    public function thread(): BelongsTo
    {
        return $this->belongsTo(Thread::class);
    }

    /**
     * Get the customer associated with this send log.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the user associated with this send log.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if email was sent successfully.
     */
    public function isSent(): bool
    {
        return $this->status === 1;
    }

    /**
     * Check if email failed to send.
     */
    public function isFailed(): bool
    {
        return $this->status === 2;
    }

    /**
     * Check if email was opened.
     */
    public function wasOpened(): bool
    {
        return $this->opens > 0;
    }

    /**
     * Check if any links were clicked.
     */
    public function wasClicked(): bool
    {
        return $this->clicks > 0;
    }
}
