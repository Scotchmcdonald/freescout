<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

/**
 * Data Transfer Object for conversation draft operations.
 * 
 * Encapsulates all the data needed for saving and managing drafts,
 * providing type safety and validation.
 */
readonly class DraftData
{
    /**
     * @param int $conversationId The conversation the draft belongs to
     * @param int $userId The user who owns the draft
     * @param string $body The draft content
     * @param string|null $to Recipient email(s) as JSON
     * @param string|null $cc CC recipients as JSON
     * @param string|null $bcc BCC recipients as JSON
     * @param array<int> $attachmentIds IDs of attachments to include
     */
    public function __construct(
        public int $conversationId,
        public int $userId,
        public string $body,
        public ?string $to = null,
        public ?string $cc = null,
        public ?string $bcc = null,
        public array $attachmentIds = [],
    ) {}

    /**
     * Create from validated request data.
     *
     * @param array<string, mixed> $data
     * @param int $conversationId
     * @param int $userId
     */
    public static function fromRequest(array $data, int $conversationId, int $userId): self
    {
        return new self(
            conversationId: $conversationId,
            userId: $userId,
            body: $data['body'] ?? '',
            to: $data['to'] ?? null,
            cc: $data['cc'] ?? null,
            bcc: $data['bcc'] ?? null,
            attachmentIds: $data['attachment_ids'] ?? [],
        );
    }

    /**
     * Check if the draft has meaningful content.
     */
    public function hasContent(): bool
    {
        return trim($this->body) !== '';
    }

    /**
     * Convert to array for model creation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'user_id' => $this->userId,
            'body' => $this->body,
            'to' => $this->to,
            'cc' => $this->cc,
            'bcc' => $this->bcc,
        ];
    }
}
