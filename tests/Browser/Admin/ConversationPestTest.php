<?php

use App\Models\Conversation;
use App\Models\Mailbox;
use App\Models\User;

function getConvPagesAdmin(): User
{
    $admin = User::firstOrCreate(['email' => 'conversation-admin@example.com'], [
        'password' => bcrypt('password'),
        'role' => User::ROLE_ADMIN,
        'first_name' => 'ConversationTest',
        'last_name' => 'Admin',
        'email_verified_at' => now(),
    ]);
    if (! $admin->isAdmin()) {
        $admin->role = User::ROLE_ADMIN;
        $admin->save();
    }

    return $admin;
}

it('conversation list via mailbox loads', function () {
    $admin = getConvPagesAdmin();
    $mailbox = Mailbox::factory()->create(['name' => 'ConvTestMailbox']);
    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/mailbox/'.$mailbox->id)
        ->assertSee('ConvTestMailbox');
})->group('admin', 'conversations');

it('conversation search page loads', function () {
    $admin = getConvPagesAdmin();
    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/conversations/search?q=test')
        ->assertSee('Search');
})->group('admin', 'conversations');

it('conversation create page loads', function () {
    $admin = getConvPagesAdmin();
    $mailbox = Mailbox::factory()->create(['name' => 'CreateConvMailbox']);
    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/mailbox/'.$mailbox->id.'/conversation/create')
        ->assertSee('New Conversation');
})->group('admin', 'conversations');

it('conversation model factory works', function () {
    $conversation = Conversation::factory()->create();
    expect($conversation->id)->toBeGreaterThan(0);
    expect($conversation->getTable())->toBe('conversations');
    expect(method_exists($conversation, 'mailbox'))->toBeTrue();
    expect(method_exists($conversation, 'customer'))->toBeTrue();
})->group('admin', 'conversations');
