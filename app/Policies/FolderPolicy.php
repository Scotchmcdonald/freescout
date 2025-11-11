<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Folder;
use App\Models\User;

class FolderPolicy
{
    /**
     * Determine whether the user can view the folder.
     */
    public function view(User $user, Folder $folder): bool
    {
        // Admins can view all folders
        if ($user->isAdmin()) {
            return true;
        }

        // Users can view their own personal folders
        if ($folder->user_id == $user->id) {
            return true;
        }

        // Users can view folders for mailboxes they have access to
        $hasAccess = $user->mailboxes()->where('mailbox_id', $folder->mailbox_id)->exists();
        
        return $hasAccess;
    }
}
