<?php

declare(strict_types=1);

use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Support\Facades\Http;

test('oauth connect requires mailbox id', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)
        ->get(route('mailboxes.oauth_connect', ['provider' => 'ms']))
        ->assertRedirect()
        ->assertSessionHas('error');
});

test('oauth connect requires client id', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create(['in_username' => null]);

    $this->actingAs($admin)
        ->get(route('mailboxes.oauth_connect', [
            'provider' => 'ms',
            'mailbox_id' => $mailbox->id,
            'type' => 'incoming',
        ]))
        ->assertRedirect()
        ->assertSessionHas('error');
});

test('oauth connect redirects to microsoft', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create(['in_username' => 'client-id-123']);

    $response = $this->actingAs($admin)
        ->get(route('mailboxes.oauth_connect', [
            'provider' => 'ms',
            'mailbox_id' => $mailbox->id,
            'type' => 'incoming',
        ]));

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('login.microsoftonline.com');
});

test('oauth connect stores mailbox in session', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create(['in_username' => 'client-id-123']);

    $this->actingAs($admin)
        ->get(route('mailboxes.oauth_connect', [
            'provider' => 'ms',
            'mailbox_id' => $mailbox->id,
            'type' => 'incoming',
        ]));

    expect(session('oauth_mailbox_id'))->toBe($mailbox->id)
        ->and(session('oauth_type'))->toBe('incoming');
});

test('oauth callback handles error', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)
        ->get(route('mailboxes.oauth_callback', ['error' => 'access_denied']))
        ->assertRedirect(route('mailboxes.index'))
        ->assertSessionHas('error');
});

test('oauth callback requires state', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)
        ->get(route('mailboxes.oauth_callback', ['code' => 'test-code']))
        ->assertRedirect(route('mailboxes.index'));
});

test('oauth callback success', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create(['in_username' => 'client-id-123']);

    Http::fake([
        'login.microsoftonline.com/*' => Http::response([
            'access_token' => 'test-access-token',
            'refresh_token' => 'test-refresh-token',
            'expires_in' => 3600,
        ], 200),
    ]);

    session(['oauth_mailbox_id' => $mailbox->id]);
    session(['oauth_type' => 'incoming']);

    $this->actingAs($admin)
        ->get(route('mailboxes.oauth_callback', [
            'code' => 'test-authorization-code',
            'state' => $mailbox->id,
        ]))
        ->assertRedirect()
        ->assertSessionHas('success');

    $mailbox->refresh();
    expect($mailbox->meta)->toHaveKey('oauth')
        ->and($mailbox->meta['oauth']['a_token'])->toBe('test-access-token'); // Assuming meta structure logic based on legacy test context
});

test('oauth callback handles token error', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create(['in_username' => 'client-id-123']);

    Http::fake([
        'login.microsoftonline.com/*' => Http::response([
            'error' => 'invalid_grant',
        ], 400),
    ]);

    session(['oauth_mailbox_id' => $mailbox->id]);
    session(['oauth_type' => 'incoming']);

    $this->actingAs($admin)
        ->get(route('mailboxes.oauth_callback', [
            'code' => 'invalid-code',
            'state' => $mailbox->id,
        ]))
        ->assertRedirect()
        ->assertSessionHas('error');
});

test('oauth disconnect clears oauth data', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();

    $mailbox->meta = ['oauth' => ['a_token' => 'test']];
    $mailbox->save();

    $this->actingAs($admin)
        ->get(route('mailboxes.oauth_disconnect', $mailbox))
        ->assertRedirect()
        ->assertSessionHas('success');

    $mailbox->refresh();
    $meta = $mailbox->meta ?? [];
    expect(isset($meta['oauth']))->toBeFalse();
});

test('oauth disconnect requires authorization', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    $mailbox = Mailbox::factory()->create();

    $this->actingAs($user)
        ->get(route('mailboxes.oauth_disconnect', $mailbox))
        ->assertForbidden();
});

test('oauth connect for outgoing type', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create(['out_username' => 'smtp-client-id']);

    $this->actingAs($admin)
        ->get(route('mailboxes.oauth_connect', [
            'provider' => 'ms',
            'mailbox_id' => $mailbox->id,
            'type' => 'outgoing',
        ]))
        ->assertRedirect();

    expect(session('oauth_type'))->toBe('outgoing');
});
