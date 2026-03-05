<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Conversation;
use App\Models\Thread;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CustomerReplied
{
    use Dispatchable, SerializesModels;

    public Conversation $conversation;
    public Thread $thread;
    public ?array $senderInfo;

    /**
     * Create a new event instance.
     */
    public function __construct(Conversation $conversation, Thread $thread, ?array $senderInfo = null)
    {
        $this->conversation = $conversation;
        $this->thread = $thread;
        $this->senderInfo = $senderInfo ?? ["email" => $conversation->customer_email];
    }
}
