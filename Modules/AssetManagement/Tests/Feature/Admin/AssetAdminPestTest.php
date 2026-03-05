<?php

use App\Models\User;
use Modules\AssetManagement\Entities\Asset;
use Modules\AssetManagement\Entities\AssetStagingRecord;
use Modules\Crm\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;

beforeEach(function () {
    if (!class_exists(Asset::class)) {
        $this->markTestSkipped('AssetManagement module not available');
    }
});

test('admin can view assignment page', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)
        ->get(route('admin.assets.assign'))
        ->assertOk()
        ->assertViewIs('assetmanagement::assign');
});

test('admin can search for asset to assign', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $client = Client::create(['name' => 'Tech Corp']);
    
    Asset::factory()->create([
        'client_id' => $client->id,
        'serial_number' => 'FIND-ME-123',
        'hostname' => 'laptop-01'
    ]);

    $this->actingAs($admin)
        ->get(route('admin.assets.assign', ['search' => 'FIND-ME-123']))
        ->assertOk()
        ->assertSee('laptop-01')
        ->assertSee('FIND-ME-123');
});

test('admin can assign user to asset', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $client = Client::create(['name' => 'Tech Corp']);
    
    $asset = Asset::factory()->create([
        'client_id' => $client->id,
        'serial_number' => 'ASSIGN-ME',
        'assigned_user_email' => null
    ]);

    $this->actingAs($admin)
        ->post(route('admin.assets.store_assignment'), [
            'asset_id' => $asset->id,
            'email' => 'newuser@example.com'
        ])
        ->assertRedirect(route('admin.assets.inventory'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('assets', [
        'id' => $asset->id,
        'assigned_user_email' => 'newuser@example.com',
        'status' => 'active'
    ]);
});

test('assignment validation', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)
        ->post(route('admin.assets.store_assignment'), [
            'asset_id' => 999, // Non-existent
            'email' => 'not-an-email'
        ])
        ->assertSessionHasErrors(['asset_id', 'email']);
});

test('admin can view conflicts', function () {
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

    $this->actingAs($admin)
        ->get(route('admin.assets.conflicts'))
        ->assertOk()
        ->assertViewIs('assetmanagement::conflicts')
        ->assertSee('CONFLICT123')
        ->assertSee('new-hostname');
});

test('admin can approve conflict', function () {
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

    $this->actingAs($admin)
        ->post(route('admin.assets.conflicts.approve', $staging->id))
        ->assertRedirect();
    
    $this->assertDatabaseHas('assets', [
        'id' => $asset->id,
        'hostname' => 'new-hostname'
    ]);
    
    $this->assertDatabaseHas('asset_staging_records', [
        'id' => $staging->id,
        'status' => 'approved',
        'reviewed_by' => $admin->id
    ]);
});

test('admin can reject conflict', function () {
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

    $this->actingAs($admin)
        ->post(route('admin.assets.conflicts.reject', $staging->id))
        ->assertRedirect();
    
    $this->assertDatabaseHas('assets', [
        'id' => $asset->id,
        'hostname' => 'old-hostname'
    ]);
    
    $this->assertDatabaseHas('asset_staging_records', [
        'id' => $staging->id,
        'status' => 'rejected',
        'reviewed_by' => $admin->id
    ]);
});

test('admin can view inventory', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $client = Client::create(['name' => 'Test Client']);
    Asset::factory()->count(3)->create(['client_id' => $client->id]);

    $this->actingAs($admin)
        ->get(route('admin.assets.inventory'))
        ->assertOk()
        ->assertViewIs('assetmanagement::inventory')
        ->assertSee('Global Fleet Inventory')
        ->assertViewHas('assets');
});

test('inventory filtering', function () {
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

    $this->actingAs($admin)
        ->get(route('admin.assets.inventory', ['source' => 'GoogleAdmin']))
        ->assertOk()
        ->assertSee('GOOGLE123')
        ->assertDontSee('ACTION456');
});

test('inventory search', function () {
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

    $this->actingAs($admin)
        ->get(route('admin.assets.inventory', ['search' => 'FINDME']))
        ->assertOk()
        ->assertSee('found-host')
        ->assertDontSee('hidden-host');
});

test('csv export', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $client = Client::create(['name' => 'Test Client']);

    Asset::factory()->create([
        'client_id' => $client->id,
        'serial_number' => 'EXPORT123',
        'status' => 'active'
    ]);

    $response = $this->actingAs($admin)->get(route('admin.assets.inventory.export'));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('csv');
    
    // Capture stream content
    ob_start();
    $response->sendContent();
    $content = ob_get_clean();

    expect($content)->toContain('EXPORT123');
    expect($content)->toContain('Global Status');
});
