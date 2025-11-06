<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Mailbox;
use App\Models\User;

class MailboxPolicy
{
    public const ACCESS_VIEW = 10;
    public const ACCESS_REPLY = 20;
    public const ACCESS_ADMIN = 30;

    /**
     * Determine whether the user can view any mailboxes.
     */
    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view mailboxes
    }

    /**
     * Determine whether the user can view the mailbox.
     */
    public function view(User $user, Mailbox $mailbox): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $pivot = $user->mailboxes->find($mailbox->id)?->pivot;

        return $pivot && $pivot->access >= self::ACCESS_VIEW;
    }

    /**
     * Determine whether the user can create mailboxes.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the mailbox.
     */
    public function update(User $user, Mailbox $mailbox): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $pivot = $user->mailboxes->find($mailbox->id)?->pivot;

        return $pivot && $pivot->access >= self::ACCESS_ADMIN;
    }

    /**
     * Determine whether the user can delete the mailbox.
     */
    public function delete(User $user, Mailbox $mailbox): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the mailbox.
     */
    public function restore(User $user, Mailbox $mailbox): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the mailbox.
     */
    public function forceDelete(User $user, Mailbox $mailbox): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can reply to conversations in the mailbox.
     */
    public function reply(User $user, Mailbox $mailbox): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $pivot = $user->mailboxes->find($mailbox->id)?->pivot;

        return $pivot && $pivot->access >= self::ACCESS_REPLY;
    }
}
