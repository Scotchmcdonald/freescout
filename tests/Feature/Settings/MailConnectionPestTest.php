<?php

use App\Models\Mailbox;
use App\Models\User;
use App\Services\ImapService;
use App\Services\SmtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin can test smtp connection', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create([
        'out_server' => 'smtp.example.com',
        'out_port' => 587,
        'out_username' => 'test@example.com',
        'out_password' => 'password',
    ]);

    $this->mock(SmtpService::class)
        ->shouldReceive('testConnection')
        ->once()
        ->andReturn(['success' => true, 'message' => 'Connected']);

    $this->actingAs($admin)
        ->postJson(route('settings.test-smtp'), [
            'mailbox_id' => $mailbox->id,
            'test_email' => 'test@example.com',
        ])
        ->assertOk()
        ->assertJson(['success' => true]);

    $mailbox->refresh();
});

test('non-admin cannot test smtp', function () {
    // Force type to 2 to ensure not internal staff
    $user = User::factory()->create(['role' => User::ROLE_USER, 'type' => 2]);
    $mailbox = Mailbox::factory()->create();

    $this->actingAs($user)
        ->postJson(route('settings.test-smtp'), [
            'mailbox_id' => $mailbox->id,
            'test_email' => 'test@example.com',
        ])
        ->assertForbidden();
});

test('test smtp validates input', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)
        ->postJson(route('settings.test-smtp'), [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['mailbox_id', 'test_email']);
});

test('admin can test imap connection', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create([
        'in_server' => 'imap.example.com',
    ]);

    $this->mock(ImapService::class)
        ->shouldReceive('testConnection')
        ->once()
        ->andReturn(['success' => true, 'message' => 'Connected']);

    $this->actingAs($admin)
        ->postJson(route('settings.test-imap'), [
            'mailbox_id' => $mailbox->id,
        ])
        ->assertOk()
        ->assertJson(['success' => true]);
});

test('non-admin cannot test imap', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER, 'type' => 2]);
    $mailbox = Mailbox::factory()->create();

    $this->actingAs($user)
        ->postJson(route('settings.test-imap'), ['mailbox_id' => $mailbox->id])
        ->assertForbidden();
});

test('admin can validate smtp settings', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->mock(SmtpService::class)
        ->shouldReceive('validateSettings')
        ->once()
        ->andReturn([]); // No errors

    $this->actingAs($admin)
        ->postJson(route('settings.validate-smtp'), [
            'out_server' => 'smtp.example.com',
            'out_port' => 587,
            'email' => 'test@example.com',
        ])
        ->assertOk()
        ->assertJson(['success' => true]);
});

test('validate smtp returns errors', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->mock(SmtpService::class)
        ->shouldReceive('validateSettings')
        ->once()
        ->andReturn(['out_server' => 'Required']);

    $this->actingAs($admin)
        ->postJson(route('settings.validate-smtp'), [
            'out_server' => 'smtp.example.com',
            'out_port' => 587,
            'email' => 'test@example.com',
        ])
        ->assertStatus(422)
        ->assertJson(['success' => false]);
});
