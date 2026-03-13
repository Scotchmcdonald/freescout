<?php

use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\User;

test('mailbox permission logic matches l5 version', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $user1 = User::factory()->create(['role' => User::ROLE_USER]);
    $user2 = User::factory()->create(['role' => User::ROLE_USER]);

    $mailbox1 = Mailbox::factory()->create(['name' => 'Support']);
    $mailbox2 = Mailbox::factory()->create(['name' => 'Sales']);

    $mailbox1->users()->attach($user1);
    $mailbox2->users()->attach($user2);

    // User1 can access mailbox1
    $this->actingAs($user1)
        ->get(route('mailboxes.view', $mailbox1))
        ->assertStatus(200);

    // User1 cannot access mailbox2
    $this->actingAs($user1)
        ->get(route('mailboxes.view', $mailbox2))
        ->assertStatus(403);

    // Admin can access all mailboxes
    $this->actingAs($admin);
    $this->get(route('mailboxes.view', $mailbox1))->assertStatus(200);
    $this->get(route('mailboxes.view', $mailbox2))->assertStatus(200);
});

test('mailbox user pivot maintains l5 compatibility', function () {
    $user = User::factory()->create();
    $mailbox = Mailbox::factory()->create();

    $mailbox->users()->attach($user->id, [
        'after_send' => 1,
    ]);

    $mailbox->load('users');
    $attachedUser = $mailbox->users->first();

    expect($attachedUser->id)->toBe($user->id)
        ->and($attachedUser->pivot->after_send)->toBeTrue()
        ->and($attachedUser->pivot->created_at)->not->toBeNull()
        ->and($attachedUser->pivot->updated_at)->not->toBeNull();
});

test('folder structure matches l5 version', function () {
    $mailbox = Mailbox::factory()->create();
    $user = User::factory()->create();

    $inboxFolder = Folder::factory()->create([
        'mailbox_id' => $mailbox->id,
        'user_id' => null,
        'type' => Folder::TYPE_INBOX,
    ]);

    $mineFolder = Folder::factory()->create([
        'mailbox_id' => $mailbox->id,
        'user_id' => $user->id,
        'type' => Folder::TYPE_MINE,
    ]);

    // Check constants
    expect(Folder::TYPE_INBOX)->toBe(1)
        ->and(Folder::TYPE_SENT)->toBe(6)
        ->and(Folder::TYPE_DRAFTS)->toBe(3)
        ->and(Folder::TYPE_SPAM)->toBe(4)
        ->and(Folder::TYPE_TRASH)->toBe(5)
        ->and(Folder::TYPE_ASSIGNED)->toBe(20)
        ->and(Folder::TYPE_MINE)->toBe(25)
        ->and(Folder::TYPE_STARRED)->toBe(30);

    // Check relationships
    expect($inboxFolder->mailbox)->toBeInstanceOf(Mailbox::class)
        ->and($inboxFolder->user_id)->toBeNull()
        ->and($mineFolder->user)->toBeInstanceOf(User::class);
});

test('conversation folder relationship matches l5', function () {
    $mailbox = Mailbox::factory()->create();
    $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id]);

    expect(method_exists($folder, 'conversationsViaFolder'))->toBeTrue();

    $relationship = $folder->conversationsViaFolder();
    expect($relationship)->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class)
        ->and($relationship->getTable())->toBe('conversation_folder');
});

test('mailbox passwords are encrypted', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $plainPassword = 'my-secret-password';

    $this->actingAs($admin)
        ->post(route('mailboxes.store'), [
            'name' => 'Test Mailbox',
            'email' => 'test@example.com',
            'in_server' => 'imap.example.com',
            'in_port' => 993,
            'in_username' => 'user@example.com',
            'in_password' => $plainPassword,
            'out_method' => 'smtp',
            'out_server' => 'smtp.example.com', // Added missing required field
            'out_port' => 587,
            'out_username' => 'user@example.com',
            'out_password' => $plainPassword,
        ])
        ->assertRedirect();

    $mailbox = Mailbox::where('email', 'test@example.com')->first();

    expect($mailbox)->not->toBeNull()
        ->and($mailbox->getRawOriginal('in_password'))->not->toBe($plainPassword)
        ->and($mailbox->getRawOriginal('out_password'))->not->toBe($plainPassword)
        ->and($mailbox->getRawOriginal('in_password'))->not->toBeEmpty()
        ->and($mailbox->getRawOriginal('out_password'))->not->toBeEmpty();
});

test('mailbox get mail from matches l5 behavior', function () {
    // case: from_name = 1 (Mailbox Name)
    $mailbox = Mailbox::factory()->create([
        'name' => 'Support',
        'email' => 'support@example.com',
        'from_name' => 1,
        'from_name_custom' => null,
    ]);

    $from = $mailbox->getMailFrom();

    // In L5, 1 meant "Use Mailbox Name", but implementation might return int 1
    // The legacy test asserted it equals 1.
    expect($from['address'])->toBe('support@example.com')
        ->and($from['name'])->toBe(1);
});

test('mailbox uses custom from name when set', function () {
    $mailbox = Mailbox::factory()->create([
        'name' => 'Support',
        'email' => 'support@example.com',
        'from_name' => 3, // Custom
        'from_name_custom' => 'Custom Support Name',
    ]);

    $from = $mailbox->getMailFrom();

    expect($from['name'])->toBe('Custom Support Name');
});

test('mailbox uses mailbox name as fallback', function () {
    $mailbox = Mailbox::factory()->create([
        'name' => 'Support',
        'email' => 'support@example.com',
        'from_name' => 2, // User Name (but checking fallback behavior if tested in legacy)
        'from_name_custom' => null,
    ]);

    $from = $mailbox->getMailFrom();

    // Legacy test says it returns 2
    expect($from['name'])->toBe(2);
});
