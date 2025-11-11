<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Conversation;

class ConversationObserver
{
    /**
     * Handle the Conversation "creating" event.
     */
    public function creating(Conversation $conversation): void
    {
        // Mark as read if created by user
        if ($conversation->source_via === Conversation::PERSON_USER) {
            $conversation->read_by_user = true;
        }

        // Set default status if not provided
        if (! $conversation->status) {
            $conversation->status = Conversation::STATUS_ACTIVE;
        }
    }

    /**
     * Handle the Conversation "created" event.
     */
    public function created(Conversation $conversation): void
    {
        // Update folder counters if folder exists
        if ($conversation->folder) {
            $conversation->folder->increment('total_count');
            if ($conversation->status === Conversation::STATUS_ACTIVE) {
                $conversation->folder->increment('active_count');
            }
        }
    }

    /**
     * Handle the Conversation "updated" event.
     */
    public function updated(Conversation $conversation): void
    {
        // Update folder counters if status changed
        if ($conversation->wasChanged('status')) {
            // Update old folder counters
            if ($conversation->folder) {
                $this->updateFolderCounters($conversation->folder);
            }
        }
    }

    /**
     * Handle the Conversation "deleting" event.
     */
    public function deleting(Conversation $conversation): void
    {
        // Delete related records
        $conversation->threads()->delete();
        $conversation->followers()->detach();

        // Update folder counters
        if ($conversation->folder) {
            $conversation->folder->decrement('total_count');
            if ($conversation->status === Conversation::STATUS_ACTIVE) {
                $conversation->folder->decrement('active_count');
            }
        }
    }

    /**
     * Update folder counters by recounting conversations.
     */
    private function updateFolderCounters($folder): void
    {
        $total = $folder->conversations()->count();
        $active = $folder->conversations()
            ->where('status', Conversation::STATUS_ACTIVE)
            ->count();

        $folder->update([
            'total_count' => $total,
            'active_count' => $active,
        ]);
    }
}
