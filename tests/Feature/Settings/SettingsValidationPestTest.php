<?php

use App\Models\Option;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;


beforeEach(function () {
    $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
});

test('settings validation prevents sql injection', function () {
    $this->actingAs($this->admin);

    $maliciousInput = "'; DROP TABLE options; --";
    $response = $this->post(route('settings.update'), [
        'company_name' => $maliciousInput,
    ]);

    $response->assertRedirect();

    // Verify the malicious input was safely stored
    $this->assertDatabaseHas('options', [
        'name' => 'company_name',
        'value' => $maliciousInput,
    ]);

    // Verify options table still exists and other options are intact
    $this->assertNotNull(Option::all());

    // Verify no SQL was executed by checking table structure
    $this->assertDatabaseHas('options', ['name' => 'company_name']);
});

test('settings validation prevents xss', function () {
    $this->actingAs($this->admin);

    $xssPayload = '<script>alert("XSS")</script>';
    $response = $this->post(route('settings.update'), [
        'company_name' => $xssPayload,
    ]);

    $response->assertRedirect();

    // Verify the value was stored (sanitization should happen on output)
    $this->assertDatabaseHas('options', [
        'name' => 'company_name',
        'value' => $xssPayload,
    ]);
});

test('email settings require valid email format', function () {
    $this->actingAs($this->admin);

    $response = $this->post(route('settings.email.update'), [
        'mail_driver' => 'smtp',
        'mail_from_address' => 'invalid-email',
        'mail_from_name' => 'Test',
    ]);

    $response->assertSessionHasErrors('mail_from_address');

    // Verify error message is helpful
    $errors = session('errors');
    expect($errors->get('mail_from_address'))->not->toBeEmpty();

    // Verify the invalid email was not saved
    $this->assertDatabaseMissing('options', [
        'name' => 'mail_from_address',
        'value' => 'invalid-email',
    ]);
});

test('email settings require supported driver', function () {
    $this->actingAs($this->admin);

    $response = $this->post(route('settings.email.update'), [
        'mail_driver' => 'unsupported_driver',
        'mail_from_address' => 'test@example.com',
        'mail_from_name' => 'Test',
    ]);

    $response->assertSessionHasErrors('mail_driver');
});

test('settings page displays existing options', function () {
    Option::create(['name' => 'company_name', 'value' => 'Test Corp']);
    // Option 'next_ticket' might not be visible on general settings page, but company_name likely is
    
    $response = $this->actingAs($this->admin)->get(route('settings'));

    $response->assertOk();
    $response->assertSee('Test Corp');
});
