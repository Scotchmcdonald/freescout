<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use App\Enums\ConversationStatus;

/**
 * Data Transfer Object for bulk conversation operations.
 * 
 * Encapsulates all the data needed for bulk operations on conversations,
 * providing type safety and validation.
 */
readonly class BulkConversationData
{
    /**
     * @param array<int> $conversationIds The IDs of conversations to process
     * @param string $action The bulk action to perform (e.g., 'change_status', 'delete')
     * @param ConversationStatus|null $status New status for status change operations
     * @param int|null $userId New assignee ID for assignment operations
     * @param int|null $mailboxId Target mailbox ID for move operations
     * @param int|null $folderId Target folder ID for folder operations
     */
    public function __construct(
        public array $conversationIds,
        public string $action,
        public ?ConversationStatus $status = null,
        public ?int $userId = null,
        public ?int $mailboxId = null,
        public ?int $folderId = null,
    ) {}

    /**
     * Create from validated request data.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $status = null;
        if (isset($data['status']) && (is_int($data['status']) || is_string($data['status']))) {
            $status = ConversationStatus::tryFrom((int) $data['status']);
        }

        $conversationIds = $data['conversation_ids'] ?? [];
        if (!is_array($conversationIds)) {
            $conversationIds = [];
        }
        $conversationIds = array_map(fn ($id) => is_numeric($id) ? intval($id) : 0, $conversationIds);

        $action = $data['action'] ?? '';
        if (!is_string($action)) {
            $action = '';
        }

        return new self(
            conversationIds: $conversationIds,
            action: $action,
            status: $status,
            userId: isset($data['user_id']) && (is_int($data['user_id']) || is_string($data['user_id'])) ? (int) $data['user_id'] : null,
            mailboxId: isset($data['mailbox_id']) && (is_int($data['mailbox_id']) || is_string($data['mailbox_id'])) ? (int) $data['mailbox_id'] : null,
            folderId: isset($data['folder_id']) && (is_int($data['folder_id']) || is_string($data['folder_id'])) ? (int) $data['folder_id'] : null,
        );
    }

    /**
     * Get the action type without the 'bulk_' prefix.
     */
    public function getActionType(): string
    {
        return str_replace('bulk_', '', $this->action);
    }

    /**
     * Check if this is a valid bulk action.
     */
    public function isValidAction(): bool
    {
        return in_array($this->action, [
            'bulk_change_status',
            'bulk_change_user',
            'bulk_delete',
            'bulk_delete_forever',
            'bulk_restore',
            'bulk_move',
        ]);
    }
}
