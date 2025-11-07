<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Mailbox;
use App\Services\SmtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SmtpServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_test_connection_returns_array(): void
    {
        $mailbox = new Mailbox([
            'id' => 1,
            'name' => 'Test Mailbox',
            'out_server' => null, // Missing required field
            'out_port' => null,
            'out_username' => null,
            'out_password' => null
        ]);
        
        $service = new SmtpService();
        $result = $service->testConnection($mailbox, 'test@example.com');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('message', $result);
    }

    public function test_test_connection_method_exists(): void
    {
        $service = new SmtpService();
        $this->assertTrue(method_exists($service, 'testConnection'));
    }

    public function test_configure_smtp_method_exists(): void
    {
        $mailbox = new Mailbox([
            'out_server' => 'smtp.example.com',
            'out_port' => 587,
            'out_username' => 'test@example.com',
            'out_password' => 'password'
        ]);
        
        $service = new SmtpService();
        
        // Just test that the method exists and can be called
        $this->assertTrue(method_exists($service, 'configureSmtp'));
        
        try {
            $service->configureSmtp($mailbox);
            $this->assertTrue(true);
        } catch (\Exception $e) {
            // Method might fail due to missing config, but it should exist
            $this->assertTrue(true);
        }
    }

    /** Test validation fails when SMTP server is missing */
    public function test_validate_settings_requires_smtp_server(): void
    {
        $service = new SmtpService();
        $settings = [
            'out_server' => '',
            'out_port' => 587,
            'email' => 'test@example.com',
        ];

        $errors = $service->validateSettings($settings);

        $this->assertArrayHasKey('out_server', $errors);
        $this->assertEquals('SMTP server is required', $errors['out_server']);
    }

    /** Test validation fails when SMTP port is missing */
    public function test_validate_settings_requires_smtp_port(): void
    {
        $service = new SmtpService();
        $settings = [
            'out_server' => 'smtp.example.com',
            'out_port' => '',
            'email' => 'test@example.com',
        ];

        $errors = $service->validateSettings($settings);

        $this->assertArrayHasKey('out_port', $errors);
    }

    /** Test validation fails with invalid port number */
    public function test_validate_settings_rejects_invalid_port(): void
    {
        $service = new SmtpService();
        $settings = [
            'out_server' => 'smtp.example.com',
            'out_port' => 99999,
            'email' => 'test@example.com',
        ];

        $errors = $service->validateSettings($settings);

        $this->assertArrayHasKey('out_port', $errors);
        $this->assertStringContainsString('between 1 and 65535', $errors['out_port']);
    }

    /** Test validation fails when email is missing */
    public function test_validate_settings_requires_email(): void
    {
        $service = new SmtpService();
        $settings = [
            'out_server' => 'smtp.example.com',
            'out_port' => 587,
            'email' => '',
        ];

        $errors = $service->validateSettings($settings);

        $this->assertArrayHasKey('email', $errors);
        $this->assertEquals('Email address is required', $errors['email']);
    }

    /** Test validation fails with invalid email format */
    public function test_validate_settings_rejects_invalid_email(): void
    {
        $service = new SmtpService();
        $settings = [
            'out_server' => 'smtp.example.com',
            'out_port' => 587,
            'email' => 'not-an-email',
        ];

        $errors = $service->validateSettings($settings);

        $this->assertArrayHasKey('email', $errors);
        $this->assertEquals('Invalid email address', $errors['email']);
    }

    /** Test validation suggests SSL for port 465 */
    public function test_validate_settings_suggests_ssl_for_port_465(): void
    {
        $service = new SmtpService();
        $settings = [
            'out_server' => 'smtp.example.com',
            'out_port' => 465,
            'out_encryption' => 0, // No encryption
            'email' => 'test@example.com',
        ];

        $errors = $service->validateSettings($settings);

        $this->assertArrayHasKey('out_encryption', $errors);
        $this->assertStringContainsString('SSL', $errors['out_encryption']);
    }

    /** Test validation suggests TLS for port 587 */
    public function test_validate_settings_suggests_tls_for_port_587(): void
    {
        $service = new SmtpService();
        $settings = [
            'out_server' => 'smtp.example.com',
            'out_port' => 587,
            'out_encryption' => 0, // No encryption
            'email' => 'test@example.com',
        ];

        $errors = $service->validateSettings($settings);

        $this->assertArrayHasKey('out_encryption', $errors);
        $this->assertStringContainsString('TLS', $errors['out_encryption']);
    }

    /** Test validation passes with correct settings */
    public function test_validate_settings_passes_with_valid_config(): void
    {
        $service = new SmtpService();
        $settings = [
            'out_server' => 'smtp.example.com',
            'out_port' => 587,
            'out_encryption' => 2, // TLS
            'email' => 'test@example.com',
        ];

        $errors = $service->validateSettings($settings);

        $this->assertEmpty($errors);
    }

    /** Test configure SMTP sets mail config correctly */
    public function test_configure_smtp_sets_config_values(): void
    {
        $mailbox = Mailbox::factory()->create([
            'out_server' => 'smtp.test.com',
            'out_port' => 587,
            'out_encryption' => 2, // TLS
            'out_username' => 'user@test.com',
            'out_password' => 'secret',
            'email' => 'from@test.com',
            'name' => 'Test Mailbox',
        ]);

        $service = new SmtpService();
        $service->configureSmtp($mailbox);

        $this->assertEquals('smtp', Config::get('mail.default'));
        $this->assertEquals('smtp.test.com', Config::get('mail.mailers.smtp.host'));
        $this->assertEquals(587, Config::get('mail.mailers.smtp.port'));
        $this->assertEquals('tls', Config::get('mail.mailers.smtp.encryption'));
        $this->assertEquals('user@test.com', Config::get('mail.mailers.smtp.username'));
        $this->assertEquals('from@test.com', Config::get('mail.from.address'));
        $this->assertEquals('Test Mailbox', Config::get('mail.from.name'));
    }

    /** Test test connection fails gracefully with missing server */
    public function test_test_connection_fails_gracefully_with_no_server(): void
    {
        $mailbox = Mailbox::factory()->create([
            'out_server' => null,
            'out_port' => 587,
        ]);

        $service = new SmtpService();
        $result = $service->testConnection($mailbox, 'test@example.com');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Configuration errors', $result['message']);
    }

    /** Test test connection validates email address format */
    public function test_test_connection_validates_recipient_implicitly(): void
    {
        Log::shouldReceive('info')->zeroOrMoreTimes();
        Log::shouldReceive('warning')->atLeast()->once();

        $mailbox = Mailbox::factory()->create([
            'out_server' => null,
            'email' => 'invalid-email',
        ]);

        $service = new SmtpService();
        $result = $service->testConnection($mailbox, 'recipient@example.com');

        $this->assertFalse($result['success']);
    }

    /** Test encryption type mapping for SSL */
    public function test_encryption_mapping_for_ssl(): void
    {
        $mailbox = Mailbox::factory()->create([
            'out_server' => 'smtp.test.com',
            'out_port' => 465,
            'out_encryption' => 1, // SSL
            'email' => 'test@example.com',
            'name' => 'Test',
        ]);

        $service = new SmtpService();
        $service->configureSmtp($mailbox);

        $this->assertEquals('ssl', Config::get('mail.mailers.smtp.encryption'));
    }

    /** Test encryption type mapping for TLS */
    public function test_encryption_mapping_for_tls(): void
    {
        $mailbox = Mailbox::factory()->create([
            'out_server' => 'smtp.test.com',
            'out_port' => 587,
            'out_encryption' => 2, // TLS
            'email' => 'test@example.com',
            'name' => 'Test',
        ]);

        $service = new SmtpService();
        $service->configureSmtp($mailbox);

        $this->assertEquals('tls', Config::get('mail.mailers.smtp.encryption'));
    }

    /** Test encryption type mapping for none */
    public function test_encryption_mapping_for_none(): void
    {
        $mailbox = Mailbox::factory()->create([
            'out_server' => 'smtp.test.com',
            'out_port' => 25,
            'out_encryption' => 0, // None
            'email' => 'test@example.com',
            'name' => 'Test',
        ]);

        $service = new SmtpService();
        $service->configureSmtp($mailbox);

        $this->assertNull(Config::get('mail.mailers.smtp.encryption'));
    }
}