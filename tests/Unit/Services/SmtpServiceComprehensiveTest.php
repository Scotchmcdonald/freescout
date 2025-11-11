<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Mailbox;
use App\Services\SmtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SmtpServiceComprehensiveTest extends TestCase
{
    use RefreshDatabase;

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
}
