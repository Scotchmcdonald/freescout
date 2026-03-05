<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use App\Http\Requests\StoreWebhookChannelRequest;

readonly class StoreWebhookChannelData
{
    public function __construct(
        public string $resourceType,
        public string $resourceId,
        public string $webhookUrl,
        public int $durationHours,
    ) {}

    public static function fromRequest(StoreWebhookChannelRequest $request): self
    {
        /** @var array{resource_type: string, resource_id: string, webhook_url: string, duration_hours: int|string} $validated */
        $validated = $request->validated();

        return new self(
            resourceType: $validated['resource_type'],
            resourceId: $validated['resource_id'],
            webhookUrl: $validated['webhook_url'],
            durationHours: intval($validated['duration_hours']),
        );
    }
}
