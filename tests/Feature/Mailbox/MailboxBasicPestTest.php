<?php

use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\User;

test('admin can view mailboxes list', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $this->actingAs($admin);

    $mailbox1 = Mailbox::factory()->create([
        'name' => 'Support',
        'email' => 'support@example.com',
    ]);
    $mailbox2 = Mailbox::factory()->create([
        'name' => 'Sales',
        'email' => 'sales@example.com',
    ]);

    $response = $this->get(route('mailboxes.index'));

    $response->assertOk();
    $response->assertViewIs('mailboxes.index');
    $response->assertSee('Support');
    $response->assertSee('Sales');
});

test('admin can create a new mailbox', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailboxData = [
        'name' => 'New Support Mailbox',
        'email' => 'newsupport@example.com',
        'from_name' => 'Support Team',
    ];

    $this->actingAs($admin)
        ->post(route('mailboxes.store'), $mailboxData)
        ->assertRedirect();

    $this->assertDatabaseHas('mailboxes', [
        'name' => 'New Support Mailbox',
        'email' => 'newsupport@example.com',
        'from_name' => 3, // custom
        'from_name_custom' => 'Support Team',
    ]);
});

test('non-admin cannot create mailbox', function () {
    // Explicitly set type to 2 to ensure they are not "Internal Staff" if that middleware applies
    $user = User::factory()->create(['role' => User::ROLE_USER, 'type' => 2]);

    $mailboxData = [
        'name' => 'Unauthorized Mailbox',
        'email' => 'unauthorized@example.com',
    ];

    $this->actingAs($user)
        ->post(route('mailboxes.store'), $mailboxData)
        ->assertForbidden();

    $this->assertDatabaseMissing('mailboxes', [
        'email' => 'unauthorized@example.com',
    ]);
});

test('mailbox creation validates email format', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailboxData = [
        'name' => 'Test Mailbox',
        'email' => 'invalid-email',
    ];

    $this->actingAs($admin)
        ->post(route('mailboxes.store'), $mailboxData)
        ->assertSessionHasErrors('email');

    $this->assertDatabaseMissing('mailboxes', ['name' => 'Test Mailbox']);
});

test('mailbox creation requires unique email', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    Mailbox::factory()->create(['email' => 'existing@example.com']);

    $mailboxData = [
        'name' => 'Duplicate Email Mailbox',
        'email' => 'existing@example.com',
    ];

    $this->actingAs($admin)
        ->post(route('mailboxes.store'), $mailboxData)
        ->assertSessionHasErrors('email');
});

test('admin can update mailbox', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create([
        'name' => 'Original Name',
        'email' => 'original@example.com',
    ]);

    Folder::factory()->create([
        'mailbox_id' => $mailbox->id,
        'type' => Folder::TYPE_INBOX,
    ]);

    $updateData = [
        'name' => 'Updated Name',
        'from_name' => 'Updated Team',
    ];

    $this->actingAs($admin)
        ->patch(route('mailboxes.update', $mailbox), $updateData)
        ->assertRedirect();

    $mailbox->refresh();
    expect($mailbox->name)->toBe('Updated Name')
        ->and($mailbox->from_name)->toBe(3)
        ->and($mailbox->from_name_custom)->toBe('Updated Team');
});

test('non-admin cannot update mailbox', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER, 'type' => 2]);
    $mailbox = Mailbox::factory()->create(['name' => 'Original Name']);

    Folder::factory()->create([
        'mailbox_id' => $mailbox->id,
        'type' => Folder::TYPE_INBOX,
    ]);

    $this->actingAs($user)
        ->patch(route('mailboxes.update', $mailbox), ['name' => 'Hacked Name'])
        ->assertForbidden();

    expect($mailbox->fresh()->name)->toBe('Original Name');
});

test('admin can delete mailbox', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();

    Folder::factory()->create([
        'mailbox_id' => $mailbox->id,
        'type' => Folder::TYPE_INBOX,
    ]);

    $this->actingAs($admin)
        ->delete(route('mailboxes.destroy', $mailbox))
        ->assertRedirect();

    $this->assertDatabaseMissing('mailboxes', ['id' => $mailbox->id]);
});

test('non-admin cannot delete mailbox', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER, 'type' => 2]);
    $mailbox = Mailbox::factory()->create();

    Folder::factory()->create([
        'mailbox_id' => $mailbox->id,
        'type' => Folder::TYPE_INBOX,
    ]);

    $this->actingAs($user)
        ->delete(route('mailboxes.destroy', $mailbox))
        ->assertForbidden();

    $this->assertDatabaseHas('mailboxes', ['id' => $mailbox->id]);
});

test('admin can view mailbox detail page', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create(['name' => 'Support Mailbox']);

    $this->actingAs($admin)
        ->get(route('mailboxes.view', $mailbox))
        ->assertOk()
        ->assertSee('Support Mailbox');
});

test('user with access can view mailbox detail', function () {
    $regularUser = User::factory()->create(['role' => User::ROLE_USER]);
    $mailbox = Mailbox::factory()->create(['name' => 'Support Mailbox']);
    $mailbox->users()->attach($regularUser);

    $this->actingAs($regularUser)
        ->get(route('mailboxes.view', $mailbox))
        ->assertOk();
});

test('user without access cannot view mailbox detail', function () {
    $regularUser = User::factory()->create(['role' => User::ROLE_USER, 'type' => 2]);
    $mailbox = Mailbox::factory()->create(['name' => 'Support Mailbox']);

    $this->actingAs($regularUser)
        ->get(route('mailboxes.view', $mailbox))
        ->assertForbidden();
});

test('mailbox index shows only accessible mailboxes for regular user', function () {
    $regularUser = User::factory()->create(['role' => User::ROLE_USER]);
    $mailbox1 = Mailbox::factory()->create(['name' => 'Accessible Mailbox']);
    $mailbox2 = Mailbox::factory()->create(['name' => 'Inaccessible Mailbox']);

    $mailbox1->users()->attach($regularUser);

    $this->actingAs($regularUser)
        ->get(route('mailboxes.index'))
        ->assertOk()
        ->assertSee('Accessible Mailbox')
        ->assertDontSee('Inaccessible Mailbox');
});

test('admin can view mailbox settings page', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create(['name' => 'Test Mailbox']);

    $this->actingAs($admin)
        ->get(route('mailboxes.settings', $mailbox))
        ->assertOk()
        ->assertSee('Test Mailbox');
});

test('non-admin cannot view mailbox settings page', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER, 'type' => 2]);
    $mailbox = Mailbox::factory()->create();

    $this->actingAs($user)
        ->get(route('mailboxes.settings', $mailbox))
        ->assertForbidden();
});

test('mailbox creation with all optional fields', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailboxData = [
        'name' => 'Full Feature Mailbox',
        'email' => 'full@example.com',
        'from_name' => 'Full Team',
        'from_name_custom' => 'Custom Name',
        'ticket_status' => 1,
        'template' => 'default',
        'signature' => 'Best regards,\nSupport Team',
    ];

    $this->actingAs($admin)
        ->post(route('mailboxes.store'), $mailboxData)
        ->assertRedirect();

    $this->assertDatabaseHas('mailboxes', [
        'email' => 'full@example.com',
        'from_name' => 3,
        'from_name_custom' => 'Full Team',
    ]);
});
