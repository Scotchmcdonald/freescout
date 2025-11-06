<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Thread;

class ThreadObserver
{
    /**
     * Handle the Thread "created" event.
     */
    public function created(Thread $thread): void
    {
        // Increment the conversation's thread count
        if ($thread->conversation_id) {
            $thread->conversation->increment('threads_count');
        }
    }

    /**
     * Handle the Thread "deleted" event.
     */
    public function deleted(Thread $thread): void
    {
        // Decrement the conversation's thread count
        $thread->conversation()->decrement('threads_count');
    }
}
