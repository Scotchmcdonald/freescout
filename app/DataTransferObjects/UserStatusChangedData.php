<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

/**
 * UserStatusChangedData
 * 
 * Immutable data transfer object for UserStatusChanged event.
 * Contains user status change information.
 */
final readonly class UserStatusChangedData
{
    public function __construct(
        public int $userId,
        public int $clientId,
        public string $email,
        public string $oldStatus,
        public string $newStatus,
        public ?string $reason,
    ) {}
}
