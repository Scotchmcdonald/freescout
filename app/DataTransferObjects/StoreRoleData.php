<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use App\Http\Requests\StoreRoleRequest;

readonly class StoreRoleData
{
    public function __construct(
        public string $name,
        public string $label,
        public string $scope,
    ) {}

    public static function fromRequest(StoreRoleRequest $request): self
    {
        /** @var array{name: string, label?: string|null, scope?: string|null} $validated */
        $validated = $request->validated();

        return new self(
            name: $validated['name'],
            // Fall back to name if label is omitted — mirrors original controller logic
            label: $validated['label'] ?? $validated['name'],
            scope: $validated['scope'] ?? 'internal',
        );
    }
}
