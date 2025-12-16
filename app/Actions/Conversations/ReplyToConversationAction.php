<?php

declare(strict_types=1);

namespace App\Actions\Conversations;

use App\Enums\ConversationStatus;
use App\Enums\ThreadType;
use App\Jobs\SendConversationReply;
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
     * @param array{
     *   body: string,
     *   type: int,
     *   status?: int,
     *   follow_up_date?: string
     * } $data
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

    private function createThread(Conversation $conversation, User $user, array $data): Thread
    {
        return Thread::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'type' => $data['type'] ?? ThreadType::MESSAGE->value,
            'status' => 1,
            'state' => 2,
            'source_via' => 1,
            'source_type' => 2,
            'body' => $data['body'],
            'from' => $conversation->mailbox->email,
            'to' => json_encode([$conversation->customer_email]),
            'created_by_user_id' => $user->id,
        ]);
    }

    private function updateConversation(Conversation $conversation, User $user, array $data): void
    {
        $updateData = [
            'threads_count' => $conversation->threads_count + 1,
            'last_reply_at' => now(),
            'status' => $data['status'] ?? $conversation->status,
        ];

        // Assign to user if unassigned
        if (is_null($conversation->user_id)) {
            $updateData['user_id'] = $user->id;
        }

        $conversation->update($updateData);
    }

    private function dispatchEmailIfNeeded(Conversation $conversation, Thread $thread, array $data): void
    {
        $isReply = ($data['type'] ?? ThreadType::MESSAGE->value) === ThreadType::MESSAGE->value;
        
        if ($isReply) {
            SendConversationReply::dispatch($conversation, $thread)
                ->delay(now()->addSeconds(10));
        }
    }
}
