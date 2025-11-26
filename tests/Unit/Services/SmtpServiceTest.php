<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Mailbox;
use App\Services\SmtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class SmtpServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SmtpService $smtpService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->smtpService = new SmtpService();
    }

    public function test_validate_settings_requires_smtp_server(): void
    {
        $result = $this->smtpService->validateSettings([
            'out_port' => 587,
            'email' => 'user@example.com',
        ]);

        $this->assertArrayHasKey('out_server', $result);
        $this->assertStringContainsString('required', strtolower($result['out_server']));
    }

    public function test_validate_settings_requires_smtp_port(): void
    {
        $result = $this->smtpService->validateSettings([
            'out_server' => 'smtp.example.com',
            'email' => 'user@example.com',
        ]);

        $this->assertArrayHasKey('out_port', $result);
        $this->assertStringContainsString('required', strtolower($result['out_port']));
    }

    public function test_validate_settings_requires_valid_port_range(): void
    {
        $result = $this->smtpService->validateSettings([
            'out_server' => 'smtp.example.com',
            'out_port' => 99999,
            'email' => 'user@example.com',
        ]);

        $this->assertArrayHasKey('out_port', $result);
        $this->assertStringContainsString('between', strtolower($result['out_port']));
    }

    public function test_validate_settings_requires_email(): void
    {
        $result = $this->smtpService->validateSettings([
            'out_server' => 'smtp.example.com',
            'out_port' => 587,
        ]);

        $this->assertArrayHasKey('email', $result);
        $this->assertStringContainsString('required', strtolower($result['email']));
    }

    public function test_validate_settings_requires_valid_email(): void
    {
        $result = $this->smtpService->validateSettings([
            'out_server' => 'smtp.example.com',
            'out_port' => 587,
            'email' => 'invalid-email',
        ]);

        $this->assertArrayHasKey('email', $result);
        $this->assertStringContainsString('invalid', strtolower($result['email']));
    }

    public function test_validate_settings_warns_ssl_on_port_465(): void
    {
        $result = $this->smtpService->validateSettings([
            'out_server' => 'smtp.example.com',
            'out_port' => 465,
            'email' => 'user@example.com',
            'out_encryption' => 0, // No encryption
        ]);

        $this->assertArrayHasKey('out_encryption', $result);
        $this->assertStringContainsString('SSL', $result['out_encryption']);
    }

    public function test_validate_settings_warns_tls_on_port_587(): void
    {
        $result = $this->smtpService->validateSettings([
            'out_server' => 'smtp.example.com',
            'out_port' => 587,
            'email' => 'user@example.com',
            'out_encryption' => 0, // No encryption
        ]);

        $this->assertArrayHasKey('out_encryption', $result);
        $this->assertStringContainsString('TLS', $result['out_encryption']);
    }

    public function test_validate_settings_accepts_valid_configuration(): void
    {
        $result = $this->smtpService->validateSettings([
            'out_server' => 'smtp.example.com',
            'out_port' => 587,
            'email' => 'user@example.com',
            'out_encryption' => 2, // TLS
        ]);

        $this->assertEmpty($result);
    }

    public function test_validate_settings_accepts_ssl_on_port_465(): void
    {
        $result = $this->smtpService->validateSettings([
            'out_server' => 'smtp.example.com',
            'out_port' => 465,
            'email' => 'user@example.com',
            'out_encryption' => 1, // SSL
        ]);

        $this->assertArrayNotHasKey('out_encryption', $result);
    }

    public function test_configure_smtp_sets_mail_config(): void
    {
        $mailbox = Mailbox::factory()->create([
            'out_server' => 'smtp.example.com',
            'out_port' => 587,
            'out_username' => 'user@example.com',
            'out_password' => 'password',
            'out_encryption' => 2, // TLS
            'email' => 'support@example.com',
            'name' => 'Support',
        ]);

        $this->smtpService->configureSmtp($mailbox);

        $this->assertEquals('smtp', Config::get('mail.default'));
        $this->assertEquals('smtp.example.com', Config::get('mail.mailers.smtp.host'));
        $this->assertEquals(587, Config::get('mail.mailers.smtp.port'));
        $this->assertEquals('tls', Config::get('mail.mailers.smtp.encryption'));
    }

    public function test_configure_smtp_sets_from_address(): void
    {
        $mailbox = Mailbox::factory()->create([
            'out_server' => 'smtp.example.com',
            'out_port' => 587,
            'email' => 'support@company.com',
            'name' => 'Support Team',
        ]);

        $this->smtpService->configureSmtp($mailbox);

        $this->assertEquals('support@company.com', Config::get('mail.from.address'));
        $this->assertEquals('Support Team', Config::get('mail.from.name'));
    }

    public function test_configure_smtp_with_ssl_encryption(): void
    {
        $mailbox = Mailbox::factory()->create([
            'out_server' => 'smtp.example.com',
            'out_port' => 465,
            'out_encryption' => 1, // SSL
        ]);

        $this->smtpService->configureSmtp($mailbox);

        $this->assertEquals('ssl', Config::get('mail.mailers.smtp.encryption'));
    }

    public function test_configure_smtp_with_no_encryption(): void
    {
        $mailbox = Mailbox::factory()->create([
            'out_server' => 'smtp.example.com',
            'out_port' => 25,
            'out_encryption' => 0, // None
        ]);

        $this->smtpService->configureSmtp($mailbox);

        $this->assertNull(Config::get('mail.mailers.smtp.encryption'));
    }

    public function test_test_connection_returns_result_array(): void
    {
        $mailbox = Mailbox::factory()->create([
            'out_server' => 'invalid.server.example',
            'out_port' => 587,
            'out_username' => 'user@example.com',
            'out_password' => 'password',
            'email' => 'test@example.com',
        ]);

        $result = $this->smtpService->testConnection($mailbox, 'recipient@test.com');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('message', $result);
    }

    public function test_test_connection_validates_settings_first(): void
    {
        $mailbox = Mailbox::factory()->create([
            'out_server' => '', // Invalid - empty
            'out_port' => 587,
            'email' => 'test@example.com',
        ]);

        $result = $this->smtpService->testConnection($mailbox, 'recipient@test.com');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Configuration errors', $result['message']);
    }

    public function test_test_connection_validates_email_address(): void
    {
        $mailbox = Mailbox::factory()->create([
            'out_server' => 'smtp.example.com',
            'out_port' => 587,
            'email' => 'invalid-email', // Invalid email
        ]);

        $result = $this->smtpService->testConnection($mailbox, 'recipient@test.com');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Configuration errors', $result['message']);
    }

    public function test_configure_smtp_handles_oauth_token(): void
    {
        $mailbox = Mailbox::factory()->create([
            'out_server' => 'smtp.office365.com',
            'out_port' => 587,
            'out_username' => 'client-id',
            'out_password' => encrypt('client-secret'),
            'email' => 'user@company.com',
            'meta' => [
                'oauth' => [
                    'provider' => 'ms',
                    'a_token' => 'valid-access-token',
                    'r_token' => 'valid-refresh-token',
                    'issued_on' => now()->toDateTimeString(),
                    'expires_in' => 3600,
                ],
            ],
        ]);

        $this->smtpService->configureSmtp($mailbox);

        // Should use OAuth token as password
        $this->assertEquals('valid-access-token', Config::get('mail.mailers.smtp.password'));
        $this->assertEquals('XOAUTH2', Config::get('mail.mailers.smtp.auth_mode'));
    }

    public function test_validate_settings_with_invalid_port_string(): void
    {
        $result = $this->smtpService->validateSettings([
            'out_server' => 'smtp.example.com',
            'out_port' => 'abc',
            'email' => 'user@example.com',
        ]);

        $this->assertArrayHasKey('out_port', $result);
    }

    public function test_validate_settings_with_negative_port(): void
    {
        $result = $this->smtpService->validateSettings([
            'out_server' => 'smtp.example.com',
            'out_port' => -1,
            'email' => 'user@example.com',
        ]);

        $this->assertArrayHasKey('out_port', $result);
    }
}
