<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

/**
 * Action1DeviceDiscoveredData - Immutable DTO for Action1DeviceDiscovered event
 * 
 * Represents device data discovered from Action1 RMM.
 * Uses readonly properties to prevent mutation after creation.
 */
final readonly class Action1DeviceDiscoveredData
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public int $clientId,
        public string $hostname,
        public string $osType, // 'windows', 'macos', 'linux'
        public string $osVersion,
        public string $action1DeviceId,
        public bool $isOnline,
        public ?string $assignedUserEmail,
        public array $metadata,
    ) {}
    
    /**
     * Factory method for backward compatibility
     * 
     * @param array<string, mixed> $data Raw array data
     * @phpstan-param array{
     *     client_id?: int,
     *     hostname?: string,
     *     os_type?: string, osType?: string,
     *     os_version?: string, osVersion?: string,
     *     action1_device_id?: string, action1DeviceId?: string,
     *     is_online?: bool, isOnline?: bool,
     *     assigned_user_email?: string|null, assignedUserEmail?: string|null,
     *     metadata?: array<string, mixed>,
     * } $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            clientId: $data['client_id'] ?? 0,
            hostname: $data['hostname'] ?? '',
            osType: $data['os_type'] ?? $data['osType'] ?? '',
            osVersion: $data['os_version'] ?? $data['osVersion'] ?? '',
            action1DeviceId: $data['action1_device_id'] ?? $data['action1DeviceId'] ?? '',
            isOnline: $data['is_online'] ?? $data['isOnline'] ?? false,
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
            'hostname' => $this->hostname,
            'os_type' => $this->osType,
            'os_version' => $this->osVersion,
            'action1_device_id' => $this->action1DeviceId,
            'is_online' => $this->isOnline,
            'assigned_user_email' => $this->assignedUserEmail,
            'metadata' => $this->metadata,
        ];
    }
}
