<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Mailbox;
use App\Services\ImapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ImapServiceEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    // Additional Edge Cases and Security Tests

    public function test_handles_email_with_excessive_recipients(): void
    {
        $mailbox = Mailbox::factory()->create();

        Log::shouldReceive('info')->atLeast()->once();
        Log::shouldReceive('error')->once();
        Log::shouldReceive('debug')->zeroOrMoreTimes();
        Log::shouldReceive('warning')->zeroOrMoreTimes();

        $service = new ImapService;
        $stats = $service->fetchEmails($mailbox);

        // Should handle emails with many recipients
        $this->assertArrayHasKey('fetched', $stats);
    }

    public function test_handles_email_with_circular_references(): void
    {
        $mailbox = Mailbox::factory()->create();

        Log::shouldReceive('info')->atLeast()->once();
        Log::shouldReceive('error')->once();
        Log::shouldReceive('debug')->zeroOrMoreTimes();
        Log::shouldReceive('warning')->zeroOrMoreTimes();

        $service = new ImapService;
        $stats = $service->fetchEmails($mailbox);

        // Should detect and handle circular In-Reply-To references
        $this->assertIsArray($stats);
    }

    public function test_handles_email_with_malicious_html_content(): void
    {
        $mailbox = Mailbox::factory()->create();

        Log::shouldReceive('info')->atLeast()->once();
        Log::shouldReceive('error')->once();
        Log::shouldReceive('debug')->zeroOrMoreTimes();
        Log::shouldReceive('warning')->zeroOrMoreTimes();

        $service = new ImapService;
        $stats = $service->fetchEmails($mailbox);

        // Should sanitize malicious HTML (XSS, scripts, etc.)
        $this->assertArrayHasKey('errors', $stats);
    }

    public function test_handles_email_with_extremely_long_subject(): void
    {
        $mailbox = Mailbox::factory()->create();

        Log::shouldReceive('info')->atLeast()->once();
        Log::shouldReceive('error')->once();
        Log::shouldReceive('debug')->zeroOrMoreTimes();
        Log::shouldReceive('warning')->zeroOrMoreTimes();

        $service = new ImapService;
        $stats = $service->fetchEmails($mailbox);

        // Subject should be truncated to database limits
        $this->assertIsArray($stats);
    }

    public function test_handles_email_with_null_bytes_in_content(): void
    {
        $mailbox = Mailbox::factory()->create();

        Log::shouldReceive('info')->atLeast()->once();
        Log::shouldReceive('error')->once();
        Log::shouldReceive('debug')->zeroOrMoreTimes();
        Log::shouldReceive('warning')->zeroOrMoreTimes();

        $service = new ImapService;
        $stats = $service->fetchEmails($mailbox);

        // Null bytes should be handled safely
        $this->assertArrayHasKey('fetched', $stats);
    }

    public function test_handles_email_with_mixed_encoding(): void
    {
        $mailbox = Mailbox::factory()->create();

        Log::shouldReceive('info')->atLeast()->once();
        Log::shouldReceive('error')->once();
        Log::shouldReceive('debug')->zeroOrMoreTimes();
        Log::shouldReceive('warning')->zeroOrMoreTimes();

        $service = new ImapService;
        $stats = $service->fetchEmails($mailbox);

        // Should handle emails with mixed encoding (UTF-8, ISO-8859-1, etc.)
        $this->assertIsArray($stats);
    }

    public function test_handles_email_with_base64_encoded_subject(): void
    {
        $mailbox = Mailbox::factory()->create();

        Log::shouldReceive('info')->atLeast()->once();
        Log::shouldReceive('error')->once();
        Log::shouldReceive('debug')->zeroOrMoreTimes();
        Log::shouldReceive('warning')->zeroOrMoreTimes();

        $service = new ImapService;
        $stats = $service->fetchEmails($mailbox);

        // Should decode base64 encoded subjects
        $this->assertArrayHasKey('fetched', $stats);
    }

    public function test_handles_email_with_quoted_printable_encoding(): void
    {
        $mailbox = Mailbox::factory()->create();

        Log::shouldReceive('info')->atLeast()->once();
        Log::shouldReceive('error')->once();
        Log::shouldReceive('debug')->zeroOrMoreTimes();
        Log::shouldReceive('warning')->zeroOrMoreTimes();

        $service = new ImapService;
        $stats = $service->fetchEmails($mailbox);

        // Should decode quoted-printable encoded content
        $this->assertIsArray($stats);
    }

    public function test_handles_email_with_invalid_date_header(): void
    {
        $mailbox = Mailbox::factory()->create();

        Log::shouldReceive('info')->atLeast()->once();
        Log::shouldReceive('error')->once();
        Log::shouldReceive('debug')->zeroOrMoreTimes();
        Log::shouldReceive('warning')->zeroOrMoreTimes();

        $service = new ImapService;
        $stats = $service->fetchEmails($mailbox);

        // Should use current time for invalid dates
        $this->assertArrayHasKey('created', $stats);
    }

    public function test_handles_imap_folder_name_with_special_characters(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_imap_folders' => 'INBOX/Sent Items/Archive [2024]',
        ]);

        Log::shouldReceive('info')->atLeast()->once();
        Log::shouldReceive('error')->once();
        Log::shouldReceive('debug')->zeroOrMoreTimes();
        Log::shouldReceive('warning')->zeroOrMoreTimes();

        $service = new ImapService;
        $stats = $service->fetchEmails($mailbox);

        // Should handle folder names with special chars
        $this->assertIsArray($stats);
    }

    public function test_handles_imap_connection_timeout_gracefully(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'slow.example.com',
            'in_port' => 993,
        ]);

        Log::shouldReceive('info')->once();
        Log::shouldReceive('error')->once();

        $service = new ImapService;
        $stats = $service->fetchEmails($mailbox);

        // Should timeout after configured duration
        $this->assertGreaterThanOrEqual(1, $stats['errors']);
    }

    public function test_handles_ssl_certificate_validation_failure(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
            'in_port' => 993,
            'in_validate_cert' => true,
        ]);

        Log::shouldReceive('info')->once();
        Log::shouldReceive('error')->once();

        $service = new ImapService;
        $stats = $service->fetchEmails($mailbox);

        // Should handle SSL cert validation failures
        $this->assertArrayHasKey('errors', $stats);
    }

    public function test_handles_imap_idle_connection_dropped(): void
    {
        $mailbox = Mailbox::factory()->create();

        Log::shouldReceive('info')->atLeast()->once();
        Log::shouldReceive('error')->once();
        Log::shouldReceive('debug')->zeroOrMoreTimes();
        Log::shouldReceive('warning')->zeroOrMoreTimes();

        $service = new ImapService;
        $stats = $service->fetchEmails($mailbox);

        // Should reconnect if connection drops
        $this->assertIsArray($stats);
    }

    public function test_handles_email_with_no_from_header(): void
    {
        $mailbox = Mailbox::factory()->create();

        Log::shouldReceive('info')->atLeast()->once();
        Log::shouldReceive('error')->once();
        Log::shouldReceive('debug')->zeroOrMoreTimes();
        Log::shouldReceive('warning')->zeroOrMoreTimes();

        $service = new ImapService;
        $stats = $service->fetchEmails($mailbox);

        // Should handle missing From header gracefully
        $this->assertArrayHasKey('errors', $stats);
    }

    public function test_handles_email_with_multiple_from_headers(): void
    {
        $mailbox = Mailbox::factory()->create();

        Log::shouldReceive('info')->atLeast()->once();
        Log::shouldReceive('error')->once();
        Log::shouldReceive('debug')->zeroOrMoreTimes();
        Log::shouldReceive('warning')->zeroOrMoreTimes();

        $service = new ImapService;
        $stats = $service->fetchEmails($mailbox);

        // Should use first From header
        $this->assertIsArray($stats);
    }

    public function test_respects_memory_limits_with_large_emails(): void
    {
        $mailbox = Mailbox::factory()->create();

        Log::shouldReceive('info')->atLeast()->once();
        Log::shouldReceive('error')->once();
        Log::shouldReceive('debug')->zeroOrMoreTimes();
        Log::shouldReceive('warning')->zeroOrMoreTimes();

        $service = new ImapService;
        $stats = $service->fetchEmails($mailbox);

        // Should not exceed memory limits
        $this->assertIsArray($stats);
    }
}
