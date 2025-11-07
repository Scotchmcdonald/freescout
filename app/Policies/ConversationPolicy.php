<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

class ConversationPolicy
{
    /**
     * Determine whether the user can view the conversation.
     */
    public function view(User $user, Conversation $conversation): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Check if user has access to the conversation's mailbox
        return $user->mailboxes->contains($conversation->mailbox_id);
    }

    /**
     * Determine whether the user can create conversations.
     */
    public function create(User $user): bool
    {
        // Users can create conversations if they have access to at least one mailbox
        return $user->isAdmin() || $user->mailboxes->isNotEmpty();
    }

    /**
     * Determine whether the user can update the conversation.
     */
    public function update(User $user, Conversation $conversation): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Check if user has access to the conversation's mailbox
        return $user->mailboxes->contains($conversation->mailbox_id);
    }

    /**
     * Determine whether the user can delete the conversation.
     */
    public function delete(User $user, Conversation $conversation): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Regular users can only delete if they have access to the mailbox
        return $user->mailboxes->contains($conversation->mailbox_id);
    }
}
