<?php

declare(strict_types=1);

namespace App\Observers;

use App\Events\ConversationStatusChanged;
use App\Events\ConversationUserChanged;
use App\Models\Conversation;
use App\Models\Folder;

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
        $statusChanged = $conversation->wasChanged('status');
        $folderChanged = $conversation->wasChanged('folder_id');

        // Handle status changes
        if ($statusChanged) {
            // Auto-move to appropriate folder
            $conversation->updateFolder();
            
            $oldStatus = $conversation->getOriginal('status');
            $oldStatusInt = is_numeric($oldStatus) ? (int) $oldStatus : 0;
            
            ConversationStatusChanged::dispatch(
                $conversation,
                auth()->user(),
                $oldStatusInt,
                (int) $conversation->status
            );
        }

        // Handle folder changes
        if ($folderChanged) {
            // Update old folder
            $originalFolderId = $conversation->getOriginal('folder_id');
            if ($originalFolderId) {
                /** @var \App\Models\Folder|null $oldFolder */
                $oldFolder = \App\Models\Folder::find($originalFolderId);
                if ($oldFolder) {
                    $this->updateFolderCounters($oldFolder);
                }
            }
            
            // Update new folder
            if ($conversation->folder) {
                $this->updateFolderCounters($conversation->folder);
            }
        } elseif ($statusChanged) {
            // If status changed but folder didn't, we still need to update counters
            if ($conversation->folder) {
                $this->updateFolderCounters($conversation->folder);
            }
        }

        if ($conversation->wasChanged('user_id') && $conversation->user) {
            $oldUserId = $conversation->getOriginal('user_id');
            $oldUser = null;
            if ($oldUserId && is_numeric($oldUserId)) {
                $found = \App\Models\User::find((int) $oldUserId);
                if ($found instanceof \App\Models\User) {
                    $oldUser = $found;
                }
            }
            ConversationUserChanged::dispatch($conversation, $oldUser, $conversation->user, auth()->user());
        }
    }

    /**
     * Handle the Conversation "deleting" event.
     */
    public function deleting(Conversation $conversation): void
    {
        // Delete related records
        // Use each() to ensure model events are fired for threads (e.g. for attachments)
        $conversation->threads->each(function ($thread) {
            $thread->delete();
        });
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
    protected function updateFolderCounters(Folder $folder): void
    {
        // Refresh folder to ensure we have the latest state from DB before updating
        // This prevents issues where the model instance has stale data (e.g. from increments)
        // and Eloquent thinks no changes are needed.
        $folder->refresh();
        
        $folder->total_count = $folder->conversations()->count();
        $folder->active_count = $folder->conversations()->where('status', Conversation::STATUS_ACTIVE)->count();
        $folder->save();
    }
}
