<?php

declare(strict_types=1);

namespace App\Actions\Conversations;

use App\Enums\ConversationStatus;
use App\Enums\ThreadType;
use App\Models\Conversation;

/**
 * Handle follow-up date logic for conversations.
 */
class UpdateFollowUpDateAction
{
    /**
     * @param array{
     *   status?: int,
     *   type?: int,
     *   follow_up_date?: string
     * } $data
     */
    public function execute(Conversation $conversation, array $data): void
    {
        $status = isset($data['status']) ? ConversationStatus::tryFrom((int)$data['status']) : null;
        $type = isset($data['type']) ? ThreadType::tryFrom((int)$data['type']) : null;
        
        $isClosing = $status === ConversationStatus::CLOSED;
        $isNote = $type === ThreadType::NOTE;

        if ($isClosing) {
            // Clear follow-up when closing
            $conversation->update([
                'follow_up_date' => null,
                'follow_up_reminded_at' => null,
            ]);
        } elseif (!$isNote) {
            // Set follow-up for replies (not internal notes)
            $followUpDate = $data['follow_up_date'] 
                ?? now()->addDays(config('app.default_follow_up_days', 3))->startOfDay();

            $conversation->update([
                'follow_up_date' => $followUpDate,
                'follow_up_reminded_at' => null,
            ]);
        }
    }
}
