<?php

namespace App\Observers;

use App\Conversation;

class ConversationObserver
{
    /**
     * On create before saving.
     */
    public function creating(Conversation $conversation)
    {
        if ($conversation->source_via == Conversation::PERSON_USER) {
            $conversation->read_by_user = true;
        }
    }

    /**
     * On create.
     */
    public function created(Conversation $conversation)
    {
        // Better to do it manually
        // $conversation->mailbox->updateFoldersCounters();
    }

    /**
     * On conversation delete.
     */
    public function deleting(Conversation $conversation)
    {
        $conversation->threads()->delete();
        $conversation->followers()->delete();

        \Eventy::action('conversation.deleting', $conversation);
    }

    public function updated(Conversation $conversation)
    {
        \Eventy::action('conversation.updated', $conversation);
    }
}
