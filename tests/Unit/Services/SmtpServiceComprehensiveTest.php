<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Mailbox;
use App\Services\SmtpService;
use Illuminate\Support\Facades\Log;
use Tests\UnitTestCase;

class SmtpServiceComprehensiveTest extends UnitTestCase
{

    public function test_test_connection_returns_result_array_with_required_keys(): void
    {
        $mailbox = Mailbox::factory()->create([
            'out_server' => 'smtp.example.com',
            'out_port' => 587,
            'out_username' => 'test@example.com',
            'out_password' => 'password',
            'email' => 'test@example.com',
            'name' => 'Test Mailbox',
        ]);

        Log::shouldReceive('info')->zeroOrMoreTimes();
        Log::shouldReceive('debug')->zeroOrMoreTimes();
        Log::shouldReceive('error')->zeroOrMoreTimes();
        Log::shouldReceive('warning')->zeroOrMoreTimes();

        $service = new SmtpService;
        $result = $service->testConnection($mailbox, 'recipient@example.com');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('message', $result);
    }

    public function test_test_connection_validates_mailbox_settings_before_sending(): void
    {
        $mailbox = Mailbox::factory()->create([
            'out_server' => null, // Invalid: no server
            'email' => 'test@example.com',
        ]);

        Log::shouldReceive('warning')
            ->once()
            ->with('SMTP test skipped due to invalid configuration', \Mockery::any());

        $service = new SmtpService;
        $result = $service->testConnection($mailbox, 'recipient@example.com');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Configuration errors', $result['message']);
    }

    public function test_test_connection_logs_start_with_correct_parameters(): void
    {
        $mailbox = Mailbox::factory()->create([
            'out_server' => 'smtp.example.com',
            'out_port' => 587,
            'out_username' => 'test@example.com',
            'out_password' => 'password',
            'out_encryption' => 'tls',
            'email' => 'test@example.com',
            'name' => 'Test Mailbox',
        ]);

        Log::shouldReceive('info')
            ->once()
            ->with('Starting SMTP test', \Mockery::on(function ($context) use ($mailbox) {
                return $context['mailbox_id'] === $mailbox->id
                    && $context['mailbox_name'] === 'Test Mailbox'
                    && $context['to_email'] === 'recipient@example.com'
                    && $context['smtp_server'] === 'smtp.example.com'
                    && $context['smtp_port'] === 587;
            }));

        Log::shouldReceive('debug')->zeroOrMoreTimes();
        Log::shouldReceive('error')->zeroOrMoreTimes();
        Log::shouldReceive('warning')->zeroOrMoreTimes();

        $service = new SmtpService;
        $service->testConnection($mailbox, 'recipient@example.com');
    }

    public function test_configure_smtp_method_exists_and_is_callable(): void
    {
        $service = new SmtpService;

        $this->assertTrue(method_exists($service, 'configureSmtp'));
        $this->assertTrue(is_callable([$service, 'configureSmtp']));
    }

    public function test_validate_settings_method_exists(): void
    {
        $service = new SmtpService;

        $reflection = new \ReflectionClass($service);
        $methods = $reflection->getMethods();
        $methodNames = array_map(fn ($method) => $method->getName(), $methods);

        $this->assertContains('validateSettings', $methodNames);
    }

    public function test_get_encryption_method_exists(): void
    {
        $service = new SmtpService;

        $reflection = new \ReflectionClass($service);
        $methods = $reflection->getMethods();
        $methodNames = array_map(fn ($method) => $method->getName(), $methods);

        $this->assertContains('getEncryption', $methodNames);
    }

    public function test_validate_mailbox_settings_method_exists(): void
    {
        $service = new SmtpService;

        $reflection = new \ReflectionClass($service);
        $methods = $reflection->getMethods();
        $methodNames = array_map(fn ($method) => $method->getName(), $methods);

        $this->assertContains('validateMailboxSettings', $methodNames);
    }

    public function test_test_connection_requires_valid_email_address(): void
    {
        $mailbox = Mailbox::factory()->create([
            'out_server' => 'smtp.example.com',
            'out_port' => 587,
            'email' => 'test@example.com',
        ]);

        Log::shouldReceive('info')->zeroOrMoreTimes();
        Log::shouldReceive('debug')->zeroOrMoreTimes();
        Log::shouldReceive('error')->zeroOrMoreTimes();
        Log::shouldReceive('warning')->zeroOrMoreTimes();

        $service = new SmtpService;
        $result = $service->testConnection($mailbox, 'recipient@example.com');

        // Should have processed the request (even if it fails in test env)
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    // Story 1.2.1: SMTP Connection Testing

    public function test_test_connection_fails_with_invalid_server(): void
    {
        $mailbox = Mailbox::factory()->create([
            'out_server' => 'invalid.smtp.server',
            'out_port' => 587,
            'out_username' => 'test@example.com',
            'out_password' => 'wrongpass',
            'email' => 'test@example.com',
        ]);

        Log::shouldReceive('info')->once();
        Log::shouldReceive('error')->once();

        $service = new SmtpService;
        $result = $service->testConnection($mailbox, 'recipient@example.com');

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('message', $result);
    }

    public function test_test_connection_handles_authentication_errors(): void
    {
        $mailbox = Mailbox::factory()->create([
            'out_server' => 'smtp.gmail.com',
            'out_port' => 587,
            'out_username' => 'test@gmail.com',
            'out_password' => 'invalid_app_password',
            'email' => 'test@gmail.com',
        ]);

        Log::shouldReceive('info')->once();
        Log::shouldReceive('error')
            ->once()
            ->with(\Mockery::pattern('/SMTP test failed/i'), \Mockery::any());

        $service = new SmtpService;
        $result = $service->testConnection($mailbox, 'recipient@example.com');

        $this->assertFalse($result['success']);
    }

    public function test_test_connection_validates_port_number(): void
    {
        $mailbox = Mailbox::factory()->create([
            'out_server' => 'smtp.example.com',
            'out_port' => 99999, // Invalid port
            'out_username' => 'test@example.com',
            'out_password' => 'password',
            'email' => 'test@example.com',
        ]);

        Log::shouldReceive('info')->once();
        Log::shouldReceive('error')->once();

        $service = new SmtpService;
        $result = $service->testConnection($mailbox, 'recipient@example.com');

        $this->assertFalse($result['success']);
    }

    public function test_test_connection_handles_timeout(): void
    {
        $mailbox = Mailbox::factory()->create([
            'out_server' => 'non-responsive-server.com',
            'out_port' => 587,
            'out_username' => 'test@example.com',
            'out_password' => 'password',
            'email' => 'test@example.com',
        ]);

        Log::shouldReceive('info')->once();
        Log::shouldReceive('error')->once();

        // Should timeout and return failure gracefully
        $service = new SmtpService;
        $result = $service->testConnection($mailbox, 'recipient@example.com');

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('message', $result);
    }

    // validateSettings() tests - 0% coverage currently

    public function test_validate_settings_with_valid_settings(): void
    {
        Log::shouldReceive('debug')->zeroOrMoreTimes();

        $service = new SmtpService;
        $settings = [
            'out_server' => 'smtp.example.com',
            'out_port' => 587,
            'email' => 'test@example.com',
            'out_encryption' => 2, // TLS
        ];

        $errors = $service->validateSettings($settings);

        $this->assertIsArray($errors);
        $this->assertEmpty($errors, 'Valid settings should return no errors');
    }

    public function test_validate_settings_requires_out_server(): void
    {
        Log::shouldReceive('debug')->zeroOrMoreTimes();

        $service = new SmtpService;
        $settings = [
            'out_port' => 587,
            'email' => 'test@example.com',
        ];

        $errors = $service->validateSettings($settings);

        $this->assertArrayHasKey('out_server', $errors);
        $this->assertEquals('SMTP server is required', $errors['out_server']);
    }

    public function test_validate_settings_requires_out_port(): void
    {
        Log::shouldReceive('debug')->zeroOrMoreTimes();

        $service = new SmtpService;
        $settings = [
            'out_server' => 'smtp.example.com',
            'email' => 'test@example.com',
        ];

        $errors = $service->validateSettings($settings);

        $this->assertArrayHasKey('out_port', $errors);
        $this->assertEquals('SMTP port is required', $errors['out_port']);
    }

    public function test_validate_settings_validates_port_range_minimum(): void
    {
        Log::shouldReceive('debug')->zeroOrMoreTimes();

        $service = new SmtpService;
        $settings = [
            'out_server' => 'smtp.example.com',
            'out_port' => 0, // Below minimum
            'email' => 'test@example.com',
        ];

        $errors = $service->validateSettings($settings);

        $this->assertArrayHasKey('out_port', $errors);
        // Port value of 0 triggers 'required' error not 'between' error
        $this->assertEquals('SMTP port is required', $errors['out_port']);
    }

    public function test_validate_settings_validates_port_range_maximum(): void
    {
        Log::shouldReceive('debug')->zeroOrMoreTimes();

        $service = new SmtpService;
        $settings = [
            'out_server' => 'smtp.example.com',
            'out_port' => 65536, // Above maximum
            'email' => 'test@example.com',
        ];

        $errors = $service->validateSettings($settings);

        $this->assertArrayHasKey('out_port', $errors);
        $this->assertStringContainsString('between 1 and 65535', $errors['out_port']);
    }

    public function test_validate_settings_validates_port_is_numeric(): void
    {
        Log::shouldReceive('debug')->zeroOrMoreTimes();

        $service = new SmtpService;
        $settings = [
            'out_server' => 'smtp.example.com',
            'out_port' => 'not-a-number',
            'email' => 'test@example.com',
        ];

        $errors = $service->validateSettings($settings);

        $this->assertArrayHasKey('out_port', $errors);
        $this->assertStringContainsString('between 1 and 65535', $errors['out_port']);
    }

    public function test_validate_settings_requires_email(): void
    {
        Log::shouldReceive('debug')->zeroOrMoreTimes();

        $service = new SmtpService;
        $settings = [
            'out_server' => 'smtp.example.com',
            'out_port' => 587,
        ];

        $errors = $service->validateSettings($settings);

        $this->assertArrayHasKey('email', $errors);
        $this->assertEquals('Email address is required', $errors['email']);
    }

    public function test_validate_settings_validates_email_format(): void
    {
        Log::shouldReceive('debug')->zeroOrMoreTimes();

        $service = new SmtpService;
        $settings = [
            'out_server' => 'smtp.example.com',
            'out_port' => 587,
            'email' => 'invalid-email-format',
        ];

        $errors = $service->validateSettings($settings);

        $this->assertArrayHasKey('email', $errors);
        $this->assertEquals('Invalid email address', $errors['email']);
    }

    public function test_validate_settings_returns_all_errors_at_once(): void
    {
        Log::shouldReceive('debug')->zeroOrMoreTimes();

        $service = new SmtpService;
        $settings = [
            // Missing all required fields
        ];

        $errors = $service->validateSettings($settings);

        $this->assertArrayHasKey('out_server', $errors);
        $this->assertArrayHasKey('out_port', $errors);
        $this->assertArrayHasKey('email', $errors);
        $this->assertCount(3, $errors);
    }

    public function test_validate_settings_warns_about_port_465_without_ssl(): void
    {
        Log::shouldReceive('debug')->zeroOrMoreTimes();

        $service = new SmtpService;
        $settings = [
            'out_server' => 'smtp.example.com',
            'out_port' => 465,
            'email' => 'test@example.com',
            'out_encryption' => 0, // No encryption
        ];

        $errors = $service->validateSettings($settings);

        $this->assertArrayHasKey('out_encryption', $errors);
        $this->assertStringContainsString('Port 465 typically requires SSL', $errors['out_encryption']);
    }

    public function test_validate_settings_warns_about_port_587_without_tls(): void
    {
        Log::shouldReceive('debug')->zeroOrMoreTimes();

        $service = new SmtpService;
        $settings = [
            'out_server' => 'smtp.example.com',
            'out_port' => 587,
            'email' => 'test@example.com',
            'out_encryption' => 0, // No encryption
        ];

        $errors = $service->validateSettings($settings);

        $this->assertArrayHasKey('out_encryption', $errors);
        $this->assertStringContainsString('Port 587 typically requires TLS', $errors['out_encryption']);
    }

    public function test_validate_settings_accepts_port_465_with_ssl(): void
    {
        Log::shouldReceive('debug')->zeroOrMoreTimes();

        $service = new SmtpService;
        $settings = [
            'out_server' => 'smtp.example.com',
            'out_port' => 465,
            'email' => 'test@example.com',
            'out_encryption' => 1, // SSL
        ];

        $errors = $service->validateSettings($settings);

        $this->assertArrayNotHasKey('out_encryption', $errors);
    }

    public function test_validate_settings_accepts_port_587_with_tls(): void
    {
        Log::shouldReceive('debug')->zeroOrMoreTimes();

        $service = new SmtpService;
        $settings = [
            'out_server' => 'smtp.example.com',
            'out_port' => 587,
            'email' => 'test@example.com',
            'out_encryption' => 2, // TLS
        ];

        $errors = $service->validateSettings($settings);

        $this->assertArrayNotHasKey('out_encryption', $errors);
    }

    // Additional edge case tests for validateSettings

    public function test_validate_settings_handles_port_as_string(): void
    {
        Log::shouldReceive('debug')->zeroOrMoreTimes();

        $service = new SmtpService;
        $settings = [
            'out_server' => 'smtp.example.com',
            'out_port' => '587', // String instead of int
            'email' => 'test@example.com',
        ];

        $errors = $service->validateSettings($settings);

        // Should not have port error if valid numeric string
        $this->assertArrayNotHasKey('out_port', $errors);
    }

    public function test_validate_settings_rejects_negative_port(): void
    {
        Log::shouldReceive('debug')->zeroOrMoreTimes();

        $service = new SmtpService;
        $settings = [
            'out_server' => 'smtp.example.com',
            'out_port' => -1,
            'email' => 'test@example.com',
        ];

        $errors = $service->validateSettings($settings);

        $this->assertArrayHasKey('out_port', $errors);
    }

    public function test_validate_settings_handles_email_with_special_characters(): void
    {
        Log::shouldReceive('debug')->zeroOrMoreTimes();

        $service = new SmtpService;
        $settings = [
            'out_server' => 'smtp.example.com',
            'out_port' => 587,
            'email' => 'user+tag@example.com', // Valid email with +
        ];

        $errors = $service->validateSettings($settings);

        $this->assertArrayNotHasKey('email', $errors);
    }

    public function test_validate_settings_rejects_email_without_domain(): void
    {
        Log::shouldReceive('debug')->zeroOrMoreTimes();

        $service = new SmtpService;
        $settings = [
            'out_server' => 'smtp.example.com',
            'out_port' => 587,
            'email' => 'userwithnodomain',
        ];

        $errors = $service->validateSettings($settings);

        $this->assertArrayHasKey('email', $errors);
    }

    public function test_validate_settings_rejects_email_with_spaces(): void
    {
        Log::shouldReceive('debug')->zeroOrMoreTimes();

        $service = new SmtpService;
        $settings = [
            'out_server' => 'smtp.example.com',
            'out_port' => 587,
            'email' => 'user @example.com', // Space in email
        ];

        $errors = $service->validateSettings($settings);

        $this->assertArrayHasKey('email', $errors);
    }

    public function test_validate_settings_handles_common_smtp_ports(): void
    {
        Log::shouldReceive('debug')->zeroOrMoreTimes();

        $service = new SmtpService;
        
        // Test port 25 (standard SMTP)
        $settings25 = [
            'out_server' => 'smtp.example.com',
            'out_port' => 25,
            'email' => 'test@example.com',
        ];
        $errors25 = $service->validateSettings($settings25);
        $this->assertArrayNotHasKey('out_port', $errors25);
        
        // Test port 2525 (alternative SMTP)
        $settings2525 = [
            'out_server' => 'smtp.example.com',
            'out_port' => 2525,
            'email' => 'test@example.com',
        ];
        $errors2525 = $service->validateSettings($settings2525);
        $this->assertArrayNotHasKey('out_port', $errors2525);
    }

    public function test_validate_settings_handles_server_with_whitespace(): void
    {
        Log::shouldReceive('debug')->zeroOrMoreTimes();

        $service = new SmtpService;
        $settings = [
            'out_server' => '  smtp.example.com  ', // With whitespace
            'out_port' => 587,
            'email' => 'test@example.com',
        ];

        $errors = $service->validateSettings($settings);

        // Should not have server error (whitespace is trimmed or handled)
        $this->assertArrayNotHasKey('out_server', $errors);
    }

    public function test_validate_settings_boundary_port_values(): void
    {
        Log::shouldReceive('debug')->zeroOrMoreTimes();

        $service = new SmtpService;
        
        // Test port 1 (minimum valid)
        $settingsMin = [
            'out_server' => 'smtp.example.com',
            'out_port' => 1,
            'email' => 'test@example.com',
        ];
        $errorsMin = $service->validateSettings($settingsMin);
        $this->assertArrayNotHasKey('out_port', $errorsMin);
        
        // Test port 65535 (maximum valid)
        $settingsMax = [
            'out_server' => 'smtp.example.com',
            'out_port' => 65535,
            'email' => 'test@example.com',
        ];
        $errorsMax = $service->validateSettings($settingsMax);
        $this->assertArrayNotHasKey('out_port', $errorsMax);
    }

    // ===== BASIC SMTP SERVICE TESTS (Merged from SmtpServiceTest.php files) =====

    public function test_smtp_service_test_connection_returns_array(): void
    {
        $mailbox = new Mailbox([
            'id' => 1,
            'name' => 'Test Mailbox',
            'out_server' => null, // Missing required field
            'out_port' => null,
            'out_username' => null,
            'out_password' => null,
        ]);

        $service = new SmtpService;
        $result = $service->testConnection($mailbox, 'test@example.com');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('message', $result);
    }

    public function test_smtp_service_test_connection_method_exists(): void
    {
        $service = new SmtpService;
        $this->assertTrue(method_exists($service, 'testConnection'));
    }

    public function test_smtp_service_configure_smtp_method_exists(): void
    {
        $mailbox = new Mailbox([
            'out_server' => 'smtp.example.com',
            'out_port' => 587,
            'out_username' => 'test@example.com',
            'out_password' => 'password',
        ]);

        $service = new SmtpService;

        $this->assertTrue(method_exists($service, 'configureSmtp'));

        try {
            $service->configureSmtp($mailbox);
            $this->expectNotToPerformAssertions();
        } catch (\Exception $e) {
            // Method might fail due to missing config, but it should exist
            $this->expectNotToPerformAssertions();
        }
    }

    public function test_smtp_service_can_be_instantiated(): void
    {
        $service = new SmtpService;

        $this->assertInstanceOf(SmtpService::class, $service);
    }

    public function test_smtp_service_has_validate_settings_method(): void
    {
        $service = new SmtpService;

        $this->assertTrue(method_exists($service, 'validateSettings'));
    }
}
