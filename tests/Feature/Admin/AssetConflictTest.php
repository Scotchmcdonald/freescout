<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\AssetManagement\Entities\Asset;
use Modules\AssetManagement\Entities\AssetStagingRecord;
use Modules\Crm\Models\Client;
use Tests\TestCase;

class AssetConflictTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_conflicts()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $client = Client::create(['name' => 'Tech Corp']);
        
        $asset = Asset::factory()->create([
            'client_id' => $client->id,
            'serial_number' => 'CONFLICT123'
        ]);

        AssetStagingRecord::create([
            'asset_id' => $asset->id,
            'source' => 'Action1',
            'proposed_changes' => ['hostname' => 'new-hostname'],
            'status' => 'pending_review'
        ]);

        $response = $this->actingAs($admin)->get(route('admin.assets.conflicts'));

        $response->assertOk();
        $response->assertViewIs('admin.assets.conflicts');
        $response->assertSee('CONFLICT123');
        $response->assertSee('new-hostname');
    }

    public function test_admin_can_approve_conflict()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $client = Client::create(['name' => 'Tech Corp']);
        
        $asset = Asset::factory()->create([
            'client_id' => $client->id,
            'serial_number' => 'CONFLICT123',
            'hostname' => 'old-hostname'
        ]);

        $staging = AssetStagingRecord::create([
            'asset_id' => $asset->id,
            'source' => 'Action1',
            'proposed_changes' => ['hostname' => 'new-hostname'],
            'status' => 'pending_review'
        ]);

        $response = $this->actingAs($admin)->post(route('admin.assets.conflicts.approve', $staging->id));

        $response->assertRedirect();
        
        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'hostname' => 'new-hostname'
        ]);
        
        $this->assertDatabaseHas('asset_staging_records', [
            'id' => $staging->id,
            'status' => 'approved',
            'reviewed_by' => $admin->id
        ]);
    }

    public function test_admin_can_reject_conflict()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $client = Client::create(['name' => 'Tech Corp']);
        
        $asset = Asset::factory()->create([
            'client_id' => $client->id,
            'serial_number' => 'CONFLICT123',
            'hostname' => 'old-hostname'
        ]);

        $staging = AssetStagingRecord::create([
            'asset_id' => $asset->id,
            'source' => 'Action1',
            'proposed_changes' => ['hostname' => 'new-hostname'],
            'status' => 'pending_review'
        ]);

        $response = $this->actingAs($admin)->post(route('admin.assets.conflicts.reject', $staging->id));

        $response->assertRedirect();
        
        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'hostname' => 'old-hostname'
        ]);
        
        $this->assertDatabaseHas('asset_staging_records', [
            'id' => $staging->id,
            'status' => 'rejected',
            'reviewed_by' => $admin->id
        ]);
    }
}
