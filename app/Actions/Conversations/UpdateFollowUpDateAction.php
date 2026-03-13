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
     * @param  array<string, mixed>  $data
     */
    public function execute(Conversation $conversation, array $data): void
    {
        $statusValue = isset($data['status']) && is_numeric($data['status']) ? intval($data['status']) : null;
        $typeValue = isset($data['type']) && is_numeric($data['type']) ? intval($data['type']) : null;

        $status = $statusValue !== null ? ConversationStatus::tryFrom($statusValue) : null;
        $type = $typeValue !== null ? ThreadType::tryFrom($typeValue) : null;

        $isClosing = $status === ConversationStatus::Closed;
        $isNote = $type === ThreadType::NOTE;

        if ($isClosing) {
            // Clear follow-up when closing
            $conversation->update([
                'follow_up_date' => null,
                'follow_up_reminded_at' => null,
            ]);
        } elseif (! $isNote) {
            // Set follow-up for replies (not internal notes)
            $defaultDays = config('app.default_follow_up_days', 3);
            $followUpDate = $data['follow_up_date']
                ?? now()->addDays(is_int($defaultDays) ? $defaultDays : 3)->startOfDay();

            $conversation->update([
                'follow_up_date' => $followUpDate,
                'follow_up_reminded_at' => null,
            ]);
        }
    }
}
