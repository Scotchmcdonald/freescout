<?php

declare(strict_types=1);

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\User;

test('admin can delete user without conversations', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $userToDelete = User::factory()->create(['role' => User::ROLE_USER]);

    $this->actingAs($admin)
        ->delete(route('users.destroy', $userToDelete))
        ->assertRedirect(route('users.index'));

    expect($userToDelete->fresh()->status)->toBe(User::STATUS_DELETED);
});

test('cannot delete user with conversations without reassign', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $userToDelete = User::factory()->create(['role' => User::ROLE_USER]);
    $mailbox = Mailbox::factory()->create();
    $customer = Customer::factory()->create();

    Conversation::factory()->create([
        'user_id' => $userToDelete->id,
        'mailbox_id' => $mailbox->id,
        'customer_id' => $customer->id,
    ]);

    $this->actingAs($admin)
        ->delete(route('users.destroy', $userToDelete))
        ->assertSessionHasErrors('error');

    expect($userToDelete->fresh()->status)->not->toBe(User::STATUS_DELETED);
});

test('delete user with conversation reassignment', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $userToDelete = User::factory()->create(['role' => User::ROLE_USER]);
    $targetUser = User::factory()->create(['role' => User::ROLE_USER]);
    $mailbox = Mailbox::factory()->create();
    $customer = Customer::factory()->create();

    $conversation1 = Conversation::factory()->create([
        'user_id' => $userToDelete->id,
        'mailbox_id' => $mailbox->id,
        'customer_id' => $customer->id,
    ]);
    $conversation2 = Conversation::factory()->create([
        'user_id' => $userToDelete->id,
        'mailbox_id' => $mailbox->id,
        'customer_id' => $customer->id,
    ]);

    $response = $this->actingAs($admin)
        ->delete(route('users.destroy', $userToDelete), [
            'reassign_to' => $targetUser->id,
        ]);

    $response->assertRedirect(route('users.index'))
        ->assertSessionHas('success');

    // Verify user is deleted
    expect($userToDelete->fresh()->status)->toBe(User::STATUS_DELETED);

    // Verify conversations are reassigned
    expect($conversation1->fresh()->user_id)->toBe($targetUser->id);
    expect($conversation2->fresh()->user_id)->toBe($targetUser->id);
});

test('cannot reassign to self', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $userToDelete = User::factory()->create(['role' => User::ROLE_USER]);
    $mailbox = Mailbox::factory()->create();
    $customer = Customer::factory()->create();

    Conversation::factory()->create([
        'user_id' => $userToDelete->id,
        'mailbox_id' => $mailbox->id,
        'customer_id' => $customer->id,
    ]);

    $this->actingAs($admin)
        ->delete(route('users.destroy', $userToDelete), [
            'reassign_to' => $userToDelete->id,
        ])
        ->assertSessionHasErrors('error');
});

test('cannot reassign to deleted user', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $userToDelete = User::factory()->create(['role' => User::ROLE_USER]);
    $deletedUser = User::factory()->create([
        'role' => User::ROLE_USER,
        'status' => User::STATUS_DELETED,
    ]);
    $mailbox = Mailbox::factory()->create();
    $customer = Customer::factory()->create();

    Conversation::factory()->create([
        'user_id' => $userToDelete->id,
        'mailbox_id' => $mailbox->id,
        'customer_id' => $customer->id,
    ]);

    $this->actingAs($admin)
        ->delete(route('users.destroy', $userToDelete), [
            'reassign_to' => $deletedUser->id,
        ])
        ->assertSessionHasErrors('error');
});

test('cannot reassign to nonexistent user', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $userToDelete = User::factory()->create(['role' => User::ROLE_USER]);
    $mailbox = Mailbox::factory()->create();
    $customer = Customer::factory()->create();

    Conversation::factory()->create([
        'user_id' => $userToDelete->id,
        'mailbox_id' => $mailbox->id,
        'customer_id' => $customer->id,
    ]);

    $this->actingAs($admin)
        ->delete(route('users.destroy', $userToDelete), [
            'reassign_to' => 9999,
        ])
        ->assertSessionHasErrors('error');
});

test('non admin cannot delete user', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    $userToDelete = User::factory()->create(['role' => User::ROLE_USER]);

    $this->actingAs($user)
        ->delete(route('users.destroy', $userToDelete))
        ->assertForbidden();
});
