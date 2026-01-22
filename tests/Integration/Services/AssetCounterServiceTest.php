<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use Modules\AssetManagement\Services\AssetCounterService;
use Modules\Crm\Models\Client;
use Modules\Crm\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * AssetCounterService Integration Tests
 * 
 * Tests the asset counting service used for billing calculations.
 * Counts assets by type and allocation for each client to determine
 * billing amounts based on managed asset quantities.
 * 
 * Critical for:
 * - Accurate per-asset billing
 * - Client entitlement calculations
 * - Billing template processing
 */
#[Group('integration')]
#[Group('services')]
#[Group('asset-management')]
#[Group('billing')]
class AssetCounterServiceTest extends TestCase
{
    use RefreshDatabase;

    private AssetCounterService $service;
    private Client $client;
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Drop and recreate test table for asset counters
        Schema::dropIfExists('client_asset_counters');
        Schema::create('client_asset_counters', function ($table) {
            $table->id();
            $table->foreignId('client_id');
            $table->string('asset_type');
            $table->string('allocation_type');
            $table->integer('count')->default(0);
            $table->timestamps();
            $table->unique(['client_id', 'asset_type', 'allocation_type']);
        });
        
        $this->company = Company::factory()->create();
        $this->client = Client::factory()->create(['company_id' => $this->company->id]);
        $this->service = app(AssetCounterService::class);
    }

    /**
     * Test counts zero assets for new client.
     */
    public function test_counts_zero_for_new_client(): void
    {
        $count = $this->service->getAssetCount($this->client->id, 'workstation');
        
        $this->assertEquals(0, $count);
    }

    /**
     * Test increment increases asset count.
     */
    public function test_increment_increases_count(): void
    {
        $this->service->initializeCounter($this->client->id, 'workstation');
        
        $newCount = $this->service->incrementAssetCount($this->client->id, 'workstation');
        
        $this->assertEquals(1, $newCount);
        
        $count = $this->service->getAssetCount($this->client->id, 'workstation');
        $this->assertEquals(1, $count);
    }

    /**
     * Test decrement decreases asset count.
     */
    public function test_decrement_decreases_count(): void
    {
        $this->service->initializeCounter($this->client->id, 'workstation');
        $this->service->incrementAssetCount($this->client->id, 'workstation');
        $this->service->incrementAssetCount($this->client->id, 'workstation');
        $this->service->incrementAssetCount($this->client->id, 'workstation');
        
        $newCount = $this->service->decrementAssetCount($this->client->id, 'workstation');
        
        $this->assertEquals(2, $newCount);
    }

    /**
     * Test counts assets by type.
     */
    public function test_counts_assets_by_type(): void
    {
        $this->service->initializeCounter($this->client->id, 'workstation');
        $this->service->initializeCounter($this->client->id, 'server');
        
        // Add 3 workstations
        $this->service->incrementAssetCount($this->client->id, 'workstation');
        $this->service->incrementAssetCount($this->client->id, 'workstation');
        $this->service->incrementAssetCount($this->client->id, 'workstation');
        
        // Add 2 servers
        $this->service->incrementAssetCount($this->client->id, 'server');
        $this->service->incrementAssetCount($this->client->id, 'server');
        
        $workstationCount = $this->service->getAssetCount($this->client->id, 'workstation');
        $serverCount = $this->service->getAssetCount($this->client->id, 'server');
        
        $this->assertEquals(3, $workstationCount);
        $this->assertEquals(2, $serverCount);
    }

    /**
     * Test counts assets by allocation type.
     */
    public function test_counts_assets_by_allocation_type(): void
    {
        $this->service->initializeCounter($this->client->id, 'chromebook', 'user_assigned');
        $this->service->initializeCounter($this->client->id, 'chromebook', 'non_allocated');
        
        // Add 3 user-assigned chromebooks
        $this->service->incrementAssetCount($this->client->id, 'chromebook', 'user_assigned');
        $this->service->incrementAssetCount($this->client->id, 'chromebook', 'user_assigned');
        $this->service->incrementAssetCount($this->client->id, 'chromebook', 'user_assigned');
        
        // Add 2 non-allocated chromebooks
        $this->service->incrementAssetCount($this->client->id, 'chromebook', 'non_allocated');
        $this->service->incrementAssetCount($this->client->id, 'chromebook', 'non_allocated');
        
        $userAssignedCount = $this->service->getAssetCount($this->client->id, 'chromebook', 'user_assigned');
        $nonAllocatedCount = $this->service->getAssetCount($this->client->id, 'chromebook', 'non_allocated');
        
        $this->assertEquals(3, $userAssignedCount);
        $this->assertEquals(2, $nonAllocatedCount);
    }

    /**
     * Test get asset count sums all allocation types when null.
     */
    public function test_get_sum_all_allocation_types(): void
    {
        $this->service->initializeCounter($this->client->id, 'chromebook', 'user_assigned');
        $this->service->initializeCounter($this->client->id, 'chromebook', 'non_allocated');
        
        // Add 3 user-assigned
        $this->service->incrementAssetCount($this->client->id, 'chromebook', 'user_assigned');
        $this->service->incrementAssetCount($this->client->id, 'chromebook', 'user_assigned');
        $this->service->incrementAssetCount($this->client->id, 'chromebook', 'user_assigned');
        
        // Add 2 non-allocated
        $this->service->incrementAssetCount($this->client->id, 'chromebook', 'non_allocated');
        $this->service->incrementAssetCount($this->client->id, 'chromebook', 'non_allocated');
        
        // Get total (allocation_type = null)
        $totalCount = $this->service->getAssetCount($this->client->id, 'chromebook');
        
        $this->assertEquals(5, $totalCount);
    }

    /**
     * Test client isolation - counts only for specified client.
     */
    public function test_client_isolation(): void
    {
        $client2 = Client::factory()->create(['company_id' => $this->company->id]);
        
        $this->service->initializeCounter($this->client->id, 'workstation');
        $this->service->initializeCounter($client2->id, 'workstation');
        
        // Add 3 to client 1
        $this->service->incrementAssetCount($this->client->id, 'workstation');
        $this->service->incrementAssetCount($this->client->id, 'workstation');
        $this->service->incrementAssetCount($this->client->id, 'workstation');
        
        // Add 7 to client 2
        for ($i = 0; $i < 7; $i++) {
            $this->service->incrementAssetCount($client2->id, 'workstation');
        }
        
        $count1 = $this->service->getAssetCount($this->client->id, 'workstation');
        $count2 = $this->service->getAssetCount($client2->id, 'workstation');
        
        $this->assertEquals(3, $count1);
        $this->assertEquals(7, $count2);
    }

    /**
     * Test initialize counter creates record if not exists.
     */
    public function test_initialize_creates_record(): void
    {
        // Should not exist before
        $countBefore = DB::table('client_asset_counters')
            ->where('client_id', $this->client->id)
            ->where('asset_type', 'laptop')
            ->count();
        
        $this->assertEquals(0, $countBefore);
        
        $this->service->initializeCounter($this->client->id, 'laptop');
        
        // Should exist now
        $countAfter = DB::table('client_asset_counters')
            ->where('client_id', $this->client->id)
            ->where('asset_type', 'laptop')
            ->count();
        
        $this->assertEquals(1, $countAfter);
    }

    /**
     * Test initialize counter does nothing if already exists.
     */
    public function test_initialize_idempotent(): void
    {
        $this->service->initializeCounter($this->client->id, 'laptop');
        $this->service->incrementAssetCount($this->client->id, 'laptop');
        $this->service->incrementAssetCount($this->client->id, 'laptop');
        
        // Initialize again should not reset
        $this->service->initializeCounter($this->client->id, 'laptop');
        
        $count = $this->service->getAssetCount($this->client->id, 'laptop');
        $this->assertEquals(2, $count);
    }

    /**
     * Test multiple operations maintain consistency.
     */
    public function test_multiple_operations_consistency(): void
    {
        $this->service->initializeCounter($this->client->id, 'desktop');
        
        // 10 increments
        for ($i = 0; $i < 10; $i++) {
            $this->service->incrementAssetCount($this->client->id, 'desktop');
        }
        
        // 3 decrements
        for ($i = 0; $i < 3; $i++) {
            $this->service->decrementAssetCount($this->client->id, 'desktop');
        }
        
        // 5 more increments
        for ($i = 0; $i < 5; $i++) {
            $this->service->incrementAssetCount($this->client->id, 'desktop');
        }
        
        // 10 - 3 + 5 = 12
        $count = $this->service->getAssetCount($this->client->id, 'desktop');
        $this->assertEquals(12, $count);
    }

    /**
     * Test handles different asset types for same client.
     */
    public function test_handles_multiple_asset_types(): void
    {
        $assetTypes = ['workstation', 'server', 'chromebook', 'printer', 'switch'];
        
        foreach ($assetTypes as $type) {
            $this->service->initializeCounter($this->client->id, $type);
        }
        
        // Add different amounts for each type
        $this->service->incrementAssetCount($this->client->id, 'workstation');
        $this->service->incrementAssetCount($this->client->id, 'workstation');
        $this->service->incrementAssetCount($this->client->id, 'server');
        $this->service->incrementAssetCount($this->client->id, 'chromebook');
        $this->service->incrementAssetCount($this->client->id, 'chromebook');
        $this->service->incrementAssetCount($this->client->id, 'chromebook');
        
        $this->assertEquals(2, $this->service->getAssetCount($this->client->id, 'workstation'));
        $this->assertEquals(1, $this->service->getAssetCount($this->client->id, 'server'));
        $this->assertEquals(3, $this->service->getAssetCount($this->client->id, 'chromebook'));
        $this->assertEquals(0, $this->service->getAssetCount($this->client->id, 'printer'));
        $this->assertEquals(0, $this->service->getAssetCount($this->client->id, 'switch'));
    }
}
