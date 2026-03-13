<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

/**
 * ClientCreatedData
 *
 * Immutable data transfer object for ClientCreated event.
 * Contains all relevant data when a new client is created.
 */
final readonly class ClientCreatedData
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public int $clientId,
        public string $name,
        public ?int $companyId,
        public string $billingEmail,
        public array $metadata,
    ) {}
}
