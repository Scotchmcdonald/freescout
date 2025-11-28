<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use App\Enums\ConversationStatus;

/**
 * Data Transfer Object for creating a new conversation.
 *
 * Provides a type-safe wrapper for conversation creation data.
 */
readonly class CreateConversationData
{
    public function __construct(
        public string $subject,
        public string $body,
        /** @var array<string> */
        public array $to,
        public ?int $customerId = null,
        public ?string $customerEmail = null,
        public ?string $customerFirstName = null,
        public ?string $customerLastName = null,
        public ?ConversationStatus $status = null,
        public ?int $assignTo = null,
    ) {}

    /**
     * Create a DTO from a validated request array.
     *
     * @param  array<string, mixed>  $validated
     */
    public static function fromArray(array $validated): self
    {
        return new self(
            subject: $validated['subject'],
            body: $validated['body'],
            to: $validated['to'],
            customerId: $validated['customer_id'] ?? null,
            customerEmail: $validated['customer_email'] ?? null,
            customerFirstName: $validated['customer_first_name'] ?? null,
            customerLastName: $validated['customer_last_name'] ?? null,
            status: isset($validated['status'])
                ? ConversationStatus::tryFrom((int) $validated['status'])
                : null,
            assignTo: $validated['assign_to'] ?? null,
        );
    }

    /**
     * Convert the DTO back to an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'subject' => $this->subject,
            'body' => $this->body,
            'to' => $this->to,
            'customer_id' => $this->customerId,
            'customer_email' => $this->customerEmail,
            'customer_first_name' => $this->customerFirstName,
            'customer_last_name' => $this->customerLastName,
            'status' => $this->status?->value,
            'assign_to' => $this->assignTo,
        ];
    }
}
