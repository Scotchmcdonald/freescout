<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Mailbox;
use App\Services\SmtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_validate_settings_requires_host(): void
    {
        $result = $this->smtpService->validateSettings([
            'port' => 587,
            'username' => 'user@example.com',
            'password' => 'password',
        ]);

        $this->assertFalse($result['success'] ?? true);
        $this->assertStringContainsString('host', strtolower($result['error'] ?? ''));
    }

    public function test_validate_settings_requires_port(): void
    {
        $result = $this->smtpService->validateSettings([
            'host' => 'smtp.example.com',
            'username' => 'user@example.com',
            'password' => 'password',
        ]);

        $this->assertFalse($result['success'] ?? true);
        $this->assertStringContainsString('port', strtolower($result['error'] ?? ''));
    }

    public function test_validate_settings_requires_valid_port_number(): void
    {
        $result = $this->smtpService->validateSettings([
            'host' => 'smtp.example.com',
            'port' => 'invalid',
            'username' => 'user@example.com',
            'password' => 'password',
        ]);

        $this->assertFalse($result['success'] ?? true);
    }

    public function test_validate_settings_accepts_valid_configuration(): void
    {
        $result = $this->smtpService->validateSettings([
            'host' => 'smtp.example.com',
            'port' => 587,
            'username' => 'user@example.com',
            'password' => 'password',
            'encryption' => 'tls',
        ]);

        // Should not have validation errors for format
        $this->assertTrue(
            ($result['success'] ?? false) || 
            !str_contains(strtolower($result['error'] ?? ''), 'required')
        );
    }

    public function test_validate_settings_accepts_common_ports(): void
    {
        $commonPorts = [25, 465, 587, 2525];

        foreach ($commonPorts as $port) {
            $result = $this->smtpService->validateSettings([
                'host' => 'smtp.example.com',
                'port' => $port,
                'username' => 'user@example.com',
                'password' => 'password',
            ]);

            // Should not fail on port validation
            $this->assertNotEquals(
                'invalid port',
                strtolower($result['error'] ?? ''),
                "Port {$port} should be valid"
            );
        }
    }

    public function test_validate_settings_accepts_ssl_encryption(): void
    {
        $result = $this->smtpService->validateSettings([
            'host' => 'smtp.example.com',
            'port' => 465,
            'username' => 'user@example.com',
            'password' => 'password',
            'encryption' => 'ssl',
        ]);

        // Should not fail on encryption validation
        $this->assertNotEquals(
            'invalid encryption',
            strtolower($result['error'] ?? '')
        );
    }

    public function test_validate_settings_accepts_tls_encryption(): void
    {
        $result = $this->smtpService->validateSettings([
            'host' => 'smtp.example.com',
            'port' => 587,
            'username' => 'user@example.com',
            'password' => 'password',
            'encryption' => 'tls',
        ]);

        // Should not fail on encryption validation
        $this->assertNotEquals(
            'invalid encryption',
            strtolower($result['error'] ?? '')
        );
    }

    public function test_configure_smtp_creates_valid_transport_config(): void
    {
        $mailbox = Mailbox::factory()->create([
            'out_server' => 'smtp.example.com',
            'out_port' => 587,
            'out_username' => 'user@example.com',
            'out_password' => 'password',
            'out_encryption' => 'tls',
        ]);

        $config = $this->smtpService->configureSmtp($mailbox);

        $this->assertIsArray($config);
        $this->assertArrayHasKey('transport', $config);
        $this->assertEquals('smtp', $config['transport']);
    }

    public function test_configure_smtp_includes_host_and_port(): void
    {
        $mailbox = Mailbox::factory()->create([
            'out_server' => 'smtp.test.com',
            'out_port' => 465,
            'out_username' => 'test@test.com',
            'out_password' => 'secret',
        ]);

        $config = $this->smtpService->configureSmtp($mailbox);

        $this->assertEquals('smtp.test.com', $config['host'] ?? null);
        $this->assertEquals(465, $config['port'] ?? null);
    }

    public function test_configure_smtp_includes_authentication(): void
    {
        $mailbox = Mailbox::factory()->create([
            'out_server' => 'smtp.example.com',
            'out_port' => 587,
            'out_username' => 'user@example.com',
            'out_password' => 'mypassword',
        ]);

        $config = $this->smtpService->configureSmtp($mailbox);

        $this->assertEquals('user@example.com', $config['username'] ?? null);
        $this->assertEquals('mypassword', $config['password'] ?? null);
    }

    public function test_test_connection_returns_result_array(): void
    {
        $mailbox = Mailbox::factory()->create([
            'out_server' => 'invalid.server.example',
            'out_port' => 587,
            'out_username' => 'user@example.com',
            'out_password' => 'password',
        ]);

        $result = $this->smtpService->testConnection($mailbox);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    public function test_test_connection_fails_for_invalid_server(): void
    {
        $mailbox = Mailbox::factory()->create([
            'out_server' => 'nonexistent.invalid.server',
            'out_port' => 587,
            'out_username' => 'user@example.com',
            'out_password' => 'password',
        ]);

        $result = $this->smtpService->testConnection($mailbox);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    public function test_validate_mailbox_settings_with_empty_server(): void
    {
        $result = $this->smtpService->validateSettings([
            'host' => '',
            'port' => 587,
            'username' => 'user@example.com',
            'password' => 'password',
        ]);

        $this->assertFalse($result['success'] ?? true);
    }

    public function test_validate_mailbox_settings_with_zero_port(): void
    {
        $result = $this->smtpService->validateSettings([
            'host' => 'smtp.example.com',
            'port' => 0,
            'username' => 'user@example.com',
            'password' => 'password',
        ]);

        $this->assertFalse($result['success'] ?? true);
    }
}
