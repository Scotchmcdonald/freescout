<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use App\Http\Requests\UpdateRolePermissionRequest;

readonly class UpdateRolePermissionData
{
    public function __construct(
        public int $roleId,
        public int $permissionId,
        public bool $attached,
    ) {}

    public static function fromRequest(UpdateRolePermissionRequest $request): self
    {
        /** @var array{role_id: int|string, permission_id: int|string, attached: bool} $validated */
        $validated = $request->validated();

        return new self(
            roleId: intval($validated['role_id']),
            permissionId: intval($validated['permission_id']),
            attached: $validated['attached'],
        );
    }
}
