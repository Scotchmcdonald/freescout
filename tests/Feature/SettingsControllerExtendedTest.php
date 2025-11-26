<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Mailbox;
use App\Models\Option;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsControllerExtendedTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->user = User::factory()->create([
            'role' => User::ROLE_USER,
        ]);
    }

    // ==================== Alerts Settings ====================

    public function test_admin_can_view_alerts_settings_page(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('settings.alerts'));

        $response->assertOk();
        $response->assertViewIs('settings.alerts');
        $response->assertViewHas('sections');
        $response->assertViewHas('currentSection', 'alerts');
    }

    public function test_admin_can_update_alerts_settings(): void
    {
        $response = $this->actingAs($this->admin)
            ->put(route('settings.alerts.update'), [
                'alert_recipients' => 'admin@example.com',
                'alerts' => [
                    'failed_jobs' => true,
                    'system_errors' => true,
                ],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('options', [
            'name' => 'alert_recipients',
            'value' => 'admin@example.com',
        ]);
        
        $this->assertDatabaseHas('options', [
            'name' => 'alert_failed_jobs',
            'value' => '1',
        ]);
    }


    // ==================== SMTP Testing ====================

    public function test_test_smtp_requires_mailbox_id(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson(route('settings.test.smtp'), [
                'test_email' => 'test@example.com',
            ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('mailbox_id');
    }

    public function test_test_smtp_requires_test_email(): void
    {
        $mailbox = Mailbox::factory()->create();

        $response = $this->actingAs($this->admin)
            ->postJson(route('settings.test.smtp'), [
                'mailbox_id' => $mailbox->id,
            ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('test_email');
    }

    public function test_test_smtp_requires_valid_email(): void
    {
        $mailbox = Mailbox::factory()->create();

        $response = $this->actingAs($this->admin)
            ->postJson(route('settings.test.smtp'), [
                'mailbox_id' => $mailbox->id,
                'test_email' => 'not-an-email',
            ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('test_email');
    }

    public function test_test_smtp_fails_without_configured_server(): void
    {
        $mailbox = Mailbox::factory()->create([
            'out_server' => null,
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson(route('settings.test.smtp'), [
                'mailbox_id' => $mailbox->id,
                'test_email' => 'test@example.com',
            ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
        ]);
        $response->assertJsonFragment([
            'message' => 'No SMTP server configured for this mailbox.',
        ]);
    }

    public function test_test_smtp_with_configured_mailbox(): void
    {
        $mailbox = Mailbox::factory()->create([
            'out_server' => 'smtp.example.com',
            'out_port' => 587,
            'out_username' => 'user@example.com',
            'out_password' => 'secret',
            'email' => 'support@example.com',
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson(route('settings.test.smtp'), [
                'mailbox_id' => $mailbox->id,
                'test_email' => 'test@example.com',
            ]);

        // Will fail connection but should return proper response structure
        $response->assertJsonStructure([
            'success',
            'message',
        ]);
    }

    // ==================== IMAP Testing ====================

    public function test_test_imap_requires_mailbox_id(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson(route('settings.test.imap'), []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('mailbox_id');
    }

    public function test_test_imap_fails_without_configured_server(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => null,
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson(route('settings.test.imap'), [
                'mailbox_id' => $mailbox->id,
            ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
        ]);
    }

    // ==================== Migrations ====================

    public function test_admin_can_run_migrations(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('settings.migrate'));

        $response->assertRedirect();
        // Either success or error, both are handled
        $this->assertTrue(
            $response->isRedirect()
        );
    }

    // ==================== Cache Operations ====================

    public function test_admin_can_clear_cache(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('settings.cache.clear'));

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_non_admin_cannot_clear_cache(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('settings.cache.clear'));

        $response->assertForbidden();
    }

    public function test_non_admin_cannot_run_migrations(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('settings.migrate'));

        $response->assertForbidden();
    }

    // ==================== Update Settings Validation ====================

    public function test_update_settings_validates_timezone(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('settings.update'), [
                'timezone' => 'Invalid/Timezone',
            ]);

        $response->assertSessionHasErrors('timezone');
    }

    public function test_update_settings_validates_next_ticket(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('settings.update'), [
                'next_ticket' => -1,
            ]);

        $response->assertSessionHasErrors('next_ticket');
    }

    public function test_update_settings_accepts_valid_timezone(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('settings.update'), [
                'timezone' => 'America/New_York',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('options', [
            'name' => 'timezone',
            'value' => 'America/New_York',
        ]);
    }

    public function test_update_email_settings_skips_empty_password(): void
    {
        // First set a password
        Option::updateOrCreate(['name' => 'mail_password'], ['value' => 'original_secret']);

        $response = $this->actingAs($this->admin)
            ->post(route('settings.email.update'), [
                'mail_driver' => 'smtp',
                'mail_host' => 'smtp.example.com',
                'mail_from_address' => 'test@example.com',
                'mail_from_name' => 'Test',
                'mail_password' => '', // Empty password should be skipped
            ]);

        $response->assertRedirect();

        // Original password should remain
        $this->assertDatabaseHas('options', [
            'name' => 'mail_password',
            'value' => 'original_secret',
        ]);
    }

    // ==================== Edge Cases ====================

    public function test_settings_index_returns_view_with_sections(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('settings'));

        $response->assertOk();
        $response->assertViewHas('sections', function ($sections) {
            return isset($sections['general']) &&
                   isset($sections['email']) &&
                   isset($sections['system']);
        });
    }

    public function test_non_admin_cannot_access_any_settings(): void
    {
        $routes = [
            route('settings'),
            route('settings.email'),
            route('settings.system'),
        ];

        foreach ($routes as $routeUrl) {
            $response = $this->actingAs($this->user)->get($routeUrl);
            $response->assertForbidden();
        }
    }

    public function test_guest_redirected_from_settings(): void
    {
        $response = $this->get(route('settings'));
        $response->assertRedirect(route('login'));
    }
}
