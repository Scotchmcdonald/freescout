<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

/**
 * ClientUpdatedData
 * 
 * Immutable data transfer object for ClientUpdated event.
 * Contains client ID and the fields that were changed.
 */
final readonly class ClientUpdatedData
{
    /**
     * @param list<string> $changedFields
     * @param array<string, mixed> $oldValues
     */
    public function __construct(
        public int $clientId,
        public array $changedFields,
        public array $oldValues,
    ) {}
}
