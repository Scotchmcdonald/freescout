<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Conversation;
use App\Models\Thread;
use Illuminate\Foundation\Events\Dispatchable;

class UserCreatedConversation
{
    use Dispatchable;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Conversation $conversation,
        public Thread $thread
    ) {}
}
