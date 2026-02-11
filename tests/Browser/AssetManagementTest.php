<?php

use App\Models\User;
use Modules\Crm\Models\Client;

test('create windows asset', function () {
    $client = Client::firstOrCreate(
        ['name' => 'Test Asset Client'],
        ['status' => 'active']
    );
    // Use a fixed admin to avoid unique constraint issues
    $admin = User::firstOrCreate(['email' => 'asset-test-admin@example.com'], [
        'password' => \Illuminate\Support\Facades\Hash::make('password'),
        'role' => User::ROLE_ADMIN,
        'first_name' => 'Test',
        'last_name' => 'Admin'
    ]);
    $admin->password = \Illuminate\Support\Facades\Hash::make('password');
    $admin->email_verified_at = now(); // Ensure verified
    $admin->save();
    
    if (!$admin->isAdmin()) { $admin->role = User::ROLE_ADMIN; $admin->save(); }

    $serialNumber = "TEST-WIN-" . date('Ymd-His');

    // Login via UI
    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]')
        ->assertPathIs('/dashboard'); // Verify login success

    $this->visit('/assets/inventory')
        ->assertPathIs('/assets/inventory')
        ->assertVisible('[dusk="create-asset"]')
        ->click('[dusk="create-asset"]')
        // Wait for modal input to be visible
        ->assertVisible('[dusk="serial-number"]') 
        ->fill('[dusk="serial-number"]', $serialNumber)
        // Use select instead of selectOption
        ->select('[dusk="asset-type"]', 'windows')
        ->fill('[dusk="model"]', 'Dell Latitude Test')
        ->select('[dusk="status"]', 'active')
        ->select('[dusk="client"]', (string)$client->id)
        ->click('[dusk="save-asset"]')
        // Assert
        ->waitForText($serialNumber)
        ->assertSee($serialNumber);

})->group('assets');
