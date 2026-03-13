<?php

declare(strict_types=1);

namespace App\Actions;

use App\DataTransferObjects\BulkConversationData;
use App\Models\Conversation;
use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Action class for bulk conversation operations.
 *
 * Encapsulates all bulk operations on conversations including:
 * - Bulk status changes
 * - Bulk assignment changes
 * - Bulk delete/restore
 * - Bulk move to mailbox
 */
class BulkConversationsAction
{
    /**
     * Execute the bulk action.
     *
     * @return array{success: bool, count: int, message?: string}
     */
    public function execute(BulkConversationData $data, User $user): array
    {
        if (empty($data->conversationIds)) {
            return ['success' => false, 'count' => 0, 'message' => 'No conversations selected'];
        }

        if (! $data->isValidAction()) {
            return ['success' => false, 'count' => 0, 'message' => 'Invalid bulk action'];
        }

        // Get conversations and filter by access
        $conversations = $this->getAccessibleConversations($data->conversationIds, $user);

        if ($conversations->isEmpty()) {
            return ['success' => false, 'count' => 0, 'message' => 'No accessible conversations'];
        }

        return match ($data->action) {
            'bulk_change_status' => $this->changeStatus($conversations, $data, $user),
            'bulk_change_user' => $this->changeUser($conversations, $data, $user),
            'bulk_delete' => $this->delete($conversations, $user),
            'bulk_delete_forever' => $this->deleteForever($conversations),
            'bulk_restore' => $this->restore($conversations, $user),
            'bulk_move' => $this->move($conversations, $data, $user),
            default => ['success' => false, 'count' => 0, 'message' => 'Unknown action'],
        };
    }

    /**
     * Get conversations the user has access to.
     *
     * @param  array<int>  $ids
     * @return Collection<int, Conversation>
     */
    private function getAccessibleConversations(array $ids, User $user): Collection
    {
        /** @var Collection<int, Conversation> $conversations */
        $conversations = Conversation::whereIn('id', $ids)->get();

        return $conversations->filter(function (Conversation $conversation) use ($user): bool {
            return $user->isAdmin() || $user->mailboxes->contains($conversation->mailbox_id);
        });
    }

    /**
     * Bulk change conversation status.
     *
     * @param  Collection<int, Conversation>  $conversations
     * @return array{success: bool, count: int, message?: string}
     */
    private function changeStatus(Collection $conversations, BulkConversationData $data, User $user): array
    {
        if ($data->status === null) {
            return ['success' => false, 'count' => 0, 'message' => 'Status is required'];
        }

        // Get the integer value from the enum
        $statusValue = $data->status->value;

        // Reporters cannot close tickets - compare integer values
        if ($user->isReporter() && $statusValue === Conversation::STATUS_CLOSED) {
            return ['success' => false, 'count' => 0, 'message' => 'Reporters cannot close tickets'];
        }

        foreach ($conversations as $conversation) {
            $conversation->changeStatus($statusValue, $user);
        }

        return ['success' => true, 'count' => $conversations->count()];
    }

    /**
     * Bulk change conversation assignee.
     *
     * @param  Collection<int, Conversation>  $conversations
     * @return array{success: bool, count: int}
     */
    private function changeUser(Collection $conversations, BulkConversationData $data, User $user): array
    {
        foreach ($conversations as $conversation) {
            $conversation->changeUser($data->userId, $user);
        }

        return ['success' => true, 'count' => $conversations->count()];
    }

    /**
     * Bulk delete conversations (move to trash).
     *
     * @param  Collection<int, Conversation>  $conversations
     * @return array{success: bool, count: int}
     */
    private function delete(Collection $conversations, User $user): array
    {
        foreach ($conversations as $conversation) {
            $conversation->deleteToFolder($user);
        }

        return ['success' => true, 'count' => $conversations->count()];
    }

    /**
     * Bulk permanently delete conversations.
     *
     * @param  Collection<int, Conversation>  $conversations
     * @return array{success: bool, count: int}
     */
    private function deleteForever(Collection $conversations): array
    {
        foreach ($conversations as $conversation) {
            $conversation->forceDelete();
        }

        return ['success' => true, 'count' => $conversations->count()];
    }

    /**
     * Bulk restore conversations from trash.
     *
     * @param  Collection<int, Conversation>  $conversations
     * @return array{success: bool, count: int}
     */
    private function restore(Collection $conversations, User $user): array
    {
        foreach ($conversations as $conversation) {
            $conversation->restoreFromDeleted($user);
        }

        return ['success' => true, 'count' => $conversations->count()];
    }

    /**
     * Bulk move conversations to another mailbox.
     *
     * @param  Collection<int, Conversation>  $conversations
     * @return array{success: bool, count: int, message?: string}
     */
    private function move(Collection $conversations, BulkConversationData $data, User $user): array
    {
        if ($data->mailboxId === null) {
            return ['success' => false, 'count' => 0, 'message' => 'Target mailbox is required'];
        }

        if (! Mailbox::where('id', $data->mailboxId)->exists()) {
            return ['success' => false, 'count' => 0, 'message' => 'Target mailbox not found'];
        }

        foreach ($conversations as $conversation) {
            $conversation->moveToMailbox($data->mailboxId, $user);
        }

        return ['success' => true, 'count' => $conversations->count()];
    }
}
