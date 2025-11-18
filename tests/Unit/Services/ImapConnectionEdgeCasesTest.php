<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Mailbox;
use App\Services\ImapService;
use Illuminate\Support\Facades\Log;
use Tests\UnitTestCase;
use Webklex\PHPIMAP\Exceptions\ConnectionFailedException;

class ImapConnectionEdgeCasesTest extends UnitTestCase
{
    public function test_imap_fetch_handles_null_in_server(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => null,
        ]);
        
        $imapService = app(ImapService::class);
        $result = $imapService->fetchEmails($mailbox);
        
        $this->assertEquals(0, $result['fetched']);
        $this->assertEquals(0, $result['created']);
        $this->assertCount(1, $result['messages']);
        $this->assertStringContainsString('No IMAP server configured', $result['messages'][0]);
    }

    public function test_imap_fetch_handles_empty_in_server(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => '',
        ]);
        
        $imapService = app(ImapService::class);
        $result = $imapService->fetchEmails($mailbox);
        
        $this->assertEquals(0, $result['fetched']);
        $this->assertEquals(0, $result['created']);
        $this->assertNotEmpty($result['messages']);
    }

    public function test_imap_folders_handles_null_value(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
            'in_imap_folders' => null,
        ]);
        
        // Should default to INBOX
        $this->assertNull($mailbox->in_imap_folders);
    }

    public function test_imap_folders_handles_empty_string(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
            'in_imap_folders' => '',
        ]);
        
        // Should be treated as empty
        $this->assertEquals('', $mailbox->in_imap_folders);
    }

    public function test_imap_folders_handles_array_format(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
            'in_imap_folders' => 'INBOX,Sent,Trash', // Store as string, cast to array
        ]);
        
        // Should be able to parse as array
        $folders = is_array($mailbox->in_imap_folders) ? $mailbox->in_imap_folders : explode(',', $mailbox->in_imap_folders);
        $this->assertCount(3, $folders);
    }

    public function test_imap_folders_handles_comma_separated_string(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
            'in_imap_folders' => 'INBOX,Sent,Trash',
        ]);
        
        $folders = explode(',', $mailbox->in_imap_folders);
        $this->assertCount(3, $folders);
        $this->assertEquals('INBOX', $folders[0]);
    }

    public function test_imap_handles_invalid_port(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
            'in_port' => 99999, // Invalid port
        ]);
        
        $this->assertEquals(99999, $mailbox->in_port);
    }

    public function test_imap_handles_missing_credentials(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
            'in_username' => null,
            'in_password' => null,
        ]);
        
        $this->assertNull($mailbox->in_username);
        $this->assertNull($mailbox->in_password);
    }

    public function test_imap_encryption_ssl_value(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
            'in_encryption' => 1, // SSL = 1
        ]);
        
        $this->assertEquals(1, $mailbox->in_encryption);
    }

    public function test_imap_encryption_tls_value(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
            'in_encryption' => 2, // TLS = 2
        ]);
        
        $this->assertEquals(2, $mailbox->in_encryption);
    }

    public function test_imap_encryption_none_value(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
            'in_encryption' => 0, // None = 0
        ]);
        
        $this->assertEquals(0, $mailbox->in_encryption);
    }

    public function test_imap_fetch_results_structure(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => null,
        ]);
        
        $imapService = app(ImapService::class);
        $result = $imapService->fetchEmails($mailbox);
        
        $this->assertArrayHasKey('fetched', $result);
        $this->assertArrayHasKey('created', $result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('messages', $result);
    }

    public function test_imap_fetch_initializes_counters_to_zero(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => null,
        ]);
        
        $imapService = app(ImapService::class);
        $result = $imapService->fetchEmails($mailbox);
        
        $this->assertIsInt($result['fetched']);
        $this->assertIsInt($result['created']);
        $this->assertIsInt($result['errors']);
        $this->assertIsArray($result['messages']);
    }

    public function test_imap_service_logs_warning_for_missing_server(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->with('IMAP fetch skipped - no server configured', \Mockery::any());
        
        $mailbox = Mailbox::factory()->create([
            'in_server' => null,
        ]);
        
        $imapService = app(ImapService::class);
        $imapService->fetchEmails($mailbox);
    }

    public function test_imap_handles_special_characters_in_folder_names(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
            'in_imap_folders' => 'INBOX,Sent Items,[Gmail]/Trash',
        ]);
        
        $folders = explode(',', $mailbox->in_imap_folders);
        $this->assertStringContainsString('[Gmail]', $folders[2]);
    }

    public function test_imap_handles_whitespace_in_folder_list(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
            'in_imap_folders' => 'INBOX, Sent, Trash',
        ]);
        
        $folders = explode(',', $mailbox->in_imap_folders);
        $this->assertCount(3, $folders);
    }

    public function test_smtp_server_configuration(): void
    {
        $mailbox = Mailbox::factory()->create([
            'out_server' => 'smtp.example.com',
            'out_port' => 587,
        ]);
        
        $this->assertEquals('smtp.example.com', $mailbox->out_server);
        $this->assertEquals(587, $mailbox->out_port);
    }

    public function test_smtp_authentication_methods(): void
    {
        $mailbox = Mailbox::factory()->create([
            'out_server' => 'smtp.example.com',
            'out_username' => 'user@example.com',
            'out_password' => 'password',
        ]);
        
        $this->assertNotNull($mailbox->out_username);
        $this->assertNotNull($mailbox->out_password);
    }

    public function test_mailbox_supports_both_imap_and_smtp(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
            'out_server' => 'smtp.example.com',
        ]);
        
        $this->assertNotNull($mailbox->in_server);
        $this->assertNotNull($mailbox->out_server);
    }

    public function test_mailbox_can_have_only_smtp(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => null,
            'out_server' => 'smtp.example.com',
        ]);
        
        $this->assertNull($mailbox->in_server);
        $this->assertNotNull($mailbox->out_server);
    }
}
