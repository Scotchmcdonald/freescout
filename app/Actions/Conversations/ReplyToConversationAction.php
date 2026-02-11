<?php

declare(strict_types=1);

namespace App\Actions\Conversations;

use App\Enums\ConversationStatus;
use App\Enums\ThreadType;
use App\Jobs\SendConversationReplyJob;
use App\Models\Conversation;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Single Responsibility Action: Create a reply thread to a conversation.
 * 
 * This encapsulates ALL business logic for replying to conversations,
 * making it testable, reusable, and maintainable.
 */
class ReplyToConversationAction
{
    public function __construct(
        private UpdateFollowUpDateAction $updateFollowUpAction
    ) {}

    /**
     * Execute the reply action.
     *
     * @param array<string, mixed> $data
     */
    public function execute(
        Conversation $conversation,
        User $user,
        array $data
    ): Thread {
        return DB::transaction(function () use ($conversation, $user, $data) {
            // Ensure mailbox is loaded
            if (!$conversation->relationLoaded('mailbox')) {
                $conversation->load('mailbox');
            }

            if (!$conversation->mailbox) {
                throw new \RuntimeException('Conversation mailbox not found');
            }

            // Create thread
            $thread = $this->createThread($conversation, $user, $data);

            // Update conversation metadata
            $this->updateConversation($conversation, $user, $data);

            // Handle follow-up dates
            $this->updateFollowUpAction->execute($conversation, $data);

            // Dispatch email for replies (not notes)
            $this->dispatchEmailIfNeeded($conversation, $thread, $data);

            return $thread;
        });
    }

    /**
     * @param array<string, mixed> $data
     */
    private function createThread(Conversation $conversation, User $user, array $data): Thread
    {
        $mailbox = $conversation->mailbox;
        if (!$mailbox || !$mailbox->email) {
            throw new \RuntimeException('Conversation mailbox email not found');
        }
        
        return Thread::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'type' => $data['type'] ?? ThreadType::MESSAGE->value,
            'status' => 1,
            'state' => 2,
            'source_via' => 1,
            'source_type' => 2,
            'body' => $data['body'],
            'from' => $mailbox->email,
            'to' => json_encode([$conversation->customer_email]),
            'created_by_user_id' => $user->id,
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function updateConversation(Conversation $conversation, User $user, array $data): void
    {
        // If no explicit status provided and this is an admin/agent reply (type=1),
        // auto-set status to Pending (2) = "Awaiting Client Response"
        $isReply = ($data['type'] ?? ThreadType::MESSAGE->value) === ThreadType::MESSAGE->value;
        $defaultStatus = $conversation->status;
        if ($isReply && !isset($data['status']) && $user->isAdmin()) {
            $defaultStatus = ConversationStatus::Pending->value;
        }

        $updateData = [
            'threads_count' => $conversation->threads_count + 1,
            'last_reply_at' => now(),
            'status' => $data['status'] ?? $defaultStatus,
        ];

        // Assign to user if unassigned
        if (is_null($conversation->user_id)) {
            $updateData['user_id'] = $user->id;
        }

        $conversation->update($updateData);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function dispatchEmailIfNeeded(Conversation $conversation, Thread $thread, array $data): void
    {
        $isReply = ($data['type'] ?? ThreadType::MESSAGE->value) === ThreadType::MESSAGE->value;
        
        if ($isReply) {
            SendConversationReplyJob::dispatch($conversation, $thread)
                ->delay(now()->addSeconds(10));
        }
    }
}
