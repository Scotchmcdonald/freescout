<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Conversation;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Action class for merging conversations.
 * 
 * Encapsulates the logic for merging two conversations into one,
 * including thread migration, metadata updates, and notifications.
 */
class MergeConversationsAction
{
    /**
     * Merge one conversation into another.
     *
     * @return array{success: bool, message: string, conversation?: Conversation}
     */
    public function execute(Conversation $source, Conversation $target, User $user): array
    {
        // Validate conversations can be merged
        $validation = $this->validateMerge($source, $target);
        if (!$validation['valid']) {
            return ['success' => false, 'message' => $validation['message']];
        }

        try {
            return DB::transaction(function () use ($source, $target, $user): array {
                // Move threads from source to target
                $this->migrateThreads($source, $target);

                // Update target conversation metadata
                $this->updateTargetMetadata($source, $target);

                // Create merge note thread
                $this->createMergeNote($source, $target, $user);

                // Archive source conversation by setting state to deleted
                // STATE_DELETED = 3 (defined in Conversation model)
                $source->update([
                    'state' => Conversation::STATE_DELETED,
                    'merged_into_id' => $target->id,
                ]);

                // Fire event
                \Eventy::action('conversation.merged', $target, $source, $user);

                return [
                    'success' => true,
                    'message' => "Conversation #{$source->number} merged into #{$target->number}",
                    'conversation' => $target->fresh(),
                ];
            });
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to merge conversations: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Validate that two conversations can be merged.
     *
     * @return array{valid: bool, message: string}
     */
    private function validateMerge(Conversation $source, Conversation $target): array
    {
        if ($source->id === $target->id) {
            return ['valid' => false, 'message' => 'Cannot merge a conversation with itself'];
        }

        if ($source->mailbox_id !== $target->mailbox_id) {
            return ['valid' => false, 'message' => 'Conversations must be in the same mailbox'];
        }

        if ($source->state === Conversation::STATE_DELETED) {
            return ['valid' => false, 'message' => 'Source conversation is deleted'];
        }

        if ($target->state === Conversation::STATE_DELETED) {
            return ['valid' => false, 'message' => 'Target conversation is deleted'];
        }

        return ['valid' => true, 'message' => ''];
    }

    /**
     * Move all threads from source to target conversation.
     */
    private function migrateThreads(Conversation $source, Conversation $target): void
    {
        Thread::where('conversation_id', $source->id)
            ->update(['conversation_id' => $target->id]);
    }

    /**
     * Update target conversation metadata after merge.
     */
    private function updateTargetMetadata(Conversation $source, Conversation $target): void
    {
        // Count threads
        $threadCount = $target->threads()->count();

        // Get latest reply time
        $latestReply = $target->threads()
            ->whereIn('type', [Thread::TYPE_MESSAGE, Thread::TYPE_CUSTOMER])
            ->orderByDesc('created_at')
            ->first();

        $target->update([
            'threads_count' => $threadCount,
            'last_reply_at' => $latestReply?->created_at ?? $target->last_reply_at,
        ]);
    }

    /**
     * Create a note thread documenting the merge.
     */
    private function createMergeNote(Conversation $source, Conversation $target, User $user): void
    {
        Thread::create([
            'conversation_id' => $target->id,
            'user_id' => $user->id,
            'type' => Thread::TYPE_NOTE,
            'status' => 1,
            'state' => Thread::STATE_PUBLISHED,
            'body' => sprintf(
                'Merged conversation #%d into this conversation.',
                $source->number
            ),
            'source_via' => 1,
            'source_type' => 2,
            'created_by_user_id' => $user->id,
        ]);
    }

    /**
     * Search for conversations that can be merged with the given one.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Conversation>
     */
    public function searchMergeCandidates(
        Conversation $conversation,
        string $query,
        int $limit = 10
    ): \Illuminate\Database\Eloquent\Collection {
        return Conversation::where('mailbox_id', $conversation->mailbox_id)
            ->where('id', '!=', $conversation->id)
            ->where('state', '!=', Conversation::STATE_DELETED)
            ->where(function ($q) use ($query) {
                $q->where('subject', 'like', "%{$query}%")
                    ->orWhere('number', 'like', "%{$query}%")
                    ->orWhere('customer_email', 'like', "%{$query}%");
            })
            ->with(['customer', 'user'])
            ->orderByDesc('last_reply_at')
            ->limit($limit)
            ->get();
    }
}
