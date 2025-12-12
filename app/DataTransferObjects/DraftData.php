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
        $body = $data['body'] ?? '';
        if (!is_string($body) && !is_int($body) && !is_float($body)) {
            $body = '';
        }

        return new self(
            conversationId: $conversationId,
            userId: $userId,
            body: (string) $body,
            to: isset($data['to']) && (is_string($data['to']) || is_int($data['to']) || is_float($data['to'])) ? (string) $data['to'] : null,
            cc: isset($data['cc']) && (is_string($data['cc']) || is_int($data['cc']) || is_float($data['cc'])) ? (string) $data['cc'] : null,
            bcc: isset($data['bcc']) && (is_string($data['bcc']) || is_int($data['bcc']) || is_float($data['bcc'])) ? (string) $data['bcc'] : null,
            attachmentIds: isset($data['attachment_ids']) && is_array($data['attachment_ids']) ? array_map(fn ($id) => is_numeric($id) ? intval($id) : 0, $data['attachment_ids']) : [],
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
