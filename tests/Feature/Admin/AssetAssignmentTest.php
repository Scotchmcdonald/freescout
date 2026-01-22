<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\AssetManagement\Entities\Asset;
use Modules\Crm\Models\Client;
use Tests\TestCase;

class AssetAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_assignment_page()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->get(route('admin.assets.assign'));

        $response->assertOk();
        $response->assertViewIs('admin.assets.assign');
    }

    public function test_admin_can_search_for_asset_to_assign()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $client = Client::create(['name' => 'Tech Corp']);
        
        $asset = Asset::factory()->create([
            'client_id' => $client->id,
            'serial_number' => 'FIND-ME-123',
            'hostname' => 'laptop-01'
        ]);

        $response = $this->actingAs($admin)->get(route('admin.assets.assign', ['search' => 'FIND-ME-123']));

        $response->assertOk();
        $response->assertSee('laptop-01');
        $response->assertSee('FIND-ME-123');
    }

    public function test_admin_can_assign_user_to_asset()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $client = Client::create(['name' => 'Tech Corp']);
        
        $asset = Asset::factory()->create([
            'client_id' => $client->id,
            'serial_number' => 'ASSIGN-ME',
            'assigned_user_email' => null
        ]);

        $response = $this->actingAs($admin)->post(route('admin.assets.store_assignment'), [
            'asset_id' => $asset->id,
            'email' => 'newuser@example.com'
        ]);

        $response->assertRedirect(route('admin.assets.inventory'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'assigned_user_email' => 'newuser@example.com',
            'status' => 'active'
        ]);
    }

    public function test_assignment_validation()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->post(route('admin.assets.store_assignment'), [
            'asset_id' => 999, // Non-existent
            'email' => 'not-an-email'
        ]);

        $response->assertSessionHasErrors(['asset_id', 'email']);
    }
}
