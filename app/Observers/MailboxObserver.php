<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Folder;
use App\Models\Mailbox;

class MailboxObserver
{
    /**
     * Handle the Mailbox "created" event.
     */
    public function created(Mailbox $mailbox): void
    {
        // Create default folders
        $this->createDefaultFolders($mailbox);
    }

    /**
     * Handle the Mailbox "deleting" event.
     */
    public function deleting(Mailbox $mailbox): void
    {
        // Delete all conversations
        $mailbox->conversations()->delete();

        // Delete all folders
        $mailbox->folders()->delete();
    }

    /**
     * Create default folders for a mailbox.
     */
    private function createDefaultFolders(Mailbox $mailbox): void
    {
        $folders = [
            ['type' => Folder::TYPE_INBOX, 'name' => 'Inbox'],
            ['type' => Folder::TYPE_ASSIGNED, 'name' => 'Assigned'],
            ['type' => Folder::TYPE_DRAFTS, 'name' => 'Drafts'],
            ['type' => Folder::TYPE_SPAM, 'name' => 'Spam'],
            ['type' => Folder::TYPE_TRASH, 'name' => 'Trash'],
        ];

        foreach ($folders as $folderData) {
            Folder::create([
                'mailbox_id' => $mailbox->id,
                'type' => $folderData['type'],
                'name' => $folderData['name'],
            ]);
        }
    }
}
