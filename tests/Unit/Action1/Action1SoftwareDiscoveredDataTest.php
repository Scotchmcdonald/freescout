<?php

declare(strict_types=1);

namespace Tests\Unit\Action1;

use Modules\Action1\DataTransferObjects\Action1SoftwareDiscoveredData;
use Tests\PureUnitTestCase;

final class Action1SoftwareDiscoveredDataTest extends PureUnitTestCase
{
    // ─── constructor ──────────────────────────────────────────────────────────

    public function test_constructor_assigns_all_properties(): void
    {
        $dto = new Action1SoftwareDiscoveredData(
            endpointId: 'ep-123',
            endpointName: 'DESKTOP-01',
            softwareName: 'Microsoft 365',
            version: '16.0.17830',
            publisher: 'Microsoft',
            installDate: '2025-01-15',
            assetId: 42,
            clientId: 7,
            metadata: ['extra' => 'data'],
        );

        $this->assertSame('ep-123', $dto->endpointId);
        $this->assertSame('DESKTOP-01', $dto->endpointName);
        $this->assertSame('Microsoft 365', $dto->softwareName);
        $this->assertSame('16.0.17830', $dto->version);
        $this->assertSame('Microsoft', $dto->publisher);
        $this->assertSame('2025-01-15', $dto->installDate);
        $this->assertSame(42, $dto->assetId);
        $this->assertSame(7, $dto->clientId);
        $this->assertSame(['extra' => 'data'], $dto->metadata);
    }

    public function test_constructor_nullable_fields_can_be_null(): void
    {
        $dto = new Action1SoftwareDiscoveredData(
            endpointId: 'ep-1',
            endpointName: 'HOST',
            softwareName: 'App',
            version: null,
            publisher: null,
            installDate: null,
            assetId: null,
            clientId: null,
        );

        $this->assertNull($dto->version);
        $this->assertNull($dto->publisher);
        $this->assertNull($dto->installDate);
        $this->assertNull($dto->assetId);
        $this->assertNull($dto->clientId);
        $this->assertSame([], $dto->metadata);
    }

    // ─── fromWebhook ──────────────────────────────────────────────────────────

    public function test_from_webhook_maps_standard_keys(): void
    {
        $dto = Action1SoftwareDiscoveredData::fromWebhook([
            'endpoint_id' => 'ep-99',
            'endpoint_name' => 'SERVER-01',
            'software_name' => 'Office',
            'version' => '2021',
            'publisher' => 'Microsoft',
            'install_date' => '2025-06-01',
            'asset_id' => 10,
            'client_id' => 3,
            'metadata' => ['tag' => 'val'],
        ]);

        $this->assertSame('ep-99', $dto->endpointId);
        $this->assertSame('SERVER-01', $dto->endpointName);
        $this->assertSame('Office', $dto->softwareName);
        $this->assertSame('2021', $dto->version);
        $this->assertSame(10, $dto->assetId);
        $this->assertSame(['tag' => 'val'], $dto->metadata);
    }

    public function test_from_webhook_falls_back_to_alternate_keys(): void
    {
        $dto = Action1SoftwareDiscoveredData::fromWebhook([
            'device_id' => 'dev-77',
            'hostname' => 'PC-02',
            'name' => 'Chrome',
            'vendor' => 'Google',
        ]);

        $this->assertSame('dev-77', $dto->endpointId);
        $this->assertSame('PC-02', $dto->endpointName);
        $this->assertSame('Chrome', $dto->softwareName);
        $this->assertSame('Google', $dto->publisher);
    }

    public function test_from_webhook_defaults_endpoint_name_when_missing(): void
    {
        $dto = Action1SoftwareDiscoveredData::fromWebhook([
            'endpoint_id' => 'ep-1',
            'software_name' => 'App',
        ]);

        $this->assertSame('Unknown', $dto->endpointName);
    }

    public function test_from_webhook_empty_payload_uses_defaults(): void
    {
        $dto = Action1SoftwareDiscoveredData::fromWebhook([]);

        $this->assertSame('', $dto->endpointId);
        $this->assertSame('Unknown', $dto->endpointName);
        $this->assertSame('', $dto->softwareName);
        $this->assertNull($dto->version);
        $this->assertSame([], $dto->metadata);
    }

    // ─── toArray ──────────────────────────────────────────────────────────────

    public function test_to_array_returns_all_keys(): void
    {
        $dto = new Action1SoftwareDiscoveredData(
            endpointId: 'ep-1',
            endpointName: 'HOST',
            softwareName: 'App',
            version: '1.0',
            publisher: 'Vendor',
            installDate: '2025-01-01',
            assetId: 5,
            clientId: 2,
            metadata: [],
        );

        $arr = $dto->toArray();

        $this->assertArrayHasKey('endpoint_id', $arr);
        $this->assertArrayHasKey('endpoint_name', $arr);
        $this->assertArrayHasKey('software_name', $arr);
        $this->assertArrayHasKey('version', $arr);
        $this->assertArrayHasKey('publisher', $arr);
        $this->assertArrayHasKey('install_date', $arr);
        $this->assertArrayHasKey('asset_id', $arr);
        $this->assertArrayHasKey('client_id', $arr);
        $this->assertArrayHasKey('metadata', $arr);
    }

    public function test_to_array_values_match_properties(): void
    {
        $dto = new Action1SoftwareDiscoveredData(
            endpointId: 'ep-abc',
            endpointName: 'MY-PC',
            softwareName: 'Zoom',
            version: '5.17',
            publisher: 'Zoom Inc',
            installDate: null,
            assetId: null,
            clientId: null,
        );

        $arr = $dto->toArray();

        $this->assertSame('ep-abc', $arr['endpoint_id']);
        $this->assertSame('MY-PC', $arr['endpoint_name']);
        $this->assertSame('Zoom', $arr['software_name']);
        $this->assertSame('5.17', $arr['version']);
        $this->assertNull($arr['asset_id']);
    }
}
