<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

/**
 * GoogleUserSyncedData - Immutable DTO for GoogleUserSynced event
 * 
 * Represents user data synchronized from Google Workspace.
 * Uses readonly properties to prevent mutation after creation.
 */
final readonly class GoogleUserSyncedData
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public int $clientId,
        public string $email,
        public string $firstName,
        public string $lastName,
        public string $googleId,
        public bool $suspended,
        public string $orgUnitPath,
        public array $metadata,
    ) {}
    
    /**
     * Factory method for backward compatibility
     * 
     * @param array<string, mixed> $data Raw array data
     * @phpstan-param array{
     *     client_id?: int,
     *     email?: string,
     *     first_name?: string, firstName?: string,
     *     last_name?: string, lastName?: string,
     *     google_id?: string, googleId?: string,
     *     suspended?: bool,
     *     org_unit_path?: string, orgUnitPath?: string,
     *     metadata?: array<string, mixed>,
     * } $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            clientId: $data['client_id'] ?? 0,
            email: $data['email'] ?? '',
            firstName: $data['first_name'] ?? $data['firstName'] ?? '',
            lastName: $data['last_name'] ?? $data['lastName'] ?? '',
            googleId: $data['google_id'] ?? $data['googleId'] ?? '',
            suspended: $data['suspended'] ?? false,
            orgUnitPath: $data['org_unit_path'] ?? $data['orgUnitPath'] ?? '/',
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
            'email' => $this->email,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'google_id' => $this->googleId,
            'suspended' => $this->suspended,
            'org_unit_path' => $this->orgUnitPath,
            'metadata' => $this->metadata,
        ];
    }
}
