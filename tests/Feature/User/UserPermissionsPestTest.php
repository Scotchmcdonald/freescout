<?php

declare(strict_types=1);

use App\Models\Mailbox;
use App\Models\User;

test('admin can assign mailbox permissions to user', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    $mailbox1 = Mailbox::factory()->create();
    $mailbox2 = Mailbox::factory()->create();

    $this->actingAs($admin)
        ->post(route('users.permissions.update', $user), [
            'mailboxes' => [$mailbox1->id, $mailbox2->id],
        ])
        ->assertRedirect(); // Likely back()

    expect($user->mailboxes->contains($mailbox1->id))->toBeTrue();
    expect($user->mailboxes->contains($mailbox2->id))->toBeTrue();
});

test('admin can remove mailbox permissions from user', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    $mailbox1 = Mailbox::factory()->create();
    $mailbox2 = Mailbox::factory()->create();
    $user->mailboxes()->attach([$mailbox1->id, $mailbox2->id]);

    $this->actingAs($admin)
        ->post(route('users.permissions.update', $user), [
            'mailboxes' => [$mailbox1->id],
        ])
        ->assertRedirect();

    $user->refresh();
    expect($user->mailboxes->contains($mailbox1->id))->toBeTrue();
    expect($user->mailboxes->contains($mailbox2->id))->toBeFalse();
});

test('admin can remove all mailbox permissions', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    $mailbox = Mailbox::factory()->create();
    $user->mailboxes()->attach($mailbox->id);

    $this->actingAs($admin)
        ->post(route('users.permissions.update', $user), [
            'mailboxes' => [],
        ])
        ->assertRedirect();

    $user->refresh();
    expect($user->mailboxes->count())->toBe(0);
});

test('non admin cannot assign permissions', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    $targetUser = User::factory()->create(['role' => User::ROLE_USER]);
    $mailbox = Mailbox::factory()->create();

    $this->actingAs($user)
        ->post(route('users.permissions.update', $targetUser), [
            'mailboxes' => [$mailbox->id],
        ])
        ->assertForbidden();

    expect($targetUser->mailboxes->count())->toBe(0);
});

test('guest cannot assign permissions', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    $mailbox = Mailbox::factory()->create();

    $this->post(route('users.permissions.update', $user), [
        'mailboxes' => [$mailbox->id],
    ])
        ->assertRedirect(route('login'));
});

test('permission assignment validates invalid mailbox ids', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $user = User::factory()->create(['role' => User::ROLE_USER]);

    $this->actingAs($admin)
        ->post(route('users.permissions.update', $user), [
            'mailboxes' => [99999], // Invalid
        ])
        ->assertSessionHasErrors(['mailboxes.0']);
});
