<?php

use App\Models\Mailbox;
use App\Models\User;

function getMailboxMgmtAdmin(): User
{
    $admin = User::firstOrCreate(['email' => 'mailboxmgmt-admin@example.com'], [
        'password' => bcrypt('password'),
        'role' => User::ROLE_ADMIN,
        'first_name' => 'MailboxMgmtTest',
        'last_name' => 'Admin',
        'email_verified_at' => now(),
    ]);
    if (! $admin->isAdmin()) {
        $admin->role = User::ROLE_ADMIN;
        $admin->save();
    }

    return $admin;
}

it('mailbox list page loads', function () {
    $admin = getMailboxMgmtAdmin();
    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/mailboxes')
        ->assertSee('Mailbox');
})->group('admin', 'mailboxes');

it('mailbox create page loads', function () {
    $admin = getMailboxMgmtAdmin();
    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/mailboxes/create')
        ->assertSee('Create a Mailbox');
})->group('admin', 'mailboxes');

it('mailbox settings page loads', function () {
    $admin = getMailboxMgmtAdmin();
    $mailbox = Mailbox::factory()->create();
    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/mailbox/'.$mailbox->id.'/settings')
        ->assertSee('Mailbox Settings');
})->group('admin', 'mailboxes');

it('mailbox model has required attributes', function () {
    $mailbox = Mailbox::factory()->create();
    expect($mailbox->id)->toBeGreaterThan(0);
    expect($mailbox->getTable())->toBe('mailboxes');
    expect($mailbox->name)->not->toBeEmpty();
    expect($mailbox->email)->not->toBeEmpty();
})->group('admin', 'mailboxes');
