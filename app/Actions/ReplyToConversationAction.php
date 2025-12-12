<?php

declare(strict_types=1);

namespace App\Actions;

use App\DataTransferObjects\ThreadData;
use App\Jobs\SendConversationReply;
use App\Models\Conversation;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Action class for replying to a conversation.
 *
 * Encapsulates the business logic for creating a reply thread,
 * updating conversation state, and dispatching notification jobs.
 */
final class ReplyToConversationAction
{
    /**
     * Execute the reply action.
     *
     * @param Conversation $conversation The conversation to reply to
     * @param User $user The user creating the reply
     * @param ThreadData $threadData The reply data
     * @return Thread The created thread
     * @throws \Exception If reply creation fails
     */
    public function execute(Conversation $conversation, User $user, ThreadData $threadData): Thread
    {
        return DB::transaction(function () use ($conversation, $user, $threadData) {
            // Load mailbox if not already loaded
            if (!$conversation->mailbox) {
                $conversation->load('mailbox');
            }

            if (!$conversation->mailbox) {
                throw new \Exception('Mailbox not found for conversation');
            }

            // Create the thread
            $thread = Thread::create([
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
                'type' => $threadData->type,
                'status' => $threadData->status ?? 1,
                'state' => $threadData->isDraft ? Thread::STATE_DRAFT : Thread::STATE_PUBLISHED,
                'source_via' => 1, // User
                'source_type' => 2, // Web
                'body' => $threadData->body,
                'from' => $conversation->mailbox->email,
                'to' => json_encode($threadData->to ?: [$conversation->customer_email]),
                'cc' => json_encode($threadData->cc),
                'bcc' => json_encode($threadData->bcc),
                'has_attachments' => $threadData->hasAttachments(),
                'created_by_user_id' => $user->id,
            ]);

            // Handle attachments if present
            if ($threadData->hasAttachments()) {
                $this->processAttachments($thread, $threadData->attachmentPaths);
            }

            // Update conversation statistics and state
            $updateData = [
                'threads_count' => $conversation->threads_count + 1,
                'last_reply_at' => now(),
            ];

            // Update status if provided
            if ($threadData->status !== null) {
                $updateData['status'] = $threadData->status;
            }

            // Assign to user if unassigned
            if ($conversation->user_id === null) {
                $updateData['user_id'] = $user->id;
            }

            $conversation->update($updateData);

            // Dispatch email notification for replies (not notes or drafts)
            if ($threadData->isReply() && !$threadData->isDraft) {
                SendConversationReply::dispatch($conversation, $thread)
                    ->delay(now()->addSeconds(10));
            }

            // Fire event hook
            \Eventy::action('conversation.reply_added', $conversation, $thread, $user);

            return $thread;
        });
    }

    /**
     * Process and attach files to the thread.
     *
     * @param Thread $thread
     * @param array<string> $attachmentPaths
     */
    private function processAttachments(Thread $thread, array $attachmentPaths): void
    {
        foreach ($attachmentPaths as $path) {
            // Skip empty paths
            if (empty($path)) {
                continue;
            }

            // Create attachment record
            // Note: In a real implementation, this would also handle
            // file metadata and storage
            $thread->attachments()->create([
                'file_name' => basename($path),
                'file_dir' => dirname($path),
                'file_size' => file_exists($path) ? filesize($path) : 0,
                'mime_type' => 'application/octet-stream',
            ]);
        }
    }
}
