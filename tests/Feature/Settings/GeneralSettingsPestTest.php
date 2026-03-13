<?php

use App\Models\Option;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

test('admin can view main settings page', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    Option::create(['name' => 'company_name', 'value' => 'Test Company']);

    $this->actingAs($admin)
        ->get(route('settings'))
        ->assertOk()
        ->assertViewIs('settings.index')
        ->assertSee('Test Company');
});

test('non-admin cannot view settings', function () {
    // Set type to 2 (not internal staff) to ensure access is denied
    $user = User::factory()->create(['role' => User::ROLE_USER, 'type' => 2]);

    $this->actingAs($user)
        ->get(route('settings'))
        ->assertForbidden();
});

test('admin can update general settings', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)
        ->post(route('settings.update'), [
            'company_name' => 'Updated Company',
            'app_timezone' => 'UTC',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->assertDatabaseHas('options', [
        'name' => 'company_name',
        'value' => 'Updated Company',
    ]);
});

test('admin can view email settings', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)
        ->get(route('settings.email'))
        ->assertOk()
        ->assertViewIs('settings.email');
});

test('admin can update email settings', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)
        ->post(route('settings.email.update'), [
            'mail_driver' => 'smtp',
            'mail_host' => 'smtp.example.com',
            'mail_port' => 587,
            'mail_username' => 'user',
            'mail_password' => 'pass',
            'mail_encryption' => 'tls',
            'mail_from_address' => 'noreply@example.com',
            'mail_from_name' => 'Support',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->assertDatabaseHas('options', [
        'name' => 'mail_host',
        'value' => 'smtp.example.com',
    ]);
});

test('admin can view alerts settings', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)
        ->get(route('settings.alerts'))
        ->assertOk()
        ->assertViewIs('settings.alerts');
});

test('admin can update alerts settings', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)
        ->put(route('settings.alerts.update'), [
            'alert_recipients' => 'admin@example.com',
            'alerts' => [
                'failed_jobs' => true,
            ],
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->assertDatabaseHas('options', [
        'name' => 'alert_recipients',
        'value' => 'admin@example.com',
    ]);
});

test('admin can send test alert', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    Mail::fake();

    $this->actingAs($admin)
        ->put(route('settings.alerts.update'), [
            'action' => 'test',
            'alert_recipients' => 'admin@example.com',
        ])
        ->assertRedirect()
        ->assertSessionHas('success', 'Test alert sent successfully to 1 recipient(s).');

    Mail::assertSent(App\Mail\Alert::class, function ($mail) {
        return $mail->hasTo('admin@example.com');
    });
});
