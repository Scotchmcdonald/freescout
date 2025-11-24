<?php

namespace Tests\Feature;

use App\Models\Mailbox;
use App\Models\User;
use App\Services\ImapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ConnectionDiagnosticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create admin user
        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    public function test_connection_test_success()
    {
        $mailbox = Mailbox::factory()->create();

        // Mock ImapService
        $mockImapService = Mockery::mock(ImapService::class);
        $mockImapService->shouldReceive('testConnection')
            ->with(Mockery::on(function ($arg) use ($mailbox) {
                return $arg->id === $mailbox->id;
            }))
            ->andReturn([
                'success' => true,
                'message' => 'Connected successfully',
            ]);

        $this->app->instance(ImapService::class, $mockImapService);

        $response = $this->actingAs($this->admin)->postJson(route('mailboxes.ajax'), [
            'action' => 'fetch_test',
            'mailbox_id' => $mailbox->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'msg_success' => 'Connected successfully',
        ]);
    }

    public function test_connection_test_failure()
    {
        $mailbox = Mailbox::factory()->create();

        // Mock ImapService
        $mockImapService = Mockery::mock(ImapService::class);
        $mockImapService->shouldReceive('testConnection')
            ->with(Mockery::on(function ($arg) use ($mailbox) {
                return $arg->id === $mailbox->id;
            }))
            ->andReturn([
                'success' => false,
                'message' => 'Connection failed',
            ]);

        $this->app->instance(ImapService::class, $mockImapService);

        $response = $this->actingAs($this->admin)->postJson(route('mailboxes.ajax'), [
            'action' => 'fetch_test',
            'mailbox_id' => $mailbox->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'error',
            'msg' => 'Connection failed',
        ]);
    }

    public function test_connection_test_unauthorized()
    {
        $mailbox = Mailbox::factory()->create();
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $response = $this->actingAs($user)->postJson(route('mailboxes.ajax'), [
            'action' => 'fetch_test',
            'mailbox_id' => $mailbox->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'error',
            'msg' => 'Not enough permissions',
        ]);
    }

    public function test_send_test_email_success()
    {
        $mailbox = Mailbox::factory()->create();
        $testEmail = 'test@example.com';

        // Mock SmtpService
        $mockSmtpService = Mockery::mock(\App\Services\SmtpService::class);
        $mockSmtpService->shouldReceive('testConnection')
            ->with(Mockery::on(function ($arg) use ($mailbox) {
                return $arg->id === $mailbox->id;
            }), $testEmail)
            ->andReturn([
                'success' => true,
                'message' => 'Test email sent successfully',
            ]);

        $this->app->instance(\App\Services\SmtpService::class, $mockSmtpService);

        $response = $this->actingAs($this->admin)->postJson(route('mailboxes.send_test_email', $mailbox), [
            'test_email' => $testEmail,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'message' => 'Test email sent successfully',
        ]);
    }

    public function test_send_test_email_failure()
    {
        $mailbox = Mailbox::factory()->create();
        $testEmail = 'test@example.com';

        // Mock SmtpService
        $mockSmtpService = Mockery::mock(\App\Services\SmtpService::class);
        $mockSmtpService->shouldReceive('testConnection')
            ->with(Mockery::on(function ($arg) use ($mailbox) {
                return $arg->id === $mailbox->id;
            }), $testEmail)
            ->andReturn([
                'success' => false,
                'message' => 'SMTP connection error',
            ]);

        $this->app->instance(\App\Services\SmtpService::class, $mockSmtpService);

        $response = $this->actingAs($this->admin)->postJson(route('mailboxes.send_test_email', $mailbox), [
            'test_email' => $testEmail,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'error',
            'message' => 'SMTP connection error',
        ]);
    }
}
