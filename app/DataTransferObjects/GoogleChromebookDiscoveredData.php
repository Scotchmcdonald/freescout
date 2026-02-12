<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

/**
 * GoogleChromebookDiscoveredData - Immutable DTO for GoogleChromebookDiscovered event
 * 
 * Represents Chromebook data discovered from Google Admin.
 * Uses readonly properties to prevent mutation after creation.
 */
final readonly class GoogleChromebookDiscoveredData
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public int $clientId,
        public string $serialNumber,
        public string $model,
        public string $status,
        public ?string $assignedUserEmail,
        public array $metadata,
    ) {}
    
    /**
     * Factory method for backward compatibility
     * 
     * @param array<string, mixed> $data Raw array data
     * @phpstan-param array{
     *     client_id?: int,
     *     serial_number?: string, serialNumber?: string,
     *     model?: string,
     *     status?: string,
     *     assigned_user_email?: string|null, assignedUserEmail?: string|null,
     *     metadata?: array<string, mixed>,
     * } $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            clientId: $data['client_id'] ?? 0,
            serialNumber: $data['serial_number'] ?? $data['serialNumber'] ?? '',
            model: $data['model'] ?? '',
            status: $data['status'] ?? '',
            assignedUserEmail: $data['assigned_user_email'] ?? $data['assignedUserEmail'] ?? null,
            metadata: $data['metadata'] ?? [],
        );
    }
    
    /**
     * Convert to array representation
     * 
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'client_id' => $this->clientId,
            'serial_number' => $this->serialNumber,
            'model' => $this->model,
            'status' => $this->status,
            'assigned_user_email' => $this->assignedUserEmail,
            'metadata' => $this->metadata,
        ];
    }
}
