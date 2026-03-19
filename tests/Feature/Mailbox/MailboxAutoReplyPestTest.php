<?php

use App\Models\Mailbox;
use App\Models\User;

test('admin can view auto-reply settings page', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();

    $this->actingAs($admin)
        ->get(route('mailboxes.auto_reply', $mailbox))
        ->assertStatus(200)
        ->assertViewIs('mailboxes.auto_reply');
});

test('non-admin cannot view auto-reply settings page', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    $mailbox = Mailbox::factory()->create();

    $this->actingAs($user)
        ->get(route('mailboxes.auto_reply', $mailbox))
        ->assertStatus(403);
});

test('admin can enable auto-reply with required fields', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();

    $this->actingAs($admin)
        ->post(route('mailboxes.auto_reply.save', $mailbox), [
            'auto_reply_enabled' => true,
            'auto_reply_subject' => 'Thank you for your message',
            'auto_reply_message' => 'We have received your message and will respond within 24 hours.',
        ])
        ->assertRedirect(route('mailboxes.auto_reply', $mailbox))
        ->assertSessionHas('success');

    $mailbox->refresh();
    expect($mailbox->auto_reply_enabled)->toBeTrue()
        ->and($mailbox->auto_reply_subject)->toBe('Thank you for your message')
        ->and($mailbox->auto_reply_message)->toBe('We have received your message and will respond within 24 hours.');
});

test('admin can disable auto-reply', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create([
        'auto_reply_enabled' => true,
        'auto_reply_subject' => 'Old Subject',
        'auto_reply_message' => 'Old Message',
    ]);

    // Omit auto_reply_enabled to disable it
    $this->actingAs($admin)
        ->post(route('mailboxes.auto_reply.save', $mailbox), [
            'auto_reply_subject' => '',
            'auto_reply_message' => '',
        ])
        ->assertRedirect(route('mailboxes.auto_reply', $mailbox));

    $mailbox->refresh();
    expect($mailbox->auto_reply_enabled)->toBeFalse();
});

test('auto-reply requires subject when enabled', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();

    $this->actingAs($admin)
        ->post(route('mailboxes.auto_reply.save', $mailbox), [
            'auto_reply_enabled' => true,
            'auto_reply_subject' => '', // Missing subject
            'auto_reply_message' => 'Some message',
        ])
        ->assertSessionHasErrors('auto_reply_subject');
});

test('auto-reply requires message when enabled', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();

    $this->actingAs($admin)
        ->post(route('mailboxes.auto_reply.save', $mailbox), [
            'auto_reply_enabled' => true,
            'auto_reply_subject' => 'Thank you',
            'auto_reply_message' => '', // Missing message
        ])
        ->assertSessionHasErrors('auto_reply_message');
});

test('auto-reply subject has max length', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();

    $this->actingAs($admin)
        ->post(route('mailboxes.auto_reply.save', $mailbox), [
            'auto_reply_enabled' => true,
            'auto_reply_subject' => str_repeat('A', 129), // Exceeds 128 char limit
            'auto_reply_message' => 'Message',
        ])
        ->assertSessionHasErrors('auto_reply_subject');
});

test('auto-reply can include auto bcc email', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();

    $this->actingAs($admin)
        ->post(route('mailboxes.auto_reply.save', $mailbox), [
            'auto_reply_enabled' => true,
            'auto_reply_subject' => 'Thank you',
            'auto_reply_message' => 'We will respond soon',
            'auto_bcc' => 'archive@example.com',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $mailbox->refresh();
    expect($mailbox->auto_bcc)->toBe('archive@example.com');
});

test('auto bcc must be valid email', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();

    $this->actingAs($admin)
        ->post(route('mailboxes.auto_reply.save', $mailbox), [
            'auto_reply_enabled' => true,
            'auto_reply_subject' => 'Thank you',
            'auto_reply_message' => 'Message',
            'auto_bcc' => 'not-an-email',
        ])
        ->assertSessionHasErrors('auto_bcc');
});

test('non-admin cannot save auto-reply settings', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    $mailbox = Mailbox::factory()->create();

    $this->actingAs($user)
        ->post(route('mailboxes.auto_reply.save', $mailbox), [
            'auto_reply_enabled' => true,
            'auto_reply_subject' => 'Subject',
            'auto_reply_message' => 'Message',
        ])
        ->assertStatus(403);
});
