<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\Subscription;
use App\Models\User;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        // Create admin personal folders for all mailboxes
        if ($user->isAdmin()) {
            $this->createAdminPersonalFolders($user);
        }

        // Add default subscriptions
        $this->addDefaultSubscriptions($user);
    }

    /**
     * Handle the User "deleting" event.
     */
    public function deleting(User $user): void
    {
        // Delete user's personal folders
        $user->folders()->delete();

        // Remove from conversation followers
        $user->followedConversations()->detach();

        // Unassign from conversations (set user_id to null)
        $user->conversations()->update(['user_id' => null]);
    }

    /**
     * Create admin personal folders for all mailboxes.
     */
    private function createAdminPersonalFolders(User $user): void
    {
        $mailboxes = Mailbox::all();

        foreach ($mailboxes as $mailbox) {
            Folder::firstOrCreate([
                'mailbox_id' => $mailbox->id,
                'user_id' => $user->id,
                'type' => Folder::TYPE_MINE,
            ], [
                'name' => 'My Conversations',
            ]);
        }
    }

    /**
     * Add default subscriptions for a new user.
     */
    private function addDefaultSubscriptions(User $user): void
    {
        // Subscribe to assigned conversations
        Subscription::firstOrCreate([
            'user_id' => $user->id,
            'medium' => Subscription::MEDIUM_EMAIL,
            'event' => Subscription::EVENT_CONVERSATION_ASSIGNED_TO_ME,
        ]);

        // Subscribe to followed conversations
        Subscription::firstOrCreate([
            'user_id' => $user->id,
            'medium' => Subscription::MEDIUM_EMAIL,
            'event' => Subscription::EVENT_FOLLOWED_CONVERSATION_UPDATED,
        ]);
    }
}
