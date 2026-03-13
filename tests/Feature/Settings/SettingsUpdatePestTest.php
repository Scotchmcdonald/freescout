<?php

use App\Models\Option;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
});

test('settings default values match legacy behavior', function () {
    // Test default for non-existent company_name
    // L5 would return config('app.name') as default
    $appName = config('app.name');
    $value = Option::getValue('company_name', $appName);

    expect($value)->toBe($appName);
});

test('settings update flow matches legacy behavior', function () {
    $this->actingAs($this->admin);

    // Act - Update settings like in L5
    $response = $this->post(route('settings.update'), [
        'company_name' => 'L5 Compatible Name',
        'next_ticket' => 1000,
    ]);

    // Assert - Settings should be stored in options table
    $this->assertDatabaseHas('options', [
        'name' => 'company_name',
        'value' => 'L5 Compatible Name',
    ]);

    $this->assertDatabaseHas('options', [
        'name' => 'next_ticket',
        'value' => '1000',
    ]);

    // Verify retrieval works
    $companyName = Option::getValue('company_name');
    expect($companyName)->toBe('L5 Compatible Name');
});

test('boolean options stored as integers matching legacy behavior', function () {
    $this->actingAs($this->admin);

    // Act - Update boolean setting
    $response = $this->post(route('settings.update'), [
        'email_branding' => true,
        'open_tracking' => false,
    ]);

    // Assert - Booleans should be stored as 1 and 0
    $this->assertDatabaseHas('options', [
        'name' => 'email_branding',
        'value' => '1',
    ]);

    $this->assertDatabaseHas('options', [
        'name' => 'open_tracking',
        'value' => '0',
    ]);
});
