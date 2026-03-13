<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

/**
 * Data Transfer Object for thread/reply data.
 *
 * Provides type-safe wrapper for conversation reply data
 * with immutable readonly properties.
 */
final readonly class ThreadData
{
    /**
     * Create a new ThreadData instance.
     *
     * @param  string  $body  The thread body content
     * @param  int  $type  Thread type (1=reply, 2=note, 5=draft)
     * @param  int|null  $status  Thread status
     * @param  array<string>  $to  Recipient email addresses
     * @param  array<string>  $cc  CC email addresses
     * @param  array<string>  $bcc  BCC email addresses
     * @param  array<string>  $attachmentPaths  Paths to uploaded attachments
     * @param  bool  $isDraft  Whether this is a draft
     */
    public function __construct(
        public string $body,
        public int $type = 1, // Default to reply
        public ?int $status = null,
        public array $to = [],
        public array $cc = [],
        public array $bcc = [],
        public array $attachmentPaths = [],
        public bool $isDraft = false,
    ) {}

    /**
     * Create ThreadData from a request array.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $body = $data['body'] ?? '';
        $type = $data['type'] ?? 1;
        $status = $data['status'] ?? null;

        return new self(
            body: is_string($body) || is_int($body) || is_float($body) ? (string) $body : '',
            type: is_numeric($type) ? intval($type) : 1,
            status: isset($status) && is_numeric($status) ? intval($status) : null,
            to: isset($data['to']) && is_array($data['to']) ? array_map(fn ($v) => is_string($v) || is_int($v) || is_float($v) ? (string) $v : '', $data['to']) : [],
            cc: isset($data['cc']) && is_array($data['cc']) ? array_map(fn ($v) => is_string($v) || is_int($v) || is_float($v) ? (string) $v : '', $data['cc']) : [],
            bcc: isset($data['bcc']) && is_array($data['bcc']) ? array_map(fn ($v) => is_string($v) || is_int($v) || is_float($v) ? (string) $v : '', $data['bcc']) : [],
            attachmentPaths: isset($data['attachments']) && is_array($data['attachments']) ? array_map(fn ($v) => is_string($v) || is_int($v) || is_float($v) ? (string) $v : '', $data['attachments']) : [],
            isDraft: (bool) ($data['is_draft'] ?? false),
        );
    }

    /**
     * Check if this is a note (internal).
     */
    public function isNote(): bool
    {
        return $this->type === 2;
    }

    /**
     * Check if this is a reply (customer-facing).
     */
    public function isReply(): bool
    {
        return $this->type === 1;
    }

    /**
     * Check if this thread has attachments.
     */
    public function hasAttachments(): bool
    {
        return count($this->attachmentPaths) > 0;
    }

    /**
     * Convert to array for model creation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'body' => $this->body,
            'type' => $this->type,
            'status' => $this->status,
            'to' => json_encode($this->to),
            'cc' => json_encode($this->cc),
            'bcc' => json_encode($this->bcc),
            'has_attachments' => $this->hasAttachments(),
        ];
    }
}
