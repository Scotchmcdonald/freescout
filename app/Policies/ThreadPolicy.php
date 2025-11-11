<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Thread;
use App\Models\User;

class ThreadPolicy
{
    /**
     * Determine whether the user can edit the thread.
     */
    public function edit(User $user, Thread $thread): bool
    {
        // Thread types
        $messageTypes = [Thread::TYPE_MESSAGE, Thread::TYPE_NOTE];
        $customerTypes = [Thread::TYPE_CUSTOMER];

        // Users can edit their own messages and notes
        if ($thread->created_by_user_id 
            && in_array($thread->type, $messageTypes)
            && $thread->created_by_user_id == $user->id
        ) {
            return true;
        }

        // Admins can edit any user-created thread
        if ($user->isAdmin() 
            && $thread->created_by_user_id 
            && in_array($thread->type, $messageTypes)
        ) {
            return true;
        }

        // Customer threads can be edited (for corrections/moderation)
        if ($thread->created_by_customer_id && in_array($thread->type, $customerTypes)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the thread.
     */
    public function delete(User $user, Thread $thread): bool
    {
        // Users can only delete their own threads
        if ($thread->created_by_user_id == $user->id) {
            return true;
        }

        return false;
    }
}
