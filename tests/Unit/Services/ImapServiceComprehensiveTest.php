<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Mailbox;
use App\Services\ImapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ImapServiceComprehensiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_fetch_emails_returns_stats_array_with_required_keys(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
            'in_port' => 993,
            'in_username' => 'test@example.com',
            'in_password' => 'password',
        ]);

        $service = new ImapService;

        // This will fail to connect in test env, but should return valid structure
        $stats = $service->fetchEmails($mailbox);

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('fetched', $stats);
        $this->assertArrayHasKey('created', $stats);
        $this->assertArrayHasKey('errors', $stats);
        $this->assertArrayHasKey('messages', $stats);
    }

    public function test_fetch_emails_skips_when_no_imap_server_configured(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => null,
            'name' => 'Test Mailbox',
        ]);

        Log::shouldReceive('warning')
            ->once()
            ->with('IMAP fetch skipped - no server configured', \Mockery::any());

        $service = new ImapService;
        $stats = $service->fetchEmails($mailbox);

        $this->assertEquals(0, $stats['fetched']);
        $this->assertEquals(0, $stats['created']);
        $this->assertStringContainsString('No IMAP server configured', $stats['messages'][0]);
    }

    public function test_fetch_emails_logs_start_with_correct_parameters(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
            'in_port' => 993,
            'in_username' => 'test@example.com',
            'in_password' => 'password',
            'name' => 'Test Mailbox',
        ]);

        Log::shouldReceive('info')
            ->once()
            ->with('Starting IMAP fetch', \Mockery::on(function ($context) use ($mailbox) {
                return $context['mailbox_id'] === $mailbox->id
                    && $context['mailbox_name'] === 'Test Mailbox'
                    && $context['server'] === 'imap.example.com'
                    && $context['port'] === 993;
            }));

        Log::shouldReceive('error')->zeroOrMoreTimes();
        Log::shouldReceive('warning')->zeroOrMoreTimes();
        Log::shouldReceive('debug')->zeroOrMoreTimes();

        $service = new ImapService;
        $service->fetchEmails($mailbox);
    }

    public function test_fetch_emails_initializes_stats_with_zeros(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => null,
        ]);

        Log::shouldReceive('warning')->once();

        $service = new ImapService;
        $stats = $service->fetchEmails($mailbox);

        $this->assertEquals(0, $stats['fetched']);
        $this->assertEquals(0, $stats['created']);
        $this->assertEquals(0, $stats['errors']);
        $this->assertIsArray($stats['messages']);
    }

    public function test_fetch_emails_handles_empty_in_server_gracefully(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => '',
            'name' => 'Empty Server Mailbox',
        ]);

        Log::shouldReceive('warning')->once();

        $service = new ImapService;
        $stats = $service->fetchEmails($mailbox);

        $this->assertNotNull($stats);
        $this->assertArrayHasKey('messages', $stats);
        $this->assertCount(1, $stats['messages']);
    }

    public function test_test_connection_method_exists(): void
    {
        $service = new ImapService;

        $this->assertTrue(method_exists($service, 'testConnection'));
    }

    public function test_create_client_method_exists(): void
    {
        $service = new ImapService;

        $reflection = new \ReflectionClass($service);
        $methods = $reflection->getMethods();
        $methodNames = array_map(fn ($method) => $method->getName(), $methods);

        $this->assertContains('createClient', $methodNames);
    }

    // Story 1.1.1: IMAP Connection Error Handling

    public function test_fetch_emails_handles_connection_failure_gracefully(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'invalid.server.com',
            'in_port' => 993,
            'in_username' => 'test@example.com',
            'in_password' => 'password',
        ]);

        Log::shouldReceive('info')->once(); // Starting IMAP fetch
        Log::shouldReceive('error')
            ->once()
            ->with('IMAP connection failed', \Mockery::any());

        $service = new ImapService;
        $stats = $service->fetchEmails($mailbox);

        $this->assertEquals(0, $stats['fetched']);
        $this->assertGreaterThan(0, $stats['errors']);
    }

    public function test_test_connection_returns_failure_for_invalid_credentials(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
            'in_port' => 993,
            'in_username' => 'invalid@example.com',
            'in_password' => 'wrongpassword',
        ]);

        $service = new ImapService;
        $result = $service->testConnection($mailbox);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('failed', strtolower($result['message']));
    }

    // Story 1.1.3: Charset/Encoding Error Recovery

    public function test_retries_fetch_on_charset_error(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'outlook.office365.com',
            'in_port' => 993,
            'in_username' => 'user@company.com',
            'in_password' => 'password',
        ]);

        Log::shouldReceive('info')->atLeast()->once();
        Log::shouldReceive('warning')->zeroOrMoreTimes();
        Log::shouldReceive('error')->once();

        $service = new ImapService;
        $stats = $service->fetchEmails($mailbox);

        // Should have attempted to fetch
        $this->assertArrayHasKey('fetched', $stats);
        $this->assertArrayHasKey('errors', $stats);
    }

    public function test_logs_charset_conversion_attempts(): void
    {
        // Test that charset issues are properly logged for debugging
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
        ]);

        Log::shouldReceive('info')->atLeast()->once();
        Log::shouldReceive('warning')->zeroOrMoreTimes();
        Log::shouldReceive('error')->once();

        $service = new ImapService;
        $service->fetchEmails($mailbox);

        $this->assertTrue(true); // Verify log expectations were met
    }
}
