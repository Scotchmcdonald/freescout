<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class ConversationUserChanged
{
    use Dispatchable;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Conversation $conversation,
        public User $user
    ) {}
}
