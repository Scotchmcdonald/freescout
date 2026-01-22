<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\AssetManagement\Database\Factories\AssetFactory;
use Modules\AssetManagement\Entities\Asset;
use Modules\Crm\Models\Client;
use Tests\TestCase;

class AssetControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_admin_can_view_inventory()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        
        $client = Client::create(['name' => 'Test Client']);
        
        Asset::factory()->count(3)->create(['client_id' => $client->id]);

        $response = $this->actingAs($admin)->get(route('admin.assets.inventory'));

        $response->assertOk();
        $response->assertViewIs('admin.assets.inventory');
        $response->assertSee('Global Fleet Inventory');
        $response->assertViewHas('assets');
    }

    public function test_inventory_filtering()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $client = Client::create(['name' => 'Test Client']);

        Asset::factory()->create([
            'client_id' => $client->id,
            'source' => 'GoogleAdmin',
            'serial_number' => 'GOOGLE123',
            'status' => 'active'
        ]);
        
        Asset::factory()->create([
            'client_id' => $client->id,
            'source' => 'Action1',
            'serial_number' => 'ACTION456',
            'status' => 'active'
        ]);

        $response = $this->actingAs($admin)->get(route('admin.assets.inventory', ['source' => 'GoogleAdmin']));

        $response->assertOk();
        $response->assertSee('GOOGLE123');
        $response->assertDontSee('ACTION456');
    }

    public function test_inventory_search()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $client = Client::create(['name' => 'Test Client']);

        Asset::factory()->create([
            'client_id' => $client->id,
            'serial_number' => 'FINDME123',
            'hostname' => 'found-host',
            'status' => 'active'
        ]);
        
        Asset::factory()->create([
            'client_id' => $client->id,
            'serial_number' => 'HIDDEN999',
            'hostname' => 'hidden-host',
            'status' => 'active'
        ]);

        $response = $this->actingAs($admin)->get(route('admin.assets.inventory', ['search' => 'FINDME']));

        $response->assertOk();
        $response->assertSee('found-host');
        $response->assertDontSee('hidden-host');
    }

    public function test_csv_export()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $client = Client::create(['name' => 'Test Client']);

        Asset::factory()->create([
            'client_id' => $client->id,
            'serial_number' => 'EXPORT123',
            'status' => 'active'
        ]);

        $response = $this->actingAs($admin)->get(route('admin.assets.inventory.export'));

        $response->assertOk();
        
        // In some environments, testing StreamedResponse content type can be tricky
        // But let's check headers
        $this->assertEquals('text/csv; charset=utf-8', $response->headers->get('content-type'));
        
        // Capture stream content
        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertStringContainsString('EXPORT123', $content);
        $this->assertStringContainsString('Global Status', $content); 
    }
}
