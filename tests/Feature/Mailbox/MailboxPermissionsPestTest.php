<?php

use App\Models\Mailbox;
use App\Models\User;
use App\Policies\MailboxPolicy;

test('admin can view permissions page', function () {
    $adminUser = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $this->actingAs($adminUser)
        ->get(route('mailboxes.permissions', $mailbox))
        ->assertStatus(200)
        ->assertSee('Mailbox Permissions')
        ->assertSee($user1->getFullName())
        ->assertSee($user2->getFullName());
});

test('non-admin cannot view permissions page', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    $mailbox = Mailbox::factory()->create();

    $this->actingAs($user)
        ->get(route('mailboxes.permissions', $mailbox))
        ->assertStatus(403);
});

test('admin can update permissions', function () {
    $adminUser = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $permissions = [
        $user1->id => MailboxPolicy::ACCESS_REPLY,
        $user2->id => MailboxPolicy::ACCESS_VIEW,
    ];

    $this->actingAs($adminUser)
        ->post(route('mailboxes.permissions.update', $mailbox), ['permissions' => $permissions])
        ->assertRedirect(route('mailboxes.permissions', $mailbox))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('mailbox_user', [
        'mailbox_id' => $mailbox->id,
        'user_id' => $user1->id,
        'access' => MailboxPolicy::ACCESS_REPLY,
    ]);

    $this->assertDatabaseHas('mailbox_user', [
        'mailbox_id' => $mailbox->id,
        'user_id' => $user2->id,
        'access' => MailboxPolicy::ACCESS_VIEW,
    ]);
});

test('user with view access can view mailbox', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    $mailbox = Mailbox::factory()->create();

    $mailbox->users()->attach($user, ['access' => MailboxPolicy::ACCESS_VIEW]);

    $this->actingAs($user)
        ->get(route('mailboxes.view', $mailbox))
        ->assertStatus(200);
});

test('user without view access cannot view mailbox', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    $mailbox = Mailbox::factory()->create();

    $this->actingAs($user)
        ->get(route('mailboxes.view', $mailbox))
        ->assertStatus(403);
});

test('user with reply access can reply', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    $mailbox = Mailbox::factory()->create();

    $mailbox->users()->attach($user, ['access' => MailboxPolicy::ACCESS_REPLY]);
    $user->refresh(); // Refresh to load relationships/permissions if necessary

    expect($user->can('reply', $mailbox))->toBeTrue();
});

test('user with view access cannot reply', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    $mailbox = Mailbox::factory()->create();

    $mailbox->users()->attach($user, ['access' => MailboxPolicy::ACCESS_VIEW]);
    $user->refresh();

    expect($user->can('reply', $mailbox))->toBeFalse();
});

test('user with admin access can update mailbox settings', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    $mailbox = Mailbox::factory()->create();

    $mailbox->users()->attach($user, ['access' => MailboxPolicy::ACCESS_ADMIN]);

    expect($user->can('update', $mailbox))->toBeTrue();
});

test('user with reply access cannot update mailbox settings', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    $mailbox = Mailbox::factory()->create();

    $mailbox->users()->attach($user, ['access' => MailboxPolicy::ACCESS_REPLY]);

    expect($user->can('update', $mailbox))->toBeFalse();
});
