<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\UserCreatedConversation;
use App\Events\UserReplied;

class SendReplyToCustomer
{
    /**
     * Handle the event.
     *
     * This listener is responsible for sending email replies to customers.
     * The actual job dispatching will be implemented when SendReplyToCustomer job is available.
     */
    public function handle(UserReplied|UserCreatedConversation $event): void
    {
        $conversation = $event->conversation;

        // Do not send email if this is a Phone conversation and customer has no email.
        if (method_exists($conversation, 'isPhone') && $conversation->isPhone()) {
            if (! $conversation->customer->getMainEmail()) {
                return;
            }
        }

        $replies = method_exists($conversation, 'getReplies') ? $conversation->getReplies() : collect();

        // Ignore imported messages.
        if ($replies && $replies->first() && $replies->first()->imported) {
            return;
        }

        // Remove threads added after this event had fired.
        $thread = $event->thread ?? null;
        if ($thread) {
            foreach ($replies as $i => $reply) {
                if ($reply->id == $thread->id) {
                    break;
                } else {
                    $replies->forget($i);
                }
            }
        }

        // Chat conversation handling would go here
        if (method_exists($conversation, 'isChat') && $conversation->isChat()) {
            // Chat conversation handling - to be implemented with Helper::backgroundAction
            return;
        }

        // TODO: Dispatch SendReplyToCustomer job when it's implemented in batch 4
        // \App\Jobs\SendReplyToCustomer::dispatch($conversation, $replies, $conversation->customer)
        //     ->delay(now()->addSeconds(Conversation::UNDO_TIMOUT))
        //     ->onQueue('emails');
    }
}
