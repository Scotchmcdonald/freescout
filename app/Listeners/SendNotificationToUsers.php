<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\ConversationUserChanged;
use App\Events\CustomerCreatedConversation;
use App\Events\CustomerReplied;
use App\Events\UserAddedNote;
use App\Events\UserCreatedConversation;
use App\Events\UserReplied;

/**
 * Send notifications to users by email and in browser.
 */
class SendNotificationToUsers
{
    /**
     * Handle the event.
     *
     * This listener is responsible for notifying users of conversation changes.
     * The actual notification logic will be implemented when notification jobs are available.
     */
    public function handle(
        UserReplied|UserAddedNote|UserCreatedConversation|CustomerCreatedConversation|ConversationUserChanged|CustomerReplied $event
    ): void {
        $event_type = null;
        $caused_by_user_id = null;

        // Detect event type by event class
        switch (get_class($event)) {
            case UserReplied::class:
                $caused_by_user_id = $event->thread->created_by_user_id ?? null;
                // EVENT_TYPE_USER_REPLIED = 5
                $event_type = 5;
                break;
            case UserAddedNote::class:
                $caused_by_user_id = $event->thread->created_by_user_id ?? null;
                // When conversation is forwarded only notification
                // about child forward conversation is sent.
                if (! method_exists($event->thread, 'isForward') || ! $event->thread->isForward()) {
                    // EVENT_TYPE_USER_ADDED_NOTE = 6
                    $event_type = 6;
                }
                break;
            case UserCreatedConversation::class:
                $caused_by_user_id = $event->conversation->created_by_user_id ?? null;
                // EVENT_TYPE_NEW = 1
                $event_type = 1;
                break;
            case CustomerCreatedConversation::class:
                // Do not send notification if conversation is spam.
                // STATUS_SPAM = 3
                if ($event->conversation->status != 3) {
                    // EVENT_TYPE_NEW = 1
                    $event_type = 1;
                }
                break;
            case ConversationUserChanged::class:
                $caused_by_user_id = $event->user->id;
                // EVENT_TYPE_ASSIGNED = 2
                $event_type = 2;
                break;
            case CustomerReplied::class:
                // EVENT_TYPE_CUSTOMER_REPLIED = 4
                $event_type = 4;
                break;
        }

        if (empty($event->conversation) || ! $event_type) {
            return;
        }

        // Ignore imported threads.
        if (! empty($event->thread) && $event->thread->imported) {
            return;
        }

        // TODO: Implement Subscription::registerEvent when Subscription model is fully implemented
        // \App\Models\Subscription::registerEvent($event_type, $event->conversation, $caused_by_user_id);
    }
}
