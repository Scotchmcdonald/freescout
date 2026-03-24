<?php

declare(strict_types=1);

use App\Models\Conversation;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin can view dashboard with all mailboxes', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox1 = Mailbox::factory()->create();
    $mailbox2 = Mailbox::factory()->create();

    Folder::factory()->create(['mailbox_id' => $mailbox1->id, 'type' => Folder::TYPE_INBOX]);
    Folder::factory()->create(['mailbox_id' => $mailbox2->id, 'type' => Folder::TYPE_INBOX]);

    $response = $this->actingAs($admin)->get(route('dashboard'));

    $response->assertOk();
    expect($response->viewData('mailboxes')->count())->toBe(2);
});

test('user can view dashboard with assigned mailboxes only', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    $mailbox1 = Mailbox::factory()->create();
    $mailbox2 = Mailbox::factory()->create();

    Folder::factory()->create(['mailbox_id' => $mailbox1->id, 'type' => Folder::TYPE_INBOX]);
    Folder::factory()->create(['mailbox_id' => $mailbox2->id, 'type' => Folder::TYPE_INBOX]);

    $mailbox1->users()->attach($user);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    expect($response->viewData('mailboxes')->count())->toBe(1);
});

test('dashboard displays active conversations count correctly', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user);
    Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => Folder::TYPE_INBOX]);

    // Active & Published
    Conversation::factory()->count(3)->create([
        'mailbox_id' => $mailbox->id,
        'status' => Conversation::STATUS_ACTIVE,
        'state' => 2, // Published
    ]);

    // Closed (should ignore)
    Conversation::factory()->create([
        'mailbox_id' => $mailbox->id,
        'status' => Conversation::STATUS_CLOSED,
        'state' => 2,
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    expect($response->viewData('activeConversations'))->toBe(3);
});

test('dashboard displays unassigned conversations count correctly', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user);
    Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => Folder::TYPE_INBOX]);

    // Unassigned active
    Conversation::factory()->count(2)->create([
        'mailbox_id' => $mailbox->id,
        'user_id' => null,
        'status' => Conversation::STATUS_ACTIVE,
        'state' => 2,
    ]);

    // Assigned active (unassigned count ignore, active count include)
    Conversation::factory()->create([
        'mailbox_id' => $mailbox->id,
        'user_id' => $user->id,
        'status' => Conversation::STATUS_ACTIVE,
        'state' => 2,
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    expect($response->viewData('unassignedConversations'))->toBe(2);
    expect($response->viewData('activeConversations'))->toBe(3);
});

test('dashboard provides per mailbox statistics', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    $mailbox1 = Mailbox::factory()->create();
    $mailbox2 = Mailbox::factory()->create();

    $mailbox1->users()->attach($user);
    $mailbox2->users()->attach($user);

    // Mailbox 1: 2 active assigned, 1 active unassigned
    Conversation::factory()->count(2)->create([
        'mailbox_id' => $mailbox1->id,
        'status' => Conversation::STATUS_ACTIVE,
        'state' => 2,
        'user_id' => $user->id,
    ]);
    Conversation::factory()->create([
        'mailbox_id' => $mailbox1->id,
        'status' => Conversation::STATUS_ACTIVE,
        'state' => 2,
        'user_id' => null,
    ]);

    // Mailbox 2: 1 active unassigned
    Conversation::factory()->create([
        'mailbox_id' => $mailbox2->id,
        'status' => Conversation::STATUS_ACTIVE,
        'state' => 2,
        'user_id' => null,
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $stats = $response->viewData('stats');
    expect($stats[$mailbox1->id]['active'])->toBe(3)
        ->and($stats[$mailbox1->id]['unassigned'])->toBe(1)
        ->and($stats[$mailbox2->id]['active'])->toBe(1)
        ->and($stats[$mailbox2->id]['unassigned'])->toBe(1);
});

test('dashboard only counts published conversations', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user);

    // Published
    Conversation::factory()->create([
        'mailbox_id' => $mailbox->id,
        'status' => Conversation::STATUS_ACTIVE,
        'state' => 2,
    ]);

    // Draft (ignore)
    Conversation::factory()->create([
        'mailbox_id' => $mailbox->id,
        'status' => Conversation::STATUS_ACTIVE,
        'state' => 1,
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));
    $response->assertOk();
    expect($response->viewData('activeConversations'))->toBe(1);
});

test('dashboard requires authentication', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('dashboard with no conversations shows zero counts', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    expect($response->viewData('activeConversations'))->toBe(0);
    expect($response->viewData('unassignedConversations'))->toBe(0);
});

test('dashboard handles user with no mailboxes', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    expect($response->viewData('mailboxes')->count())->toBe(0);
    expect($response->viewData('activeConversations'))->toBe(0);
    expect($response->viewData('unassignedConversations'))->toBe(0);
});

test('dashboard stats exclude closed conversations', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user);

    // Closed
    Conversation::factory()->count(3)->create([
        'mailbox_id' => $mailbox->id,
        'status' => Conversation::STATUS_CLOSED,
        'state' => 2,
    ]);

    // Active
    Conversation::factory()->create([
        'mailbox_id' => $mailbox->id,
        'status' => Conversation::STATUS_ACTIVE,
        'state' => 2,
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    expect($response->viewData('activeConversations'))->toBe(1);
});
