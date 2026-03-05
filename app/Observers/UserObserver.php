<?php

declare(strict_types=1);

namespace App\Observers;

use App\DataTransferObjects\UserStatusChangedData;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\Subscription;
use App\Models\User;
use Modules\Crm\Events\UserStatusChanged;

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
     * Handle the User "updated" event.
     *
     * Dispatches UserStatusChanged when the user's status field changes.
     * Previously this was handled by ClientUser::booted() — now unified here.
     */
    public function updated(User $user): void
    {
        if ($user->isDirty('status')) {
            $oldStatusInt = (int) $user->getOriginal('status');
            $newStatusInt = (int) $user->status;

            $oldStatus = $oldStatusInt === User::STATUS_ACTIVE ? 'active' : 'inactive';
            $newStatus = $newStatusInt === User::STATUS_ACTIVE ? 'active' : 'inactive';

            if ($oldStatus !== $newStatus) {
                event(new UserStatusChanged(
                    new UserStatusChangedData(
                        userId: $user->id,
                        clientId: $user->company_id ?? 0,
                        email: $user->email,
                        oldStatus: $oldStatus,
                        newStatus: $newStatus,
                        reason: null,
                    )
                ));
            }
        }
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

        // Dispatch UserDeleted event
        // Note: We pass the user as both deleted_user and by_user (assuming self-delete or system delete)
        // In a real controller, by_user would be the authenticated user
        \App\Events\UserDeleted::dispatch($user, $user);
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
