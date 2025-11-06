<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\CustomerCreatedConversation;
use App\Jobs\SendAutoReply as SendAutoReplyJob;
use App\Models\Conversation;
use App\Models\Mailbox;
use App\Models\SendLog;
use Illuminate\Support\Facades\Log;

class SendAutoReply
{
    const CHECK_PERIOD = 180; // minutes

    /**
     * Handle the event.
     */
    public function handle(CustomerCreatedConversation $event): void
    {
        Log::info('SendAutoReply listener triggered', [
            'conversation_id' => $event->conversation->id,
            'customer_email' => $event->customer->getMainEmail(),
            'mailbox_id' => $event->conversation->mailbox_id,
        ]);

        $conversation = $event->conversation;
        $thread = $event->thread;
        $mailbox = $conversation->mailbox;
        $customer = $event->customer;

        // Do not send auto reply if imported
        if ($conversation->imported) {
            Log::debug('Skipping auto-reply for imported conversation', [
                'conversation_id' => $conversation->id,
            ]);

            return;
        }

        // Check if mailbox has auto-reply enabled
        if (! $mailbox->auto_reply_enabled) {
            Log::debug('Auto-reply disabled for mailbox', [
                'mailbox_id' => $mailbox->id,
            ]);

            return;
        }

        // Do not send auto reply to auto responders
        if ($thread->isAutoResponder()) {
            Log::debug('Skipping auto-reply for auto-responder', [
                'conversation_id' => $conversation->id,
            ]);

            return;
        }

        // Do not send auto replies to bounces
        if ($thread->isBounce()) {
            Log::debug('Skipping auto-reply for bounce', [
                'conversation_id' => $conversation->id,
            ]);

            return;
        }

        // Do not send auto reply to spam messages
        if ($conversation->status == 3) { // STATUS_SPAM
            Log::debug('Skipping auto-reply for spam conversation', [
                'conversation_id' => $conversation->id,
            ]);

            return;
        }

        // Rate limiting: prevent infinite loops
        // We can not send auto reply to incoming bounce messages, as it will lead to the infinite loop.
        // Bounce detection can not be 100% reliable.
        // So to prevent infinite loop, we are checking number of auto replies sent to the customer recently.
        $createdAt = now()->subMinutes(self::CHECK_PERIOD);

        $autoRepliesSent = SendLog::where('customer_id', $customer->id)
            ->where('mail_type', 3) // SendLog::MAIL_TYPE_AUTO_REPLY
            ->where('created_at', '>', $createdAt)
            ->count();

        if ($autoRepliesSent >= 10) {
            Log::warning('Auto-reply rate limit exceeded (10)', [
                'customer_id' => $customer->id,
                'auto_replies_sent' => $autoRepliesSent,
            ]);

            return;
        }

        if ($autoRepliesSent >= 2) {
            // Find conversations from this customer with same subject
            $prevConversations = Conversation::select('subject', 'id')
                ->where('customer_id', $customer->id)
                ->where('created_at', '>', $createdAt)
                ->get();

            foreach ($prevConversations as $prevConv) {
                if ($prevConv->subject == $conversation->subject && $prevConv->id != $conversation->id) {
                    Log::debug('Skipping auto-reply - duplicate subject detected', [
                        'conversation_id' => $conversation->id,
                        'duplicate_conversation_id' => $prevConv->id,
                    ]);

                    return;
                }
            }
        }

        // Do not send autoreplies to own mailboxes
        if ($conversation->customer_email) {
            $isInternalEmail = Mailbox::where('email', $conversation->customer_email)->exists();
            if ($isInternalEmail) {
                Log::debug('Skipping auto-reply to internal mailbox', [
                    'customer_email' => $conversation->customer_email,
                ]);

                return;
            }
        }

        // Dispatch the job to send the auto-reply
        SendAutoReplyJob::dispatch($conversation, $thread, $mailbox, $customer)
            ->onQueue('emails');

        Log::info('SendAutoReply job dispatched', [
            'conversation_id' => $conversation->id,
            'customer_email' => $customer->getMainEmail(),
        ]);
    }
}
