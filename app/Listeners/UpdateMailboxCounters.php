<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\ConversationStatusChanged;
use App\Events\ConversationUserChanged;

class UpdateMailboxCounters
{
    /**
     * Handle the event.
     *
     * Updates mailbox statistics when conversation status or assignment changes.
     */
    public function handle(ConversationStatusChanged|ConversationUserChanged $event): void
    {
        // Update mailbox folder counters
        if (method_exists($event->conversation->mailbox, 'updateFoldersCounters')) {
            $event->conversation->mailbox->updateFoldersCounters();
        }
    }
}
