<?php

declare(strict_types=1);

namespace ModulesAssetManagementTestsIntegrationCrossModule;

use Illuminate\Support\Facades\Event;
use Modules\AssetManagement\Entities\Asset;
use Modules\AssetManagement\Events\AssetStatusChanged;
use Modules\Crm\Events\ClientCreated;
use Modules\Crm\Events\ClientStatusChanged;
use Modules\Crm\Events\ClientUpdated;
use Modules\Crm\Models\Client;
use Modules\Crm\Models\Company;
use PHPUnit\Framework\Attributes\Group;
use Tests\IntegrationTestCase;

/**
 * Integration tests for CRM and AssetManagement module interactions.
 * 
 * Tests actual cross-module functionality:
 * - CRM events and DTOs structure
 * - Asset model relationships to CRM Client
 * - Event dispatching on model changes
 * - Multi-tenant data isolation
 */
#[Group('integration')]
#[Group('cross-module')]
#[Group('crm-asset')]
class CrmAssetIntegrationTest extends IntegrationTestCase
{
    private Company $company;
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        
        if (!class_exists(Company::class)) {
            $this->markTestSkipped('CRM module not available');
        }
        
        if (!class_exists(Asset::class)) {
            $this->markTestSkipped('AssetManagement module not available');
        }

        $this->company = Company::factory()->create([
            'name' => 'Test MSP Company',
            'is_active' => true,
        ]);

        $this->client = Client::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Test Client',
            'status' => 'active',
        ]);
    }

    /**
     * Test that creating a client triggers ClientCreated event with proper DTO.
     */
    public function test_client_creation_emits_event_with_dto(): void
    {
        Event::fake([ClientCreated::class]);

        $client = Client::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Event Test Client',
            'email' => 'test@example.com',
        ]);

        Event::assertDispatched(ClientCreated::class, function ($event) use ($client) {
            return $event->data->clientId === $client->id
                && $event->data->name === 'Event Test Client';
        });
    }

    /**
     * Test that Asset model correctly relates to CRM Client.
     */
    public function test_asset_belongs_to_client(): void
    {
        $asset = Asset::factory()->create([
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
            'status' => 'active',
        ]);

        $this->assertInstanceOf(Client::class, $asset->client);
        $this->assertEquals($this->client->id, $asset->client->id);
        $this->assertEquals($this->client->name, $asset->client->name);
    }

    /**
     * Test that changing asset status triggers AssetStatusChanged event.
     */
    public function test_asset_status_change_emits_event(): void
    {
        Event::fake([AssetStatusChanged::class]);

        $asset = Asset::factory()->create([
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
            'status' => 'active',
        ]);

        $asset->update(['status' => 'retired']);

        Event::assertDispatched(AssetStatusChanged::class, function ($event) use ($asset) {
            return $event->data->assetId === $asset->id
                && $event->data->clientId === $this->client->id
                && $event->data->oldStatus === 'active'
                && $event->data->newStatus === 'retired';
        });
    }

    /**
     * Test counting active assets for a client.
     */
    public function test_asset_count_tracking(): void
    {
        $initialCount = Asset::where('client_id', $this->client->id)
            ->where('status', 'active')
            ->count();
        $this->assertEquals(0, $initialCount);

        Asset::factory()->count(5)->create([
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
            'status' => 'active',
        ]);

        $activeCount = Asset::where('client_id', $this->client->id)
            ->where('status', 'active')
            ->count();
        $this->assertEquals(5, $activeCount);

        Asset::factory()->count(2)->create([
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
            'status' => 'retired',
        ]);

        $activeCount = Asset::where('client_id', $this->client->id)
            ->where('status', 'active')
            ->count();
        $this->assertEquals(5, $activeCount);

        $totalCount = Asset::where('client_id', $this->client->id)->count();
        $this->assertEquals(7, $totalCount);
    }

    /**
     * Test that client status changes emit ClientStatusChanged event.
     */
    public function test_client_status_change_emits_event(): void
    {
        Event::fake([ClientStatusChanged::class]);

        $this->client->update(['status' => 'suspended']);

        Event::assertDispatched(ClientStatusChanged::class, function ($event) {
            return $event->data->clientId === $this->client->id
                && $event->data->oldStatus === 'active'
                && $event->data->newStatus === 'suspended';
        });
    }

    /**
     * Test that client updates emit ClientUpdated event.
     */
    public function test_client_update_emits_event(): void
    {
        Event::fake([ClientUpdated::class]);

        $this->client->update(['name' => 'Updated Name']);

        Event::assertDispatched(ClientUpdated::class, function ($event) {
            return $event->data->clientId === $this->client->id
                && isset($event->data->changedFields['name']);
        });
    }

    /**
     * Test asset client isolation for multi-tenancy.
     */
    public function test_asset_client_isolation(): void
    {
        $client2 = Client::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Another Client',
        ]);

        Asset::factory()->count(3)->create([
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
        ]);

        Asset::factory()->count(2)->create([
            'client_id' => $client2->id,
            'company_id' => $this->company->id,
        ]);

        $client1Assets = Asset::where('client_id', $this->client->id)->get();
        $client2Assets = Asset::where('client_id', $client2->id)->get();

        $this->assertCount(3, $client1Assets);
        $this->assertCount(2, $client2Assets);
        
        foreach ($client1Assets as $asset) {
            $this->assertEquals($this->client->id, $asset->client_id);
        }

        foreach ($client2Assets as $asset) {
            $this->assertEquals($client2->id, $asset->client_id);
        }
    }

    /**
     * Test asset company isolation for multi-tenancy.
     */
    public function test_asset_company_isolation(): void
    {
        $company2 = Company::factory()->create(['name' => 'Other Company']);
        $client2 = Client::factory()->create([
            'company_id' => $company2->id,
            'name' => 'Client in Other Company',
        ]);

        Asset::factory()->count(3)->create([
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
        ]);

        Asset::factory()->count(2)->create([
            'client_id' => $client2->id,
            'company_id' => $company2->id,
        ]);

        $company1Assets = Asset::where('company_id', $this->company->id)->get();
        $company2Assets = Asset::where('company_id', $company2->id)->get();

        $this->assertCount(3, $company1Assets);
        $this->assertCount(2, $company2Assets);
    }

    /**
     * Test Asset factory creates valid records.
     */
    public function test_asset_factory_creates_valid_records(): void
    {
        $asset = Asset::factory()->create([
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
        ]);

        $this->assertNotNull($asset->id);
        $this->assertNotNull($asset->serial_number);
        $this->assertContains($asset->asset_type, ['chromebook', 'windows', 'macos', 'linux']);
        $this->assertContains($asset->status, ['active', 'inactive', 'retired']);
        $this->assertContains($asset->source, ['GoogleAdmin', 'Action1', 'Manual']);
    }
}
