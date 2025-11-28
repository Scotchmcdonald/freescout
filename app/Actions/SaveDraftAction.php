<?php

declare(strict_types=1);

namespace App\Actions;

use App\DataTransferObjects\DraftData;
use App\Models\Conversation;
use App\Models\Thread;

/**
 * Action class for saving conversation drafts.
 * 
 * Encapsulates the logic for creating and updating drafts,
 * including finding existing drafts and handling attachments.
 */
class SaveDraftAction
{
    /**
     * Save or update a draft for a conversation.
     *
     * @return array{success: bool, draft: Thread, message: string}
     */
    public function execute(DraftData $data, Conversation $conversation): array
    {
        // Find existing draft for this user
        $existingDraft = $this->findExistingDraft($conversation, $data->userId);

        if ($existingDraft) {
            return $this->updateDraft($existingDraft, $data);
        }

        return $this->createDraft($data, $conversation);
    }

    /**
     * Find an existing draft for the user.
     */
    private function findExistingDraft(Conversation $conversation, int $userId): ?Thread
    {
        return $conversation->threads()
            ->where('user_id', $userId)
            ->where('type', Thread::TYPE_DRAFT)
            ->where('state', Thread::STATE_DRAFT)
            ->first();
    }

    /**
     * Update an existing draft.
     *
     * @return array{success: bool, draft: Thread, message: string}
     */
    private function updateDraft(Thread $draft, DraftData $data): array
    {
        $updateData = ['body' => $data->body];

        if ($data->to !== null) {
            $updateData['to'] = $data->to;
        }
        if ($data->cc !== null) {
            $updateData['cc'] = $data->cc;
        }
        if ($data->bcc !== null) {
            $updateData['bcc'] = $data->bcc;
        }

        $draft->update($updateData);

        return [
            'success' => true,
            'draft' => $draft->fresh(),
            'message' => 'Draft updated',
        ];
    }

    /**
     * Create a new draft.
     *
     * @return array{success: bool, draft: Thread, message: string}
     */
    private function createDraft(DraftData $data, Conversation $conversation): array
    {
        $draft = Thread::create([
            'conversation_id' => $data->conversationId,
            'user_id' => $data->userId,
            'type' => Thread::TYPE_DRAFT,
            'status' => 1,
            'state' => Thread::STATE_DRAFT,
            'body' => $data->body,
            'from' => $conversation->mailbox?->email ?? '',
            'to' => $data->to ?? json_encode([$conversation->customer_email ?? '']),
            'cc' => $data->cc,
            'bcc' => $data->bcc,
            'source_via' => 1,
            'source_type' => 2,
        ]);

        return [
            'success' => true,
            'draft' => $draft,
            'message' => 'Draft saved',
        ];
    }

    /**
     * Discard a draft for a user.
     *
     * @return array{success: bool, message: string}
     */
    public function discard(Conversation $conversation, int $userId): array
    {
        $deleted = $conversation->threads()
            ->where('user_id', $userId)
            ->where('type', Thread::TYPE_DRAFT)
            ->where('state', Thread::STATE_DRAFT)
            ->delete();

        return [
            'success' => true,
            'message' => $deleted > 0 ? 'Draft discarded' : 'No draft found',
        ];
    }
}
