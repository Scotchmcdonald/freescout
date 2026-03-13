<?php

declare(strict_types=1);

namespace Tests\Integration\Jobs;

use App\DataTransferObjects\Action1DeviceDiscoveredData;
use Illuminate\Support\Facades\Event;
use Modules\Action1\Events\Action1DeviceDiscovered;
use Modules\Action1\Jobs\SyncAction1DevicesJob;
use PHPUnit\Framework\Attributes\Group;
use ReflectionMethod;
use Tests\TestCase;

/**
 * SyncAction1DevicesJob Integration Tests
 *
 * Tests the Action1 RMM device sync job infrastructure without requiring
 * actual Action1 API connections. Focuses on:
 * - DTO validation and data integrity
 * - Event structure and dispatching
 * - Job configuration (retries, backoff)
 * - OS type mapping logic
 */
#[Group('integration')]
#[Group('jobs')]
#[Group('action1')]
#[Group('external')]
class SyncAction1DevicesJobIntegrationTest extends TestCase
{
    /**
     * Test job has correct retry configuration.
     */
    public function test_job_has_correct_retry_configuration(): void
    {
        $job = new SyncAction1DevicesJob(
            clientId: 1,
            apiKey: 'test-api-key'
        );

        $this->assertEquals(3, $job->tries);
        $this->assertEquals(60, $job->backoff);
    }

    /**
     * Test job stores correct construction parameters.
     */
    public function test_job_stores_construction_parameters(): void
    {
        $job = new SyncAction1DevicesJob(
            clientId: 42,
            apiKey: 'secret-key-123'
        );

        $this->assertEquals(42, $job->clientId);
        $this->assertEquals('secret-key-123', $job->apiKey);
    }

    /**
     * Test job generates unique idempotency id.
     */
    public function test_job_generates_unique_id(): void
    {
        $job1 = new SyncAction1DevicesJob(clientId: 1, apiKey: 'key');
        $job2 = new SyncAction1DevicesJob(clientId: 2, apiKey: 'key');

        $this->assertEquals('action1-sync-devices-1', $job1->uniqueId());
        $this->assertEquals('action1-sync-devices-2', $job2->uniqueId());
        $this->assertNotEquals($job1->uniqueId(), $job2->uniqueId());
    }

    /**
     * Test OS type mapping for Windows.
     */
    public function test_maps_windows_os_type(): void
    {
        $job = new SyncAction1DevicesJob(clientId: 1, apiKey: 'key');
        $method = new ReflectionMethod($job, 'mapOsType');
        $method->setAccessible(true);

        $this->assertEquals('windows', $method->invoke($job, 'Windows'));
        $this->assertEquals('windows', $method->invoke($job, 'windows'));
        $this->assertEquals('windows', $method->invoke($job, 'Windows 10'));
        $this->assertEquals('windows', $method->invoke($job, 'Windows Server 2019'));
        $this->assertEquals('windows', $method->invoke($job, 'WINDOWS'));
    }

    /**
     * Test OS type mapping for macOS.
     */
    public function test_maps_macos_os_type(): void
    {
        $job = new SyncAction1DevicesJob(clientId: 1, apiKey: 'key');
        $method = new ReflectionMethod($job, 'mapOsType');
        $method->setAccessible(true);

        $this->assertEquals('macos', $method->invoke($job, 'macOS'));
        $this->assertEquals('macos', $method->invoke($job, 'Mac'));
        $this->assertEquals('macos', $method->invoke($job, 'darwin'));
        $this->assertEquals('macos', $method->invoke($job, 'Mac OS X'));
        $this->assertEquals('macos', $method->invoke($job, 'Darwin'));
    }

    /**
     * Test OS type mapping for Linux.
     */
    public function test_maps_linux_os_type(): void
    {
        $job = new SyncAction1DevicesJob(clientId: 1, apiKey: 'key');
        $method = new ReflectionMethod($job, 'mapOsType');
        $method->setAccessible(true);

        $this->assertEquals('linux', $method->invoke($job, 'linux'));
        $this->assertEquals('linux', $method->invoke($job, 'Linux'));
        $this->assertEquals('linux', $method->invoke($job, 'Ubuntu'));
        $this->assertEquals('linux', $method->invoke($job, 'ubuntu'));
        $this->assertEquals('linux', $method->invoke($job, 'Debian'));
        $this->assertEquals('linux', $method->invoke($job, 'debian'));
        $this->assertEquals('linux', $method->invoke($job, 'Ubuntu 22.04'));
    }

    /**
     * Test OS type mapping for unknown systems.
     */
    public function test_maps_unknown_os_type(): void
    {
        $job = new SyncAction1DevicesJob(clientId: 1, apiKey: 'key');
        $method = new ReflectionMethod($job, 'mapOsType');
        $method->setAccessible(true);

        $this->assertEquals('unknown', $method->invoke($job, 'FreeBSD'));
        $this->assertEquals('unknown', $method->invoke($job, 'ChromeOS'));
        $this->assertEquals('unknown', $method->invoke($job, ''));
        $this->assertEquals('unknown', $method->invoke($job, 'unknown'));
    }

    /**
     * Test Action1DeviceDiscoveredData DTO creation.
     */
    public function test_action1_device_discovered_data_dto_creation(): void
    {
        $dto = new Action1DeviceDiscoveredData(
            clientId: 1,
            hostname: 'workstation-001',
            osType: 'windows',
            osVersion: 'Windows 10 Pro',
            action1DeviceId: 'device-123',
            isOnline: true,
            assignedUserEmail: 'user@example.com',
            metadata: ['ip_address' => '192.168.1.100']
        );

        $this->assertEquals(1, $dto->clientId);
        $this->assertEquals('workstation-001', $dto->hostname);
        $this->assertEquals('windows', $dto->osType);
        $this->assertEquals('Windows 10 Pro', $dto->osVersion);
        $this->assertEquals('device-123', $dto->action1DeviceId);
        $this->assertTrue($dto->isOnline);
        $this->assertEquals('user@example.com', $dto->assignedUserEmail);
        $this->assertEquals('192.168.1.100', $dto->metadata['ip_address']);
    }

    /**
     * Test Action1DeviceDiscoveredData fromArray factory.
     */
    public function test_action1_device_discovered_data_from_array(): void
    {
        $dto = Action1DeviceDiscoveredData::fromArray([
            'client_id' => 5,
            'hostname' => 'server-001',
            'os_type' => 'linux',
            'os_version' => 'Ubuntu 22.04 LTS',
            'action1_device_id' => 'srv-456',
            'is_online' => false,
            'assigned_user_email' => null,
            'metadata' => ['ram_gb' => 32],
        ]);

        $this->assertEquals(5, $dto->clientId);
        $this->assertEquals('server-001', $dto->hostname);
        $this->assertEquals('linux', $dto->osType);
        $this->assertEquals('Ubuntu 22.04 LTS', $dto->osVersion);
        $this->assertEquals('srv-456', $dto->action1DeviceId);
        $this->assertFalse($dto->isOnline);
        $this->assertNull($dto->assignedUserEmail);
        $this->assertEquals(32, $dto->metadata['ram_gb']);
    }

    /**
     * Test Action1DeviceDiscoveredData handles camelCase keys.
     */
    public function test_action1_device_data_handles_camel_case_keys(): void
    {
        $dto = Action1DeviceDiscoveredData::fromArray([
            'client_id' => 1,
            'hostname' => 'camel-host',
            'osType' => 'macos',
            'osVersion' => 'macOS 14.0',
            'action1DeviceId' => 'camel-id',
            'isOnline' => true,
            'assignedUserEmail' => 'camel@test.com',
            'metadata' => [],
        ]);

        $this->assertEquals('macos', $dto->osType);
        $this->assertEquals('macOS 14.0', $dto->osVersion);
        $this->assertEquals('camel-id', $dto->action1DeviceId);
        $this->assertTrue($dto->isOnline);
        $this->assertEquals('camel@test.com', $dto->assignedUserEmail);
    }

    /**
     * Test Action1DeviceDiscoveredData toArray conversion.
     */
    public function test_action1_device_discovered_data_to_array(): void
    {
        $dto = new Action1DeviceDiscoveredData(
            clientId: 10,
            hostname: 'convert-host',
            osType: 'windows',
            osVersion: 'Windows 11',
            action1DeviceId: 'convert-id',
            isOnline: true,
            assignedUserEmail: 'convert@test.com',
            metadata: ['serial_number' => 'SN12345']
        );

        $array = $dto->toArray();

        $this->assertEquals(10, $array['client_id']);
        $this->assertEquals('convert-host', $array['hostname']);
        $this->assertEquals('windows', $array['os_type']);
        $this->assertEquals('Windows 11', $array['os_version']);
        $this->assertEquals('convert-id', $array['action1_device_id']);
        $this->assertTrue($array['is_online']);
        $this->assertEquals('convert@test.com', $array['assigned_user_email']);
        $this->assertEquals('SN12345', $array['metadata']['serial_number']);
    }

    /**
     * Test Action1DeviceDiscovered event can be dispatched.
     */
    public function test_action1_device_discovered_event_dispatches(): void
    {
        Event::fake([Action1DeviceDiscovered::class]);

        $dto = new Action1DeviceDiscoveredData(
            clientId: 1,
            hostname: 'event-host',
            osType: 'linux',
            osVersion: 'Debian 12',
            action1DeviceId: 'event-device',
            isOnline: true,
            assignedUserEmail: null,
            metadata: []
        );

        event(new Action1DeviceDiscovered($dto));

        Event::assertDispatched(Action1DeviceDiscovered::class, function ($event) {
            return $event->data->hostname === 'event-host';
        });
    }

    /**
     * Test DTO handles null assigned user email.
     */
    public function test_dto_handles_null_assigned_user(): void
    {
        $dto = new Action1DeviceDiscoveredData(
            clientId: 1,
            hostname: 'unassigned-host',
            osType: 'windows',
            osVersion: 'Windows 10',
            action1DeviceId: 'unassigned-id',
            isOnline: false,
            assignedUserEmail: null,
            metadata: []
        );

        $this->assertNull($dto->assignedUserEmail);

        $array = $dto->toArray();
        $this->assertNull($array['assigned_user_email']);
    }

    /**
     * Test DTO preserves device hardware metadata.
     */
    public function test_dto_preserves_hardware_metadata(): void
    {
        $metadata = [
            'last_seen' => '2024-01-15T10:00:00Z',
            'ip_address' => '10.0.0.50',
            'mac_address' => '00:1A:2B:3C:4D:5E',
            'serial_number' => 'LAPTOP-SN-001',
            'manufacturer' => 'Dell',
            'model' => 'Latitude 5520',
            'cpu' => 'Intel Core i7-1165G7',
            'ram_gb' => 16,
            'disk_gb' => 512,
            'tags' => ['engineering', 'laptop', 'encrypted'],
        ];

        $dto = new Action1DeviceDiscoveredData(
            clientId: 1,
            hostname: 'dell-laptop',
            osType: 'windows',
            osVersion: 'Windows 11 Pro',
            action1DeviceId: 'dell-001',
            isOnline: true,
            assignedUserEmail: 'engineer@company.com',
            metadata: $metadata
        );

        $this->assertEquals('10.0.0.50', $dto->metadata['ip_address']);
        $this->assertEquals('00:1A:2B:3C:4D:5E', $dto->metadata['mac_address']);
        $this->assertEquals('Dell', $dto->metadata['manufacturer']);
        $this->assertEquals('Latitude 5520', $dto->metadata['model']);
        $this->assertEquals(16, $dto->metadata['ram_gb']);
        $this->assertContains('encrypted', $dto->metadata['tags']);
    }

    /**
     * Test round-trip from DTO to array and back.
     */
    public function test_dto_round_trip_conversion(): void
    {
        $original = new Action1DeviceDiscoveredData(
            clientId: 99,
            hostname: 'roundtrip-host',
            osType: 'macos',
            osVersion: 'macOS Sonoma 14.2',
            action1DeviceId: 'rt-device',
            isOnline: true,
            assignedUserEmail: 'roundtrip@test.com',
            metadata: ['test' => true]
        );

        $array = $original->toArray();
        $rebuilt = Action1DeviceDiscoveredData::fromArray($array);

        $this->assertEquals($original->clientId, $rebuilt->clientId);
        $this->assertEquals($original->hostname, $rebuilt->hostname);
        $this->assertEquals($original->osType, $rebuilt->osType);
        $this->assertEquals($original->osVersion, $rebuilt->osVersion);
        $this->assertEquals($original->action1DeviceId, $rebuilt->action1DeviceId);
        $this->assertEquals($original->isOnline, $rebuilt->isOnline);
        $this->assertEquals($original->assignedUserEmail, $rebuilt->assignedUserEmail);
        $this->assertEquals($original->metadata, $rebuilt->metadata);
    }

    /**
     * Test DTO handles missing optional fields in fromArray.
     */
    public function test_dto_handles_missing_optional_fields(): void
    {
        $dto = Action1DeviceDiscoveredData::fromArray([
            'client_id' => 1,
            'hostname' => 'minimal',
            'os_type' => 'linux',
            'os_version' => 'Ubuntu',
            'action1_device_id' => 'min-id',
            'metadata' => [],
        ]);

        $this->assertFalse($dto->isOnline);
        $this->assertNull($dto->assignedUserEmail);
    }
}
