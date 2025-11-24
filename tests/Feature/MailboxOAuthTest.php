<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Misc\OAuth;
use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MailboxOAuthTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Mailbox $mailbox;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->mailbox = Mailbox::factory()->create([
            'in_username' => 'client-id-123',
            'in_password' => encrypt('client-secret'),
            'out_username' => 'smtp-client-id',
            'out_password' => encrypt('smtp-secret'),
        ]);
    }

    public function test_oauth_connect_requires_mailbox_id(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('mailboxes.oauth_connect', ['provider' => 'ms']));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_oauth_connect_requires_client_id(): void
    {
        $mailboxNoClientId = Mailbox::factory()->create([
            'in_username' => null,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('mailboxes.oauth_connect', [
                'provider' => 'ms',
                'mailbox_id' => $mailboxNoClientId->id,
                'type' => 'incoming',
            ]));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_oauth_connect_redirects_to_microsoft(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('mailboxes.oauth_connect', [
                'provider' => 'ms',
                'mailbox_id' => $this->mailbox->id,
                'type' => 'incoming',
            ]));

        $response->assertRedirect();
        $this->assertStringContainsString('login.microsoftonline.com', $response->headers->get('Location'));
    }

    public function test_oauth_connect_stores_mailbox_in_session(): void
    {
        $this->actingAs($this->admin)
            ->get(route('mailboxes.oauth_connect', [
                'provider' => 'ms',
                'mailbox_id' => $this->mailbox->id,
                'type' => 'incoming',
            ]));

        $this->assertEquals($this->mailbox->id, session('oauth_mailbox_id'));
        $this->assertEquals('incoming', session('oauth_type'));
    }

    public function test_oauth_callback_handles_error(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('mailboxes.oauth_callback', [
                'error' => 'access_denied',
            ]));

        $response->assertRedirect(route('mailboxes.index'));
        $response->assertSessionHas('error');
    }

    public function test_oauth_callback_requires_state(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('mailboxes.oauth_callback', [
                'code' => 'test-code',
            ]));

        // Without state in session or request, should redirect with error
        $response->assertRedirect(route('mailboxes.index'));
    }

    public function test_oauth_callback_success(): void
    {
        Http::fake([
            'login.microsoftonline.com/*' => Http::response([
                'access_token' => 'test-access-token',
                'refresh_token' => 'test-refresh-token',
                'expires_in' => 3600,
            ], 200),
        ]);

        session(['oauth_mailbox_id' => $this->mailbox->id]);
        session(['oauth_type' => 'incoming']);

        $response = $this->actingAs($this->admin)
            ->get(route('mailboxes.oauth_callback', [
                'code' => 'test-authorization-code',
                'state' => $this->mailbox->id,
            ]));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify OAuth data was saved
        $this->mailbox->refresh();
        $this->assertArrayHasKey('oauth', $this->mailbox->meta);
    }

    public function test_oauth_callback_handles_token_error(): void
    {
        Http::fake([
            'login.microsoftonline.com/*' => Http::response([
                'error' => 'invalid_grant',
            ], 400),
        ]);

        session(['oauth_mailbox_id' => $this->mailbox->id]);
        session(['oauth_type' => 'incoming']);

        $response = $this->actingAs($this->admin)
            ->get(route('mailboxes.oauth_callback', [
                'code' => 'invalid-code',
                'state' => $this->mailbox->id,
            ]));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_oauth_disconnect_clears_oauth_data(): void
    {
        // Set up mailbox with OAuth data
        $this->mailbox->meta = ['oauth' => ['a_token' => 'test']];
        $this->mailbox->save();

        $response = $this->actingAs($this->admin)
            ->post(route('mailboxes.oauth_disconnect', $this->mailbox));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->mailbox->refresh();
        $this->assertArrayNotHasKey('oauth', $this->mailbox->meta ?? []);
    }

    public function test_oauth_disconnect_requires_authorization(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $response = $this->actingAs($user)
            ->post(route('mailboxes.oauth_disconnect', $this->mailbox));

        $response->assertForbidden();
    }

    public function test_oauth_connect_for_outgoing_type(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('mailboxes.oauth_connect', [
                'provider' => 'ms',
                'mailbox_id' => $this->mailbox->id,
                'type' => 'outgoing',
            ]));

        $response->assertRedirect();
        $this->assertEquals('outgoing', session('oauth_type'));
    }
}
