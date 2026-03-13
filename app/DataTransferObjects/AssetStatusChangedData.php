<?php

declare(strict_types=1);

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
        public ?string $oldStatus,
        public string $newStatus,
        public string $source, // 'GoogleAdmin', 'Action1', 'Manual'
        public ?int $userId,
    ) {}

    /**
     * Factory method for backward compatibility
     *
     * @param  array<string, mixed>  $data  Raw array data
     *
     * @phpstan-param array{
     *     asset_id?: int, assetId?: int,
     *     client_id?: int, clientId?: int,
     *     old_status?: string|null, oldStatus?: string|null,
     *     new_status?: string, newStatus?: string,
     *     source?: string,
     *     user_id?: int|null, userId?: int|null,
     * } $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            assetId: $data['asset_id'] ?? $data['assetId'] ?? 0,
            clientId: $data['client_id'] ?? $data['clientId'] ?? 0,
            oldStatus: $data['old_status'] ?? $data['oldStatus'] ?? null,
            newStatus: $data['new_status'] ?? $data['newStatus'] ?? '',
            source: $data['source'] ?? '',
            userId: $data['user_id'] ?? $data['userId'] ?? null,
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
            'asset_id' => $this->assetId,
            'client_id' => $this->clientId,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'source' => $this->source,
            'user_id' => $this->userId,
        ];
    }
}
