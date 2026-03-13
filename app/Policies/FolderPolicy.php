<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Folder;
use App\Models\User;

/**
 * FolderPolicy — governs folder visibility.
 *
 * Admin bypass handled by Gate::before. Uses manage_tickets for
 * broader folder access.
 */
class FolderPolicy
{
    /**
     * Determine whether the user can view the folder.
     */
    public function view(User $user, Folder $folder): bool
    {
        // Users with manage_tickets can view all folders
        if ($user->hasPermission('manage_tickets')) {
            return true;
        }

        // Users can view their own personal folders
        if ($folder->user_id == $user->id) {
            return true;
        }

        // Users can view folders for mailboxes they have access to
        $hasAccess = $user->mailboxes()->where('mailboxes.id', $folder->mailbox_id)->exists();

        return $hasAccess;
    }
}
