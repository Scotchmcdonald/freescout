<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Mailbox;
use App\Models\User;
use App\Services\ImapService;
use App\Services\SmtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsControllerMethodsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);
    }

    /**
     * Test testSmtp() method
     */
    public function test_admin_can_test_smtp_connection(): void
    {
        $this->actingAs($this->admin);

        $mailbox = Mailbox::factory()->create([
            'out_server' => 'smtp.example.com',
            'out_port' => 587,
            'out_username' => 'test@example.com',
            'out_password' => 'password',
        ]);

        $mockService = $this->mock(SmtpService::class);
        $mockService->shouldReceive('testConnection')
            ->once()
            ->withArgs(function ($mbx, $email) use ($mailbox) {
                return $mbx->id === $mailbox->id && $email === 'test@example.com';
            })
            ->andReturn([
                'success' => true,
                'message' => 'SMTP connection successful',
            ]);

        $response = $this->postJson(route('settings.test-smtp'), [
            'mailbox_id' => $mailbox->id,
            'test_email' => 'test@example.com',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => 'SMTP connection successful',
        ]);
    }

    public function test_test_smtp_validates_required_fields(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('settings.test-smtp'), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['mailbox_id', 'test_email']);
    }

    public function test_test_smtp_validates_email_format(): void
    {
        $this->actingAs($this->admin);

        $mailbox = Mailbox::factory()->create();

        $response = $this->postJson(route('settings.test-smtp'), [
            'mailbox_id' => $mailbox->id,
            'test_email' => 'invalid-email',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['test_email']);
    }

    public function test_test_smtp_fails_when_no_server_configured(): void
    {
        $this->actingAs($this->admin);

        $mailbox = Mailbox::factory()->create([
            'out_server' => null,
        ]);

        $response = $this->postJson(route('settings.test-smtp'), [
            'mailbox_id' => $mailbox->id,
            'test_email' => 'test@example.com',
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => 'No SMTP server configured for this mailbox.',
        ]);
    }

    public function test_test_smtp_handles_service_exceptions(): void
    {
        $this->actingAs($this->admin);

        $mailbox = Mailbox::factory()->create([
            'out_server' => 'smtp.example.com',
        ]);

        $mockService = $this->mock(SmtpService::class);
        $mockService->shouldReceive('testConnection')
            ->once()
            ->andThrow(new \Exception('Connection failed'));

        $response = $this->postJson(route('settings.test-smtp'), [
            'mailbox_id' => $mailbox->id,
            'test_email' => 'test@example.com',
        ]);

        $response->assertStatus(500);
        $response->assertJson([
            'success' => false,
        ]);
        $response->assertJsonFragment(['message' => 'Error: Connection failed']);
    }

    public function test_non_admin_cannot_test_smtp(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $this->actingAs($user);

        $mailbox = Mailbox::factory()->create(['out_server' => 'smtp.example.com']);

        $response = $this->postJson(route('settings.test-smtp'), [
            'mailbox_id' => $mailbox->id,
            'test_email' => 'test@example.com',
        ]);

        $response->assertForbidden();
    }

    /**
     * Test testImap() method
     */
    public function test_admin_can_test_imap_connection(): void
    {
        $this->actingAs($this->admin);

        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
            'in_port' => 993,
            'in_username' => 'test@example.com',
            'in_password' => 'password',
        ]);

        $mockService = $this->mock(ImapService::class);
        $mockService->shouldReceive('testConnection')
            ->once()
            ->withArgs(function ($mbx) use ($mailbox) {
                return $mbx->id === $mailbox->id;
            })
            ->andReturn([
                'success' => true,
                'message' => 'IMAP connection successful',
            ]);

        $response = $this->postJson(route('settings.test-imap'), [
            'mailbox_id' => $mailbox->id,
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => 'IMAP connection successful',
        ]);
    }

    public function test_test_imap_validates_required_mailbox_id(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('settings.test-imap'), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['mailbox_id']);
    }

    public function test_test_imap_fails_when_no_server_configured(): void
    {
        $this->actingAs($this->admin);

        $mailbox = Mailbox::factory()->create([
            'in_server' => null,
        ]);

        $response = $this->postJson(route('settings.test-imap'), [
            'mailbox_id' => $mailbox->id,
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => 'No IMAP server configured for this mailbox.',
        ]);
    }

    public function test_test_imap_handles_service_exceptions(): void
    {
        $this->actingAs($this->admin);

        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
        ]);

        $mockService = $this->mock(ImapService::class);
        $mockService->shouldReceive('testConnection')
            ->once()
            ->andThrow(new \Exception('Connection timeout'));

        $response = $this->postJson(route('settings.test-imap'), [
            'mailbox_id' => $mailbox->id,
        ]);

        $response->assertStatus(500);
        $response->assertJson([
            'success' => false,
        ]);
        $response->assertJsonFragment(['message' => 'Error: Connection timeout']);
    }

    public function test_non_admin_cannot_test_imap(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $this->actingAs($user);

        $mailbox = Mailbox::factory()->create(['in_server' => 'imap.example.com']);

        $response = $this->postJson(route('settings.test-imap'), [
            'mailbox_id' => $mailbox->id,
        ]);

        $response->assertForbidden();
    }

    public function test_guest_cannot_test_smtp(): void
    {
        $mailbox = Mailbox::factory()->create();

        $response = $this->postJson(route('settings.test-smtp'), [
            'mailbox_id' => $mailbox->id,
            'test_email' => 'test@example.com',
        ]);

        $response->assertUnauthorized();
    }

    public function test_guest_cannot_test_imap(): void
    {
        $mailbox = Mailbox::factory()->create();

        $response = $this->postJson(route('settings.test-imap'), [
            'mailbox_id' => $mailbox->id,
        ]);

        $response->assertUnauthorized();
    }
}
