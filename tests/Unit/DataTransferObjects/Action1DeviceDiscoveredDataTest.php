<?php

declare(strict_types=1);

namespace Tests\Unit\DataTransferObjects;

use App\DataTransferObjects\Action1DeviceDiscoveredData;

it('creates dto directly via constructor', function () {
    $dto = new Action1DeviceDiscoveredData(
        123,
        'MacBook-Pro',
        'macos',
        '14.2',
        'device-xyz',
        true,
        'user@example.com',
        ['ip' => '1.2.3.4']
    );

    expect($dto->clientId)->toBe(123)
        ->and($dto->hostname)->toBe('MacBook-Pro')
        ->and($dto->osType)->toBe('macos')
        ->and($dto->osVersion)->toBe('14.2')
        ->and($dto->action1DeviceId)->toBe('device-xyz')
        ->and($dto->isOnline)->toBeTrue()
        ->and($dto->assignedUserEmail)->toBe('user@example.com')
        ->and($dto->metadata)->toBe(['ip' => '1.2.3.4']);
});

it('creates dto using fromArray with snake_case keys', function () {
    $data = [
        'client_id' => 456,
        'hostname' => 'Win-PC',
        'os_type' => 'windows',
        'os_version' => '11',
        'action1_device_id' => 'device-abc',
        'is_online' => false,
        'assigned_user_email' => 'admin@example.com',
        'metadata' => ['key' => 'value'],
    ];

    $dto = Action1DeviceDiscoveredData::fromArray($data);

    expect($dto->clientId)->toBe(456)
        ->and($dto->hostname)->toBe('Win-PC')
        ->and($dto->osType)->toBe('windows')
        ->and($dto->osVersion)->toBe('11')
        ->and($dto->action1DeviceId)->toBe('device-abc')
        ->and($dto->isOnline)->toBeFalse()
        ->and($dto->assignedUserEmail)->toBe('admin@example.com')
        ->and($dto->metadata)->toBe(['key' => 'value']);
});

it('creates dto using fromArray with camelCase keys', function () {
    $data = [
        'client_id' => 789,
        'hostname' => 'Linux-Server',
        'osType' => 'linux',
        'osVersion' => 'Ubuntu 22.04',
        'action1DeviceId' => 'device-123',
        'isOnline' => true,
        'assignedUserEmail' => 'sysadmin@example.com',
        'metadata' => [],
    ];

    $dto = Action1DeviceDiscoveredData::fromArray($data);

    expect($dto->osType)->toBe('linux')
        ->and($dto->osVersion)->toBe('Ubuntu 22.04')
        ->and($dto->action1DeviceId)->toBe('device-123')
        ->and($dto->isOnline)->toBeTrue()
        ->and($dto->assignedUserEmail)->toBe('sysadmin@example.com');
});

it('converts back to array via toArray', function () {
    $dto = new Action1DeviceDiscoveredData(
        999,
        'Test-Mac',
        'macos',
        '15.0',
        'device-000',
        false,
        null,
        []
    );

    $array = $dto->toArray();

    expect($array)->toBe([
        'client_id' => 999,
        'hostname' => 'Test-Mac',
        'os_type' => 'macos',
        'os_version' => '15.0',
        'action1_device_id' => 'device-000',
        'is_online' => false,
        'assigned_user_email' => null,
        'metadata' => [],
    ]);
});

it('handles boundary input defaults when optional fields are missing (validation edge case)', function () {
    $dto = Action1DeviceDiscoveredData::fromArray([
        'client_id' => 42,
        'hostname' => 'Edge-Device',
    ]);

    expect($dto->clientId)->toBe(42)
        ->and($dto->hostname)->toBe('Edge-Device')
        ->and($dto->osType)->toBe('')
        ->and($dto->osVersion)->toBe('')
        ->and($dto->action1DeviceId)->toBe('')
        ->and($dto->isOnline)->toBeFalse()
        ->and($dto->assignedUserEmail)->toBeNull()
        ->and($dto->metadata)->toBe([]);
});

it('preserves boundary metadata payload for downstream authorization checks', function () {
    $dto = Action1DeviceDiscoveredData::fromArray([
        'client_id' => 77,
        'hostname' => 'Secure-Host',
        'metadata' => [
            'source' => 'api',
            'authorization_context' => 'sync-job',
            'validation_trace' => 'edge',
        ],
    ]);

    expect($dto->metadata)->toHaveKey('authorization_context')
        ->and($dto->metadata)->toHaveKey('validation_trace');
});
