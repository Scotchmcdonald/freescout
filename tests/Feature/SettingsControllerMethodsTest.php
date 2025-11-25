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

    /**
     * Edge case tests for testSmtp() and testImap()
     */
    public function test_test_smtp_with_invalid_mailbox_id(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('settings.test-smtp'), [
            'mailbox_id' => 99999, // Non-existent mailbox
            'test_email' => 'test@example.com',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['mailbox_id']);
    }

    public function test_test_imap_with_invalid_mailbox_id(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('settings.test-imap'), [
            'mailbox_id' => 99999, // Non-existent mailbox
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['mailbox_id']);
    }

    public function test_test_smtp_with_non_numeric_mailbox_id(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('settings.test-smtp'), [
            'mailbox_id' => 'invalid',
            'test_email' => 'test@example.com',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['mailbox_id']);
    }

    public function test_test_smtp_service_returns_failure(): void
    {
        $this->actingAs($this->admin);

        $mailbox = Mailbox::factory()->create([
            'out_server' => 'smtp.example.com',
        ]);

        $mockService = $this->mock(SmtpService::class);
        $mockService->shouldReceive('testConnection')
            ->once()
            ->andReturn([
                'success' => false,
                'message' => 'Authentication failed',
            ]);

        $response = $this->postJson(route('settings.test-smtp'), [
            'mailbox_id' => $mailbox->id,
            'test_email' => 'test@example.com',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => false,
            'message' => 'Authentication failed',
        ]);
    }

    public function test_test_imap_service_returns_failure(): void
    {
        $this->actingAs($this->admin);

        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
        ]);

        $mockService = $this->mock(ImapService::class);
        $mockService->shouldReceive('testConnection')
            ->once()
            ->andReturn([
                'success' => false,
                'message' => 'Connection refused',
            ]);

        $response = $this->postJson(route('settings.test-imap'), [
            'mailbox_id' => $mailbox->id,
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => false,
            'message' => 'Connection refused',
        ]);
    }

    public function test_test_smtp_with_empty_server_string(): void
    {
        $this->actingAs($this->admin);

        $mailbox = Mailbox::factory()->create([
            'out_server' => '', // Empty string
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

    public function test_test_imap_with_empty_server_string(): void
    {
        $this->actingAs($this->admin);

        $mailbox = Mailbox::factory()->create([
            'in_server' => '', // Empty string
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

    public function test_test_smtp_with_multiple_validation_errors(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('settings.test-smtp'), [
            'mailbox_id' => 'invalid',
            'test_email' => 'not-an-email',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['mailbox_id', 'test_email']);
    }

    public function test_test_smtp_with_special_characters_in_email(): void
    {
        $this->actingAs($this->admin);

        $mailbox = Mailbox::factory()->create([
            'out_server' => 'smtp.example.com',
        ]);

        $mockService = $this->mock(SmtpService::class);
        $mockService->shouldReceive('testConnection')
            ->once()
            ->andReturn(['success' => true, 'message' => 'Success']);

        // Test with valid special characters in email
        $response = $this->postJson(route('settings.test-smtp'), [
            'mailbox_id' => $mailbox->id,
            'test_email' => 'test+tag@example.co.uk',
        ]);

        $response->assertOk();
    }

    /**
     * Tests for alerts() method
     */
    public function test_admin_can_view_alerts_settings(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('settings.alerts'));

        $response->assertOk();
        $response->assertViewIs('settings.alerts');
        $response->assertViewHas('settings');
        $response->assertViewHas('sections');
        $response->assertViewHas('currentSection', 'alerts');
    }

    public function test_non_admin_cannot_view_alerts_settings(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $this->actingAs($user);

        $response = $this->get(route('settings.alerts'));

        $response->assertForbidden();
    }

    public function test_guest_cannot_view_alerts_settings(): void
    {
        $response = $this->get(route('settings.alerts'));

        $response->assertRedirect(route('login'));
    }

    /**
     * Tests for updateAlerts() method
     */
    public function test_admin_can_update_alert_settings(): void
    {
        $this->actingAs($this->admin);

        $response = $this->put(route('settings.alerts.update'), [
            'alerts' => [
                'system_errors' => true,
                'high_queue' => true,
                'failed_jobs' => false,
                'disk_space' => true,
                'db_connection' => false,
            ],
            'queue_threshold' => 200,
            'alert_recipients' => "admin@example.com\ntech@example.com",
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('options', [
            'name' => 'alert_system_errors',
            'value' => '1',
        ]);
        $this->assertDatabaseHas('options', [
            'name' => 'alert_high_queue',
            'value' => '1',
        ]);
        $this->assertDatabaseHas('options', [
            'name' => 'alert_failed_jobs',
            'value' => '0',
        ]);
        $this->assertDatabaseHas('options', [
            'name' => 'queue_threshold',
            'value' => '200',
        ]);
    }

    public function test_update_alerts_validates_queue_threshold_minimum(): void
    {
        $this->actingAs($this->admin);

        $response = $this->put(route('settings.alerts.update'), [
            'queue_threshold' => 5, // Below minimum of 10
        ]);

        $response->assertSessionHasErrors(['queue_threshold']);
    }

    public function test_update_alerts_validates_queue_threshold_maximum(): void
    {
        $this->actingAs($this->admin);

        $response = $this->put(route('settings.alerts.update'), [
            'queue_threshold' => 20000, // Above maximum of 10000
        ]);

        $response->assertSessionHasErrors(['queue_threshold']);
    }

    public function test_update_alerts_with_empty_alerts_array(): void
    {
        $this->actingAs($this->admin);

        // First enable some alerts
        \App\Models\Option::updateOrCreate(['name' => 'alert_system_errors'], ['value' => '1']);
        \App\Models\Option::updateOrCreate(['name' => 'alert_failed_jobs'], ['value' => '1']);

        // Then disable all by not including them
        $response = $this->put(route('settings.alerts.update'), [
            'alerts' => [],
            'alert_recipients' => 'admin@example.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // All alerts should be disabled
        $this->assertDatabaseHas('options', [
            'name' => 'alert_system_errors',
            'value' => '0',
        ]);
        $this->assertDatabaseHas('options', [
            'name' => 'alert_failed_jobs',
            'value' => '0',
        ]);
    }

    public function test_update_alerts_with_no_alerts_key(): void
    {
        $this->actingAs($this->admin);

        $response = $this->put(route('settings.alerts.update'), [
            'alert_recipients' => 'admin@example.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_non_admin_cannot_update_alerts(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $this->actingAs($user);

        $response = $this->put(route('settings.alerts.update'), [
            'alerts' => ['system_errors' => true],
        ]);

        $response->assertForbidden();
    }

    public function test_guest_cannot_update_alerts(): void
    {
        $response = $this->put(route('settings.alerts.update'), [
            'alerts' => ['system_errors' => true],
        ]);

        $response->assertRedirect(route('login'));
    }

    /**
     * Tests for sendTestAlert via updateAlerts()
     */
    public function test_admin_can_send_test_alert(): void
    {
        $this->actingAs($this->admin);

        \Illuminate\Support\Facades\Mail::fake();

        $response = $this->put(route('settings.alerts.update'), [
            'action' => 'test',
            'alert_recipients' => 'admin@example.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\Alert::class, function ($mail) {
            return $mail->hasTo('admin@example.com');
        });
    }

    public function test_send_test_alert_with_multiple_recipients(): void
    {
        $this->actingAs($this->admin);

        \Illuminate\Support\Facades\Mail::fake();

        $response = $this->put(route('settings.alerts.update'), [
            'action' => 'test',
            'alert_recipients' => "admin@example.com\ntech@example.com\nops@example.com",
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\Alert::class, 3);
    }

    public function test_send_test_alert_with_no_recipients(): void
    {
        $this->actingAs($this->admin);

        $response = $this->put(route('settings.alerts.update'), [
            'action' => 'test',
            'alert_recipients' => '',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'No recipients configured for alerts.');
    }

    public function test_send_test_alert_filters_invalid_emails(): void
    {
        $this->actingAs($this->admin);

        \Illuminate\Support\Facades\Mail::fake();

        $response = $this->put(route('settings.alerts.update'), [
            'action' => 'test',
            'alert_recipients' => "valid@example.com\ninvalid-email\nvalid2@example.com",
        ]);

        $response->assertRedirect();

        // Only valid emails should receive the test alert
        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\Alert::class, 2);
    }

    public function test_send_test_alert_returns_error_when_all_emails_invalid(): void
    {
        $this->actingAs($this->admin);

        \Illuminate\Support\Facades\Mail::fake();

        $response = $this->put(route('settings.alerts.update'), [
            'action' => 'test',
            'alert_recipients' => "invalid-email-1\ninvalid-email-2\nnot-an-email",
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'No valid email addresses found in recipients.');

        // No emails should be sent
        \Illuminate\Support\Facades\Mail::assertNothingSent();
    }

    public function test_send_test_alert_handles_mail_exception(): void
    {
        $this->actingAs($this->admin);

        \Illuminate\Support\Facades\Mail::shouldReceive('to->send')
            ->andThrow(new \Exception('Mail server unavailable'));

        $response = $this->put(route('settings.alerts.update'), [
            'action' => 'test',
            'alert_recipients' => 'admin@example.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    /**
     * Tests for alert recipients edge cases
     */
    public function test_update_alerts_with_whitespace_in_recipients(): void
    {
        $this->actingAs($this->admin);

        $response = $this->put(route('settings.alerts.update'), [
            'alert_recipients' => "  admin@example.com  \n  tech@example.com  ",
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('options', [
            'name' => 'alert_recipients',
        ]);
    }

    public function test_update_alerts_with_empty_lines_in_recipients(): void
    {
        $this->actingAs($this->admin);

        $response = $this->put(route('settings.alerts.update'), [
            'alert_recipients' => "admin@example.com\n\n\ntech@example.com",
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_update_alerts_preserves_previous_values(): void
    {
        $this->actingAs($this->admin);

        // Set initial values
        \App\Models\Option::updateOrCreate(['name' => 'alert_system_errors'], ['value' => '1']);
        \App\Models\Option::updateOrCreate(['name' => 'queue_threshold'], ['value' => '150']);

        // Update only queue threshold
        $response = $this->put(route('settings.alerts.update'), [
            'alerts' => ['system_errors' => true],
            'queue_threshold' => 300,
        ]);

        $response->assertRedirect();
        
        $this->assertDatabaseHas('options', [
            'name' => 'alert_system_errors',
            'value' => '1',
        ]);
        $this->assertDatabaseHas('options', [
            'name' => 'queue_threshold',
            'value' => '300',
        ]);
    }

    public function test_alerts_view_loads_existing_settings(): void
    {
        $this->actingAs($this->admin);

        // Set some options
        \App\Models\Option::updateOrCreate(['name' => 'alert_system_errors'], ['value' => '1']);
        \App\Models\Option::updateOrCreate(['name' => 'queue_threshold'], ['value' => '250']);
        \App\Models\Option::updateOrCreate(['name' => 'alert_recipients'], ['value' => 'admin@test.com']);

        $response = $this->get(route('settings.alerts'));

        $response->assertOk();
        $response->assertViewHas('settings', function ($settings) {
            return isset($settings['alert_system_errors']) && $settings['alert_system_errors'] == '1'
                && isset($settings['queue_threshold']) && $settings['queue_threshold'] == '250'
                && isset($settings['alert_recipients']) && $settings['alert_recipients'] == 'admin@test.com';
        });
    }

    /**
     * Tests for validateSmtp() method
     */
    public function test_validate_smtp_returns_success_for_valid_settings(): void
    {
        $this->actingAs($this->admin);

        $mockService = $this->mock(SmtpService::class);
        $mockService->shouldReceive('validateSettings')
            ->once()
            ->andReturn([]);

        $response = $this->postJson(route('settings.validate-smtp'), [
            'host' => 'smtp.example.com',
            'port' => 587,
            'username' => 'user@example.com',
            'password' => 'secret',
            'encryption' => 'tls',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => 'SMTP settings are valid.',
        ]);
    }

    public function test_validate_smtp_returns_errors_for_invalid_settings(): void
    {
        $this->actingAs($this->admin);

        $mockService = $this->mock(SmtpService::class);
        $mockService->shouldReceive('validateSettings')
            ->once()
            ->andReturn([
                'host' => 'Host is required',
                'port' => 'Invalid port number',
            ]);

        $response = $this->postJson(route('settings.validate-smtp'), [
            'port' => -1,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'errors' => [
                'host' => 'Host is required',
                'port' => 'Invalid port number',
            ],
        ]);
    }

    public function test_non_admin_cannot_validate_smtp(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $this->actingAs($user);

        $response = $this->postJson(route('settings.validate-smtp'), [
            'host' => 'smtp.example.com',
        ]);

        $response->assertForbidden();
    }
}
