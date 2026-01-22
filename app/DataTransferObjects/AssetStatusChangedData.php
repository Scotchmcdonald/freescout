<?php

namespace App\DataTransferObjects;

/**
 * AssetStatusChangedData - Immutable DTO for AssetStatusChanged event
 * 
 * Represents asset status change events from any source.
 * Uses readonly properties to prevent mutation after creation.
 */
final readonly class AssetStatusChangedData
{
    public function __construct(
        public int $assetId,
        public int $clientId,
        public string $oldStatus,
        public string $newStatus,
        public string $source, // 'GoogleAdmin', 'Action1', 'Manual'
        public ?int $userId,
    ) {}
    
    /**
     * Factory method for backward compatibility
     * 
     * @param array $data Raw array data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            assetId: $data['asset_id'] ?? $data['assetId'],
            clientId: $data['client_id'] ?? $data['clientId'],
            oldStatus: $data['old_status'] ?? $data['oldStatus'],
            newStatus: $data['new_status'] ?? $data['newStatus'],
            source: $data['source'],
            userId: $data['user_id'] ?? $data['userId'] ?? null,
        );
    }
    
    /**
     * Convert to array representation
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'asset_id' => $this->assetId,
            'client_id' => $this->clientId,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'source' => $this->source,
            'user_id' => $this->userId,
        ];
    }
}
