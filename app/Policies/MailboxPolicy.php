<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Mailbox;
use App\Models\User;

/**
 * MailboxPolicy — governs mailbox operations.
 *
 * Uses a 3-tier access model: VIEW (10), REPLY (20), ADMIN (30).
 * Admin bypass handled by Gate::before. Uses hasPermission()
 * for manage_settings (CUD) to allow flexible role assignment.
 */
class MailboxPolicy
{
    public const ACCESS_VIEW = 10;
    public const ACCESS_REPLY = 20;
    public const ACCESS_ADMIN = 30;

    /**
     * Determine whether the user can view any mailboxes.
     */
    public function viewAny(?User $user): bool
    {
        return $user !== null; // All authenticated users can view mailboxes
    }

    /**
     * Determine whether the user can view the mailbox.
     */
    public function view(?User $user, Mailbox $mailbox): bool
    {
        if ($user === null) {
            return false;
        }

        if ($user->hasPermission('manage_settings')) {
            return true;
        }

        /** @var (Mailbox&object{pivot: \App\Models\MailboxUser})|null $mailboxWithPivot */
        $mailboxWithPivot = $user->mailboxes->find($mailbox->id);

        return $mailboxWithPivot && $mailboxWithPivot->pivot->access >= self::ACCESS_VIEW;
    }

    /**
     * Determine whether the user can create mailboxes.
     */
    public function create(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        return $user->hasPermission('manage_settings');
    }

    /**
     * Determine whether the user can update the mailbox.
     */
    public function update(?User $user, Mailbox $mailbox): bool
    {
        if ($user === null) {
            return false;
        }

        if ($user->hasPermission('manage_settings')) {
            return true;
        }

        /** @var (Mailbox&object{pivot: \App\Models\MailboxUser})|null $mailboxWithPivot */
        $mailboxWithPivot = $user->mailboxes->find($mailbox->id);

        return $mailboxWithPivot && $mailboxWithPivot->pivot->access >= self::ACCESS_ADMIN;
    }

    /**
     * Determine whether the user can delete the mailbox.
     */
    public function delete(?User $user, Mailbox $mailbox): bool
    {
        if ($user === null) {
            return false;
        }

        return $user->hasPermission('manage_settings');
    }

    /**
     * Determine whether the user can restore the mailbox.
     */
    public function restore(?User $user, Mailbox $mailbox): bool
    {
        if ($user === null) {
            return false;
        }

        return $user->hasPermission('manage_settings');
    }

    /**
     * Determine whether the user can permanently delete the mailbox.
     */
    public function forceDelete(?User $user, Mailbox $mailbox): bool
    {
        if ($user === null) {
            return false;
        }

        return $user->hasPermission('manage_settings');
    }

    /**
     * Determine whether the user can reply to conversations in the mailbox.
     */
    public function reply(?User $user, Mailbox $mailbox): bool
    {
        if ($user === null) {
            return false;
        }

        if ($user->hasPermission('manage_tickets')) {
            return true;
        }

        /** @var (Mailbox&object{pivot: \App\Models\MailboxUser})|null $mailboxWithPivot */
        $mailboxWithPivot = $user->mailboxes->find($mailbox->id);

        return $mailboxWithPivot && $mailboxWithPivot->pivot->access >= self::ACCESS_REPLY;
    }

    /**
     * Determine whether the user can administer the mailbox (admin access required).
     * This is used for connection testing and folder retrieval operations.
     */
    public function admin(?User $user, Mailbox $mailbox): bool
    {
        if ($user === null) {
            return false;
        }

        if ($user->hasPermission('manage_settings')) {
            return true;
        }

        /** @var (Mailbox&object{pivot: \App\Models\MailboxUser})|null $mailboxWithPivot */
        $mailboxWithPivot = $user->mailboxes->find($mailbox->id);

        return $mailboxWithPivot && $mailboxWithPivot->pivot->access >= self::ACCESS_ADMIN;
    }
}
