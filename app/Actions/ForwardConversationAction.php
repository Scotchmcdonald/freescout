<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Conversation;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Action class for forwarding a conversation.
 *
 * Encapsulates the business logic for creating a new conversation
 * from an existing thread, including cloning attachments.
 */
final class ForwardConversationAction
{
    /**
     * Execute the forward action.
     *
     * @param  Conversation  $sourceConversation  The original conversation
     * @param  Thread  $sourceThread  The thread to forward
     * @param  User  $user  The user performing the forward
     * @param  array<string, mixed>  $options  Forward options
     * @return Conversation The new forwarded conversation
     *
     * @throws \Exception If forward creation fails
     */
    public function execute(
        Conversation $sourceConversation,
        Thread $sourceThread,
        User $user,
        array $options = []
    ): Conversation {
        return DB::transaction(function () use ($sourceConversation, $sourceThread, $user, $options) {
            // Generate new conversation number
            $maxNumber = Conversation::max('number');
            $currentNumber = is_numeric($maxNumber) ? (int) $maxNumber : 0;

            // Create the new conversation
            $newConversation = Conversation::create([
                'number' => $currentNumber + 1,
                'type' => 1, // Email
                'subject' => 'Fwd: '.$sourceConversation->subject,
                'mailbox_id' => $options['mailbox_id'] ?? $sourceConversation->mailbox_id,
                'folder_id' => $this->getDefaultFolderId($sourceConversation),
                'source_via' => 1, // User
                'source_type' => 2, // Web
                'status' => 1, // Active
                'state' => 1, // Draft
                'user_id' => $user->id,
                'created_by_user_id' => $user->id,
                'preview' => '',
            ]);

            // Create the forward thread as a draft
            $newThread = Thread::create([
                'conversation_id' => $newConversation->id,
                'user_id' => $user->id,
                'type' => Thread::TYPE_DRAFT,
                'status' => 1,
                'state' => Thread::STATE_DRAFT,
                'body' => $this->buildForwardBody($sourceConversation, $sourceThread),
                'from' => $sourceConversation->mailbox->email ?? '',
                'to' => json_encode($options['to'] ?? []),
                'cc' => json_encode($options['cc'] ?? []),
                'bcc' => json_encode($options['bcc'] ?? []),
                'source_via' => 1, // User
                'source_type' => 2, // Web
                'created_by_user_id' => $user->id,
                'has_attachments' => $sourceThread->has_attachments,
            ]);

            // Clone attachments if any
            if ($sourceThread->has_attachments) {
                $this->cloneAttachments($sourceThread, $newThread);
            }

            // Fire event hook
            \Eventy::action('conversation.forwarded', $newConversation, $sourceConversation, $user);

            return $newConversation;
        });
    }

    /**
     * Get the default folder ID for the conversation.
     */
    private function getDefaultFolderId(Conversation $conversation): int
    {
        $mailbox = $conversation->mailbox;
        if ($mailbox === null) {
            return 1;
        }

        return $mailbox->folders()
            ->where('type', 1) // Inbox
            ->first()->id ?? 1;
    }

    /**
     * Build the forwarded email body with proper formatting.
     */
    private function buildForwardBody(Conversation $conversation, Thread $thread): string
    {
        $separator = '---------- Forwarded message ---------';
        $fromLine = 'From: '.($thread->from ?? 'Unknown');
        $dateLine = 'Date: '.($thread->created_at?->format('D, M j, Y \a\t g:i A') ?? 'Unknown');
        $subjectLine = 'Subject: '.$conversation->subject;

        // Decode the to field if it's a JSON string
        $toRecipients = $thread->to;
        if (! is_array($toRecipients)) {
            $toRecipients = [];
        }
        $toLine = 'To: '.implode(', ', $toRecipients);

        return "<br><br>{$separator}<br>{$fromLine}<br>{$dateLine}<br>{$subjectLine}<br>{$toLine}<br><br>".$thread->body;
    }

    /**
     * Clone attachments from source thread to new thread.
     */
    private function cloneAttachments(Thread $sourceThread, Thread $newThread): void
    {
        foreach ($sourceThread->attachments as $attachment) {
            $newAttachment = $attachment->replicate();
            $newAttachment->thread_id = $newThread->id;
            $newAttachment->save();
        }
    }
}
