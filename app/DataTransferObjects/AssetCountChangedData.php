<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

/**
 * AssetCountChangedData - Immutable DTO for AssetCountChanged event
 *
 * Represents changes to asset counts for billing integration.
 * Used to trigger PIB entitlement recalculations.
 */
final readonly class AssetCountChangedData
{
    public function __construct(
        public int $clientId,
        public string $assetType, // 'chromebook', 'windows', 'macos', 'linux', etc.
        public int $previousCount,
        public int $newCount,
        public string $changeReason, // 'asset_added', 'asset_removed', 'asset_reassigned', 'sync_reconciliation'
        public ?int $assetId = null, // Optional: specific asset that triggered the change
    ) {}

    /**
     * Factory method for array construction
     *
     * @param  array<string, mixed>  $data
     *
     * @phpstan-param array{
     *     client_id?: int, clientId?: int,
     *     asset_type?: string, assetType?: string,
     *     previous_count?: int, previousCount?: int,
     *     new_count?: int, newCount?: int,
     *     change_reason?: string, changeReason?: string,
     *     asset_id?: int|null, assetId?: int|null,
     * } $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            clientId: $data['client_id'] ?? $data['clientId'] ?? 0,
            assetType: $data['asset_type'] ?? $data['assetType'] ?? '',
            previousCount: $data['previous_count'] ?? $data['previousCount'] ?? 0,
            newCount: $data['new_count'] ?? $data['newCount'] ?? 0,
            changeReason: $data['change_reason'] ?? $data['changeReason'] ?? '',
            assetId: $data['asset_id'] ?? $data['assetId'] ?? null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'client_id' => $this->clientId,
            'asset_type' => $this->assetType,
            'previous_count' => $this->previousCount,
            'new_count' => $this->newCount,
            'change_reason' => $this->changeReason,
            'asset_id' => $this->assetId,
        ];
    }

    /**
     * Calculate the delta (change in count)
     */
    public function getDelta(): int
    {
        return $this->newCount - $this->previousCount;
    }

    /**
     * Check if count increased
     */
    public function isIncrease(): bool
    {
        return $this->newCount > $this->previousCount;
    }

    /**
     * Check if count decreased
     */
    public function isDecrease(): bool
    {
        return $this->newCount < $this->previousCount;
    }
}
