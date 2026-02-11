<?php

use App\Models\Mailbox;
use App\Models\User;

// ====================
// AUTHORIZATION TESTS
// ====================

test('admin can access advanced settings', function () {
    $adminUser = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();

    $this->actingAs($adminUser)
        ->get(route('mailboxes.advanced_settings', $mailbox))
        ->assertStatus(200)
        ->assertViewIs('mailboxes.advanced_settings');
});

test('regular user cannot access advanced settings of unassigned mailbox', function () {
    $regularUser = User::factory()->create(['role' => User::ROLE_USER]);
    $mailbox = Mailbox::factory()->create();
    $otherMailbox = Mailbox::factory()->create();

    // Attach to first mailbox to show they are a valid user
    $mailbox->users()->attach($regularUser->id, ['access' => 10]);

    $this->actingAs($regularUser)
        ->get(route('mailboxes.advanced_settings', $otherMailbox))
        ->assertStatus(403);
});

test('unauthenticated user is redirected to login from advanced settings', function () {
    $mailbox = Mailbox::factory()->create();

    $this->get(route('mailboxes.advanced_settings', $mailbox))
        ->assertRedirect(route('login'));
});

// ====================
// VIEW DATA TESTS
// ====================

test('advanced settings view has required options', function () {
    $adminUser = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();

    $response = $this->actingAs($adminUser)
        ->get(route('mailboxes.advanced_settings', $mailbox));

    $response->assertViewHas('fromNameOptions');
    $response->assertViewHas('ticketAssigneeOptions');

    $fromNameOptions = $response->viewData('fromNameOptions');
    expect($fromNameOptions)->toHaveKeys([1, 4]);

    $ticketAssigneeOptions = $response->viewData('ticketAssigneeOptions');
    expect($ticketAssigneeOptions)->toHaveKeys([1, 2]);
});

// ====================
// SAVE SETTINGS TESTS
// ====================

test('save email aliases', function () {
    $adminUser = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();

    $this->actingAs($adminUser)
        ->post(route('mailboxes.save_advanced_settings', $mailbox), [
            'aliases' => "alias1@example.com\nalias2@example.com",
            'aliases_reply' => 1,
            'from_name' => 1,
        ])
        ->assertRedirect(route('mailboxes.advanced_settings', $mailbox));

    $mailbox->refresh();
    expect($mailbox->aliases)->toBe('alias1@example.com,alias2@example.com')
        ->and($mailbox->aliases_reply)->toBeTrue();
});

test('save invalid email aliases are filtered', function () {
    $adminUser = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();

    $this->actingAs($adminUser)
        ->post(route('mailboxes.save_advanced_settings', $mailbox), [
            'aliases' => "valid@example.com\ninvalid-email\nalso-valid@test.com",
            'from_name' => 1,
        ])
        ->assertRedirect(route('mailboxes.advanced_settings', $mailbox));

    $mailbox->refresh();
    expect($mailbox->aliases)->toBe('valid@example.com,also-valid@test.com');
});

test('save from name options', function () {
    $adminUser = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();

    $this->actingAs($adminUser)
        ->post(route('mailboxes.save_advanced_settings', $mailbox), [
            'from_name' => 4,
            'from_name_custom' => 'Custom Name',
        ])
        ->assertRedirect(route('mailboxes.advanced_settings', $mailbox));

    $mailbox->refresh();
    expect($mailbox->from_name)->toBe(4)
        ->and($mailbox->from_name_custom)->toBe('Custom Name');
});

test('custom from name required when from name is custom', function () {
    $adminUser = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();

    $this->actingAs($adminUser)
        ->post(route('mailboxes.save_advanced_settings', $mailbox), [
            'from_name' => 4,
            'from_name_custom' => '',
        ])
        ->assertSessionHasErrors('from_name_custom');
});

test('save ticket assignment options', function () {
    $adminUser = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();

    $this->actingAs($adminUser)
        ->post(route('mailboxes.save_advanced_settings', $mailbox), [
            'from_name' => 1,
            'ticket_assignee' => 2,
        ])
        ->assertRedirect(route('mailboxes.advanced_settings', $mailbox));

    $mailbox->refresh();
    expect($mailbox->ticket_assignee)->toBe(2);
});

test('save signature', function () {
    $adminUser = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();
    $signature = "Best regards,\nSupport Team";

    $this->actingAs($adminUser)
        ->post(route('mailboxes.save_advanced_settings', $mailbox), [
            'from_name' => 1,
            'signature' => $signature,
        ])
        ->assertRedirect(route('mailboxes.advanced_settings', $mailbox));

    $mailbox->refresh();
    expect($mailbox->signature)->toBe($signature);
});

test('save before reply text', function () {
    $adminUser = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();
    $beforeReply = "--- Original Message ---";

    $this->actingAs($adminUser)
        ->post(route('mailboxes.save_advanced_settings', $mailbox), [
            'from_name' => 1,
            'before_reply' => $beforeReply,
        ])
        ->assertRedirect(route('mailboxes.advanced_settings', $mailbox));

    $mailbox->refresh();
    expect($mailbox->before_reply)->toBe($beforeReply);
});

test('save ratings toggle', function () {
    $adminUser = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();

    $this->actingAs($adminUser)
        ->post(route('mailboxes.save_advanced_settings', $mailbox), [
            'from_name' => 1,
            'ratings' => 1,
        ])
        ->assertRedirect(route('mailboxes.advanced_settings', $mailbox));

    $mailbox->refresh();
    expect($mailbox->ratings)->toBeTrue();
});

// ====================
// VALIDATION TESTS
// ====================

test('from name must be valid option', function () {
    $adminUser = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();

    $this->actingAs($adminUser)
        ->post(route('mailboxes.save_advanced_settings', $mailbox), [
            'from_name' => 99,
        ])
        ->assertSessionHasErrors('from_name');
});

test('ticket assignee must be valid option', function () {
    $adminUser = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();

    $this->actingAs($adminUser)
        ->post(route('mailboxes.save_advanced_settings', $mailbox), [
            'from_name' => 1,
            'ticket_assignee' => 99,
        ])
        ->assertSessionHasErrors('ticket_assignee');
});

test('aliases max length validation', function () {
    $adminUser = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();

    $this->actingAs($adminUser)
        ->post(route('mailboxes.save_advanced_settings', $mailbox), [
            'from_name' => 1,
            'aliases' => str_repeat('a', 1001),
        ])
        ->assertSessionHasErrors('aliases');
});

test('signature max length validation', function () {
    $adminUser = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();

    $this->actingAs($adminUser)
        ->post(route('mailboxes.save_advanced_settings', $mailbox), [
            'from_name' => 1,
            'signature' => str_repeat('a', 10001),
        ])
        ->assertSessionHasErrors('signature');
});
