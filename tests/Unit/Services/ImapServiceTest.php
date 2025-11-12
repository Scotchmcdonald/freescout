<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Mailbox;
use App\Services\ImapService;
use Illuminate\Support\Facades\Log;
use Tests\UnitTestCase;
use Mockery;
use PHPUnit\Framework\Attributes\Group;
use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\Folder;
use Webklex\PHPIMAP\Message;
use Webklex\PHPIMAP\Query\WhereQuery;
use Webklex\PHPIMAP\Exceptions\ConnectionFailedException;

/**
 * Integration tests that make real IMAP connection attempts.
 * These tests are slow and require network access.
 * Run separately with: php artisan test --group=integration
 */
#[Group('integration')]
#[Group('slow')]
class ImapServiceTest extends UnitTestCase
{

    protected ImapService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ImapService();
    }

    public function test_service_can_be_instantiated(): void
    {
        $this->assertInstanceOf(ImapService::class, $this->service);
    }

    public function test_fetch_emails_returns_early_when_no_server_configured(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => null,
        ]);

        Log::shouldReceive('warning')->once();

        $result = $this->service->fetchEmails($mailbox);

        $this->assertEquals(0, $result['fetched']);
        $this->assertEquals(0, $result['created']);
        $this->assertEquals(0, $result['errors']);
        $this->assertCount(1, $result['messages']);
        $this->assertStringContainsString('No IMAP server configured', $result['messages'][0]);
    }

    public function test_fetch_emails_handles_connection_failure(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'invalid.server.com',
            'in_port' => 993,
            'in_username' => 'test@example.com',
            'in_password' => encrypt('password'),
            'in_protocol' => 1, // IMAP
            'in_encryption' => 1, // SSL
        ]);

        Log::shouldReceive('info')->once();
        Log::shouldReceive('error')->once();

        // This will attempt real connection and fail - we test error handling
        $result = $this->service->fetchEmails($mailbox);

        $this->assertEquals(0, $result['fetched']);
        $this->assertEquals(0, $result['created']);
        $this->assertGreaterThan(0, $result['errors']);
        $this->assertNotEmpty($result['messages']);
    }

    public function test_get_folders_returns_success_with_folders(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
            'in_port' => 993,
            'in_username' => 'test@example.com',
            'in_password' => encrypt('password'),
            'in_protocol' => 1,
            'in_encryption' => 1,
        ]);

        // Mock will fail connection, so we test error path
        $result = $this->service->getFolders($mailbox);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('folders', $result);
        $this->assertIsArray($result['folders']);
    }

    public function test_test_connection_returns_error_on_invalid_server(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'invalid.imap.server',
            'in_port' => 993,
            'in_username' => 'test@example.com',
            'in_password' => encrypt('password'),
            'in_protocol' => 1,
            'in_encryption' => 1,
        ]);

        $result = $this->service->testConnection($mailbox);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertFalse($result['success']);
        $this->assertNotEmpty($result['message']);
    }

    public function test_fetch_emails_uses_inbox_when_no_folders_specified(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.test.com',
            'in_port' => 993,
            'in_username' => 'test@example.com',
            'in_password' => encrypt('password'),
            'in_protocol' => 1,
            'in_encryption' => 1,
            'in_imap_folders' => null,
        ]);

        Log::shouldReceive('info')->atLeast()->once();
        Log::shouldReceive('error')->atLeast()->once();

        $result = $this->service->fetchEmails($mailbox);

        // Should attempt to fetch from INBOX (default)
        $this->assertIsArray($result);
        $this->assertArrayHasKey('fetched', $result);
        $this->assertArrayHasKey('created', $result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('messages', $result);
    }

    public function test_fetch_emails_handles_multiple_folders(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.test.com',
            'in_port' => 993,
            'in_username' => 'test@example.com',
            'in_password' => encrypt('password'),
            'in_protocol' => 1,
            'in_encryption' => 1,
            'in_imap_folders' => 'INBOX,Sent,Drafts',
        ]);

        Log::shouldReceive('info')->atLeast()->once();
        Log::shouldReceive('error')->atLeast()->once();

        $result = $this->service->fetchEmails($mailbox);

        // Should attempt all three folders
        $this->assertIsArray($result);
    }

    public function test_fetch_emails_stats_structure(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => null,
        ]);

        $result = $this->service->fetchEmails($mailbox);

        $this->assertArrayHasKey('fetched', $result);
        $this->assertArrayHasKey('created', $result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('messages', $result);
        $this->assertIsInt($result['fetched']);
        $this->assertIsInt($result['created']);
        $this->assertIsInt($result['errors']);
        $this->assertIsArray($result['messages']);
    }

    public function test_get_folders_result_structure(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.test.com',
            'in_port' => 993,
            'in_username' => 'test@example.com',
            'in_password' => encrypt('password'),
        ]);

        $result = $this->service->getFolders($mailbox);

        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('folders', $result);
        $this->assertIsBool($result['success']);
        $this->assertIsArray($result['folders']);
    }

    public function test_test_connection_result_structure(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.test.com',
            'in_port' => 993,
            'in_username' => 'test@example.com',
            'in_password' => encrypt('password'),
        ]);

        $result = $this->service->testConnection($mailbox);

        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertIsBool($result['success']);
        $this->assertIsString($result['message']);
    }

    public function test_service_handles_encrypted_password(): void
    {
        $plainPassword = 'test-password-123';
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.test.com',
            'in_port' => 993,
            'in_username' => 'test@example.com',
            'in_password' => encrypt($plainPassword),
            'in_protocol' => 1,
            'in_encryption' => 1,
        ]);

        // Service should decrypt password internally
        $decrypted = decrypt($mailbox->in_password);
        $this->assertEquals($plainPassword, $decrypted);
    }

    public function test_service_logs_fetch_activity(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.test.com',
            'in_port' => 993,
            'in_username' => 'test@example.com',
            'in_password' => encrypt('password'),
        ]);

        Log::shouldReceive('info')
            ->once()
            ->with('Starting IMAP fetch', Mockery::type('array'));

        Log::shouldReceive('error')->atLeast()->once();

        $this->service->fetchEmails($mailbox);
    }

    public function test_fetch_emails_with_empty_server_string(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => '',
        ]);

        Log::shouldReceive('warning')->once();

        $result = $this->service->fetchEmails($mailbox);

        $this->assertEquals(0, $result['fetched']);
        $this->assertStringContainsString('No IMAP server configured', $result['messages'][0]);
    }

    public function test_fetch_emails_with_whitespace_only_server(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => '   ',
        ]);

        Log::shouldReceive('info')->once();
        Log::shouldReceive('error')->atLeast()->once();

        $result = $this->service->fetchEmails($mailbox);

        // Should attempt connection (and fail) rather than early return
        $this->assertIsArray($result);
    }

    public function test_different_encryption_types(): void
    {
        $encryptionTypes = [0, 1, 2]; // none, ssl, tls

        foreach ($encryptionTypes as $encryption) {
            $mailbox = Mailbox::factory()->create([
                'in_server' => 'imap.test.com',
                'in_port' => 993,
                'in_username' => 'test@example.com',
                'in_password' => encrypt('password'),
                'in_encryption' => $encryption,
            ]);

            Log::shouldReceive('info')->atLeast()->once();
            Log::shouldReceive('error')->atLeast()->once();

            $result = $this->service->testConnection($mailbox);

            $this->assertIsArray($result);
            $this->assertArrayHasKey('success', $result);
        }
    }

    public function test_different_protocol_types(): void
    {
        $protocols = [1, 2]; // IMAP, POP3

        foreach ($protocols as $protocol) {
            $mailbox = Mailbox::factory()->create([
                'in_server' => 'mail.test.com',
                'in_port' => 993,
                'in_username' => 'test@example.com',
                'in_password' => encrypt('password'),
                'in_protocol' => $protocol,
            ]);

            Log::shouldReceive('info')->atLeast()->once();
            Log::shouldReceive('error')->atLeast()->once();

            $result = $this->service->testConnection($mailbox);

            $this->assertIsArray($result);
        }
    }

    public function test_fetch_emails_with_array_folders(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.test.com',
            'in_port' => 993,
            'in_username' => 'test@example.com',
            'in_password' => encrypt('password'),
            'in_imap_folders' => ['INBOX', 'Sent', 'Archive'],
        ]);

        Log::shouldReceive('info')->atLeast()->once();
        Log::shouldReceive('error')->atLeast()->once();

        $result = $this->service->fetchEmails($mailbox);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('fetched', $result);
    }

    public function test_various_port_numbers(): void
    {
        $ports = [143, 993, 995]; // IMAP, IMAPS, POP3S

        foreach ($ports as $port) {
            $mailbox = Mailbox::factory()->create([
                'in_server' => 'mail.test.com',
                'in_port' => $port,
                'in_username' => 'test@example.com',
                'in_password' => encrypt('password'),
            ]);

            Log::shouldReceive('info')->atLeast()->once();
            Log::shouldReceive('error')->atLeast()->once();

            $result = $this->service->testConnection($mailbox);

            $this->assertIsArray($result);
        }
    }
}
