<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Conversation;
use Illuminate\Foundation\Events\Dispatchable;

class ConversationStatusChanged
{
    use Dispatchable;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Conversation $conversation,
        public ?\App\Models\User $user = null,
        public int $oldStatus = 0,
        public int $newStatus = 0
    ) {}
}
