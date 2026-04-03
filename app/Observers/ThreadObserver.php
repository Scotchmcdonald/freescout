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
        $conversation = $thread->conversation;

        if ($conversation) {
            $conversation->increment('threads_count');

            if ($thread->type !== Thread::TYPE_DRAFT) {
                $conversation->last_contact_at = $thread->created_at ?? now();
            }

            // Update preview
            if ($thread->body && $thread->type !== Thread::TYPE_DRAFT) {
                $conversation->preview = substr(strip_tags($thread->body), 0, 100);
            }

            $conversation->saveQuietly();
        }
    }

    /**
     * Handle the Thread "deleted" event.
     */
    public function deleted(Thread $thread): void
    {
        // Decrement the conversation's thread count
        /** @var \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Conversation, \App\Models\Thread> $conversationQuery */
        $conversationQuery = $thread->conversation();
        $conversationQuery->decrement('threads_count');

        // Delete attachments
        $thread->attachments()->delete();
    }
}
