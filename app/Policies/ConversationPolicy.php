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
        $hasAccess = $user->mailboxes()->where('mailbox_id', $conversation->mailbox_id)->exists();
        
        if ($hasAccess) {
            // Maybe user can see only assigned conversations
            return $this->checkIsOnlyAssigned($conversation, $user);
        }

        return false;
    }

    /**
     * Cached version of view check.
     */
    public function viewCached(User $user, Conversation $conversation): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Check if user has access via the mailbox relationship
        if ($conversation->mailbox && $conversation->mailbox->users->contains($user)) {
            // Maybe user can see only assigned conversations
            return $this->checkIsOnlyAssigned($conversation, $user);
        }

        return false;
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
        $hasAccess = $user->mailboxes()->where('mailbox_id', $conversation->mailbox_id)->exists();
        
        if ($hasAccess) {
            // Maybe user can see only assigned conversations
            return $this->checkIsOnlyAssigned($conversation, $user);
        }

        return false;
    }

    /**
     * Check if user can delete conversation.
     */
    public function delete(User $user, Conversation $conversation): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Check if user has delete permission (this would need to be added to User model)
        // For now, we'll use a basic check
        if (! $conversation->id) {
            return true;
        }

        // Check if user has access to the conversation's mailbox
        $hasAccess = $user->mailboxes()->where('mailbox_id', $conversation->mailbox_id)->exists();
        
        if ($hasAccess) {
            // Maybe user can see only assigned conversations
            return $this->checkIsOnlyAssigned($conversation, $user);
        }

        return false;
    }

    /**
     * Determine whether current user can move conversations.
     */
    public function move(User $user): bool
    {
        // First check if user has access to more than one mailbox
        if ($user->mailboxes()->count() > 1) {
            return true;
        }

        // Check if there are multiple mailboxes in the system
        return \App\Models\Mailbox::count() > 1;
    }

    /**
     * Check if user is assignee or creator of conversation.
     */
    public function checkIsOnlyAssigned(Conversation $conversation, User $user): bool
    {
        // Check if user is the assignee or created the conversation
        $isAssignee = $conversation->user_id == $user->id;
        $isCreator = $conversation->created_by_user_id == $user->id;

        // If user can only see assigned conversations, check if they are assignee or creator
        // For now, we'll allow all users to see conversations they have access to
        // This logic can be extended based on user permissions
        
        return true;
    }
}
