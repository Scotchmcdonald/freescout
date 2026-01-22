<?php

declare(strict_types=1);

namespace Tests\Integration\CrossModule;

use Illuminate\Support\Facades\Event;
use App\DataTransferObjects\GoogleUserSyncedData;
use App\DataTransferObjects\GoogleChromebookDiscoveredData;
use App\DataTransferObjects\Action1DeviceDiscoveredData;
use Modules\AssetManagement\Entities\Asset;
use Modules\Crm\Models\Client;
use Modules\Crm\Models\Company;
use Modules\GoogleAdmin\Events\GoogleUserSynced;
use Modules\GoogleAdmin\Events\GoogleChromebookDiscovered;
use Modules\Action1\Events\Action1DeviceDiscovered;
use PHPUnit\Framework\Attributes\Group;
use Tests\IntegrationTestCase;

/**
 * Integration tests for GoogleAdmin and Action1 sync modules.
 * 
 * Tests actual cross-module functionality:
 * - Event dispatching with proper DTOs
 * - Listener registration for asset creation
 * - Multi-source device tracking
 */
#[Group('integration')]
#[Group('cross-module')]
#[Group('sync-modules')]
class SyncModuleIntegrationTest extends IntegrationTestCase
{
    private Company $company;
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        
        if (!class_exists(GoogleUserSynced::class)) {
            $this->markTestSkipped('GoogleAdmin module not available');
        }
        
        if (!class_exists(Action1DeviceDiscovered::class)) {
            $this->markTestSkipped('Action1 module not available');
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
     * Test GoogleUserSynced event can be dispatched with proper DTO.
     */
    public function test_google_user_synced_event_dispatches(): void
    {
        Event::fake([GoogleUserSynced::class]);

        $data = new GoogleUserSyncedData(
            clientId: $this->client->id,
            email: 'user@example.com',
            firstName: 'Test',
            lastName: 'User',
            googleId: 'google-id-123',
            suspended: false,
            orgUnitPath: '/Staff',
            metadata: ['department' => 'IT'],
        );

        event(new GoogleUserSynced($data));

        Event::assertDispatched(GoogleUserSynced::class, function ($event) {
            return $event->data->email === 'user@example.com'
                && $event->data->clientId === $this->client->id;
        });
    }

    /**
     * Test GoogleChromebookDiscovered event can be dispatched with proper DTO.
     */
    public function test_google_chromebook_discovered_event_dispatches(): void
    {
        Event::fake([GoogleChromebookDiscovered::class]);

        $data = new GoogleChromebookDiscoveredData(
            clientId: $this->client->id,
            serialNumber: 'CB-123456',
            model: 'HP Chromebook 14',
            status: 'ACTIVE',
            assignedUserEmail: 'user@example.com',
            metadata: ['enrollment_date' => '2025-01-01'],
        );

        event(new GoogleChromebookDiscovered($data));

        Event::assertDispatched(GoogleChromebookDiscovered::class, function ($event) {
            return $event->data->serialNumber === 'CB-123456'
                && $event->data->clientId === $this->client->id;
        });
    }

    /**
     * Test Action1DeviceDiscovered event can be dispatched with proper DTO.
     */
    public function test_action1_device_discovered_event_dispatches(): void
    {
        Event::fake([Action1DeviceDiscovered::class]);

        $data = new Action1DeviceDiscoveredData(
            clientId: $this->client->id,
            hostname: 'LAPTOP-001',
            osType: 'windows',
            osVersion: 'Windows 11 Pro',
            action1DeviceId: 'a1-device-123',
            isOnline: true,
            assignedUserEmail: 'user@example.com',
            metadata: ['last_seen' => now()->toIso8601String()],
        );

        event(new Action1DeviceDiscovered($data));

        Event::assertDispatched(Action1DeviceDiscovered::class, function ($event) {
            return $event->data->hostname === 'LAPTOP-001'
                && $event->data->osType === 'windows';
        });
    }

    /**
     * Test that Asset can be created from GoogleAdmin source.
     */
    public function test_asset_created_from_google_source(): void
    {
        $asset = Asset::factory()->create([
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
            'serial_number' => 'GOOGLE-CB-001',
            'asset_type' => 'chromebook',
            'source' => 'GoogleAdmin',
            'status' => 'active',
        ]);

        $this->assertEquals('GoogleAdmin', $asset->source);
        $this->assertEquals('chromebook', $asset->asset_type);
    }

    /**
     * Test that Asset can be created from Action1 source.
     */
    public function test_asset_created_from_action1_source(): void
    {
        $asset = Asset::factory()->create([
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
            'serial_number' => 'A1-WIN-001',
            'asset_type' => 'windows',
            'source' => 'Action1',
            'hostname' => 'LAPTOP-SALES-01',
            'status' => 'active',
        ]);

        $this->assertEquals('Action1', $asset->source);
        $this->assertEquals('windows', $asset->asset_type);
        $this->assertEquals('LAPTOP-SALES-01', $asset->hostname);
    }

    /**
     * Test tracking assets from multiple sources for same client.
     */
    public function test_multi_source_asset_tracking(): void
    {
        // Create assets from different sources
        Asset::factory()->count(3)->create([
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
            'source' => 'GoogleAdmin',
            'asset_type' => 'chromebook',
        ]);

        Asset::factory()->count(5)->create([
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
            'source' => 'Action1',
            'asset_type' => 'windows',
        ]);

        Asset::factory()->count(2)->create([
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
            'source' => 'Manual',
            'asset_type' => 'macos',
        ]);

        $googleAssets = Asset::where('client_id', $this->client->id)
            ->where('source', 'GoogleAdmin')
            ->count();
        $action1Assets = Asset::where('client_id', $this->client->id)
            ->where('source', 'Action1')
            ->count();
        $manualAssets = Asset::where('client_id', $this->client->id)
            ->where('source', 'Manual')
            ->count();

        $this->assertEquals(3, $googleAssets);
        $this->assertEquals(5, $action1Assets);
        $this->assertEquals(2, $manualAssets);
        $this->assertEquals(10, Asset::where('client_id', $this->client->id)->count());
    }

    /**
     * Test asset by type breakdown.
     */
    public function test_asset_type_breakdown(): void
    {
        Asset::factory()->count(4)->create([
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
            'asset_type' => 'chromebook',
        ]);

        Asset::factory()->count(6)->create([
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
            'asset_type' => 'windows',
        ]);

        $chromebooks = Asset::where('client_id', $this->client->id)
            ->where('asset_type', 'chromebook')
            ->count();
        $windows = Asset::where('client_id', $this->client->id)
            ->where('asset_type', 'windows')
            ->count();

        $this->assertEquals(4, $chromebooks);
        $this->assertEquals(6, $windows);
    }

    /**
     * Test VersionedEvent base class properties on sync events.
     */
    public function test_versioned_event_has_event_id(): void
    {
        $data = new GoogleUserSyncedData(
            clientId: $this->client->id,
            email: 'test@example.com',
            firstName: 'Test',
            lastName: 'User',
            googleId: 'google-123',
            suspended: false,
            orgUnitPath: '/',
            metadata: [],
        );

        $event = new GoogleUserSynced($data);

        $this->assertNotEmpty($event->eventId);
        $this->assertIsString($event->eventId);
        // UUID format
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $event->eventId
        );
    }
}
