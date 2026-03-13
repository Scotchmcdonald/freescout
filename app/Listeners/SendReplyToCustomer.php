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
        $thread = $event->thread;

        // Do not send email if this is a Phone conversation and customer has no email.
        if ($conversation->isPhone()) {
            $customer = $conversation->customer;
            if (! $customer || ! $customer->getMainEmail()) {
                return;
            }
        }

        // Ignore imported messages.
        if ($thread->imported) {
            return;
        }

        // Chat conversation handling would go here
        if ($conversation->isChat()) {
            // Chat conversation handling - to be implemented with Helper::backgroundAction
            return;
        }

        if (! $conversation->customer) {
            return;
        }

        // Dispatch SendConversationReplyJob job
        \App\Jobs\SendConversationReplyJob::dispatch($conversation, $thread)
            ->delay(now()->addSeconds(10))
            ->onQueue('emails');
    }
}
