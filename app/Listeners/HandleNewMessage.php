<?php

namespace App\Listeners;

use App\Events\NewMessageReceived;

class HandleNewMessage
{
    /**
     * Create the event listener.
     */
    public function __construct() {}

    /**
     * Handle the event.
     */
    public function handle(NewMessageReceived $event): void
    {
        if ($event->conversation->status === \App\Models\Conversation::STATUS_CLOSED) {
            $event->conversation->update(['status' => \App\Models\Conversation::STATUS_ACTIVE]);
        }

        // Notify users
        $eventType = \App\Models\Subscription::EVENT_CUSTOMER_REPLIED_TO_UNASSIGNED;
        if ($event->conversation->user_id) {
            $eventType = \App\Models\Subscription::EVENT_CUSTOMER_REPLIED_TO_ASSIGNED;
        }
        
        \App\Models\Subscription::registerEvent($eventType, $event->conversation);
    }
}
