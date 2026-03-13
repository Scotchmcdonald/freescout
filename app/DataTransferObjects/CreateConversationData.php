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
        $subject = $validated['subject'];
        if (! is_string($subject)) {
            $subject = '';
        }

        $body = $validated['body'];
        if (! is_string($body)) {
            $body = '';
        }

        $to = $validated['to'];
        if (! is_array($to)) {
            $to = [];
        }
        $to = array_filter($to, 'is_string');

        $customerId = $validated['customer_id'] ?? null;
        if ($customerId !== null && ! is_int($customerId)) {
            $customerId = is_numeric($customerId) ? (int) $customerId : null;
        }

        $customerEmail = $validated['customer_email'] ?? null;
        if ($customerEmail !== null && ! is_string($customerEmail)) {
            $customerEmail = null;
        }

        $customerFirstName = $validated['customer_first_name'] ?? null;
        if ($customerFirstName !== null && ! is_string($customerFirstName)) {
            $customerFirstName = null;
        }

        $customerLastName = $validated['customer_last_name'] ?? null;
        if ($customerLastName !== null && ! is_string($customerLastName)) {
            $customerLastName = null;
        }

        $assignTo = $validated['assign_to'] ?? null;
        if ($assignTo !== null && ! is_int($assignTo)) {
            $assignTo = is_numeric($assignTo) ? (int) $assignTo : null;
        }

        $statusValue = $validated['status'] ?? null;
        $status = null;
        if ($statusValue !== null && (is_int($statusValue) || is_string($statusValue) || is_float($statusValue))) {
            $status = ConversationStatus::tryFrom((int) $statusValue);
        }

        return new self(
            subject: $subject,
            body: $body,
            to: $to,
            customerId: $customerId,
            customerEmail: $customerEmail,
            customerFirstName: $customerFirstName,
            customerLastName: $customerLastName,
            status: $status,
            assignTo: $assignTo,
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
