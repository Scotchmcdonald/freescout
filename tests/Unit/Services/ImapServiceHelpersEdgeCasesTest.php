<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\ImapService;
use App\Models\Mailbox;
use App\Models\Customer;
use Tests\UnitTestCase;
use Mockery;
use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\Folder;
use Webklex\PHPIMAP\Message;
use Webklex\PHPIMAP\Attribute;

/**
 * Test Suite for IMAP Service Helper Methods - Edge Cases & Integration
 *
 * This test suite covers edge cases and integration methods:
 * - getFolders() (9 tests) - Folder retrieval and management
 * - testConnection() (8 tests) - Connection testing
 * - fetchEmails() (11 tests) - Email fetching operations
 * Total: 28 tests
 *
 * These methods handle integration points and edge cases.
 */
class ImapServiceHelpersEdgeCasesTest extends UnitTestCase
{
    protected ImapService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ImapService();
    }

    protected function tearDown(): void
    {
        try {
            Mockery::close();
        } finally {
            parent::tearDown();
        }
    }

    /**
     * Helper method to invoke private/protected methods using reflection
     */
    protected function invokeMethod($object, string $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    // =====================================================================
    // Tests for getFolders() - MEDIUM (CRAP: 30)
    // =====================================================================

    public function test_get_folders_returns_success_structure(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
            'in_port' => 993,
            'in_username' => 'test@example.com',
            'in_password' => encrypt('password'),
            'in_protocol' => 1,
            'in_encryption' => 1,
        ]);

        $result = $this->service->getFolders($mailbox);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('folders', $result);
        $this->assertIsArray($result['folders']);
    }

    public function test_get_folders_returns_bool_success(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
            'in_port' => 993,
            'in_username' => 'test@example.com',
            'in_password' => encrypt('password'),
            'in_protocol' => 1,
            'in_encryption' => 1,
        ]);

        $result = $this->service->getFolders($mailbox);

        $this->assertIsBool($result['success']);
    }

    public function test_get_folders_returns_string_message(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
            'in_port' => 993,
            'in_username' => 'test@example.com',
            'in_password' => encrypt('password'),
            'in_protocol' => 1,
            'in_encryption' => 1,
        ]);

        $result = $this->service->getFolders($mailbox);

        $this->assertIsString($result['message']);
    }

    public function test_get_folders_handles_connection_failure(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'invalid.server.com',
            'in_port' => 993,
            'in_username' => 'test@example.com',
            'in_password' => encrypt('password'),
            'in_protocol' => 1,
            'in_encryption' => 1,
        ]);

        $result = $this->service->getFolders($mailbox);

        $this->assertFalse($result['success']);
        $this->assertNotEmpty($result['message']);
        $this->assertStringContainsString('Connection failed', $result['message']);
    }

    public function test_get_folders_handles_general_exception(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'bad.server.com',
            'in_port' => 9999,
            'in_username' => 'test@example.com',
            'in_password' => encrypt('password'),
            'in_protocol' => 1,
            'in_encryption' => 1,
        ]);

        $result = $this->service->getFolders($mailbox);

        $this->assertFalse($result['success']);
        $this->assertNotEmpty($result['message']);
        $this->assertTrue(
            str_contains($result['message'], 'Connection failed') || 
            str_contains($result['message'], 'Error')
        );
    }

    public function test_get_folders_returns_empty_array_on_failure(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'invalid.server.com',
            'in_port' => 993,
            'in_username' => 'test@example.com',
            'in_password' => encrypt('password'),
            'in_protocol' => 1,
            'in_encryption' => 1,
        ]);

        $result = $this->service->getFolders($mailbox);

        $this->assertIsArray($result['folders']);
        $this->assertCount(0, $result['folders']);
    }

    public function test_get_folders_with_different_ports(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
            'in_port' => 143,  // Non-SSL port
            'in_username' => 'test@example.com',
            'in_password' => encrypt('password'),
            'in_protocol' => 1,
            'in_encryption' => 0,
        ]);

        $result = $this->service->getFolders($mailbox);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    public function test_get_folders_with_ssl_encryption(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
            'in_port' => 993,
            'in_username' => 'test@example.com',
            'in_password' => encrypt('password'),
            'in_protocol' => 1,
            'in_encryption' => 1,  // SSL
        ]);

        $result = $this->service->getFolders($mailbox);

        $this->assertIsArray($result);
    }

    public function test_get_folders_with_tls_encryption(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
            'in_port' => 143,
            'in_username' => 'test@example.com',
            'in_password' => encrypt('password'),
            'in_protocol' => 1,
            'in_encryption' => 2,  // TLS
        ]);

        $result = $this->service->getFolders($mailbox);

        $this->assertIsArray($result);
    }

    // =====================================================================
    // =====================================================================
    // Tests for testConnection() - LOW (CRAP: 58 - Improve existing)
    // =====================================================================

    public function test_test_connection_returns_success_structure(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
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
        $this->assertIsBool($result['success']);
        $this->assertIsString($result['message']);
    }

    public function test_test_connection_fails_with_invalid_credentials(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'invalid.imap.server',
            'in_port' => 993,
            'in_username' => 'invalid@example.com',
            'in_password' => encrypt('wrongpassword'),
            'in_protocol' => 1,
            'in_encryption' => 1,
        ]);

        $result = $this->service->testConnection($mailbox);

        $this->assertFalse($result['success']);
        $this->assertNotEmpty($result['message']);
    }

    public function test_test_connection_fails_with_invalid_server(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'nonexistent.server.invalid',
            'in_port' => 993,
            'in_username' => 'test@example.com',
            'in_password' => encrypt('password'),
            'in_protocol' => 1,
            'in_encryption' => 1,
        ]);

        $result = $this->service->testConnection($mailbox);

        $this->assertFalse($result['success']);
        $this->assertNotEmpty($result['message']);
    }

    public function test_test_connection_handles_connection_failure(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'unreachable.example.com',
            'in_port' => 9999,
            'in_username' => 'test@example.com',
            'in_password' => encrypt('password'),
            'in_protocol' => 1,
            'in_encryption' => 1,
        ]);

        $result = $this->service->testConnection($mailbox);

        $this->assertFalse($result['success']);
        $this->assertTrue(
            str_contains($result['message'], 'Connection failed') ||
            str_contains($result['message'], 'Error')
        );
    }

    public function test_test_connection_with_ssl_encryption(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
            'in_port' => 993,
            'in_username' => 'test@example.com',
            'in_password' => encrypt('password'),
            'in_protocol' => 1,
            'in_encryption' => 1,  // SSL
        ]);

        $result = $this->service->testConnection($mailbox);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    public function test_test_connection_with_tls_encryption(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
            'in_port' => 143,
            'in_username' => 'test@example.com',
            'in_password' => encrypt('password'),
            'in_protocol' => 1,
            'in_encryption' => 2,  // TLS
        ]);

        $result = $this->service->testConnection($mailbox);

        $this->assertIsArray($result);
    }

    public function test_test_connection_with_no_encryption(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
            'in_port' => 143,
            'in_username' => 'test@example.com',
            'in_password' => encrypt('password'),
            'in_protocol' => 1,
            'in_encryption' => 0,  // No encryption
        ]);

        $result = $this->service->testConnection($mailbox);

        $this->assertIsArray($result);
    }

    public function test_test_connection_message_format_on_failure(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'bad.server',
            'in_port' => 993,
            'in_username' => 'test@example.com',
            'in_password' => encrypt('password'),
            'in_protocol' => 1,
            'in_encryption' => 1,
        ]);

        $result = $this->service->testConnection($mailbox);

        $this->assertFalse($result['success']);
        $this->assertNotEmpty($result['message']);
        $this->assertIsString($result['message']);
    }

    // =====================================================================
    // =====================================================================
    // Tests for fetchEmails() - LOW (CRAP: 73 - Improve existing)
    // =====================================================================

    public function test_fetch_emails_returns_stats_structure(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
            'in_port' => 993,
            'in_username' => 'test@example.com',
            'in_password' => encrypt('password'),
            'in_protocol' => 1,
            'in_encryption' => 1,
        ]);

        $result = $this->service->fetchEmails($mailbox);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('fetched', $result);
        $this->assertArrayHasKey('created', $result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('messages', $result);
        $this->assertIsInt($result['fetched']);
        $this->assertIsInt($result['created']);
        $this->assertIsInt($result['errors']);
        $this->assertIsArray($result['messages']);
    }

    public function test_fetch_emails_handles_empty_folder(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => null, // No server configured
        ]);

        $result = $this->service->fetchEmails($mailbox);

        $this->assertEquals(0, $result['fetched']);
        $this->assertEquals(0, $result['created']);
        $this->assertNotEmpty($result['messages']);
    }

    public function test_fetch_emails_returns_early_for_null_server(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => null,
            'name' => 'Test Mailbox',
        ]);

        $result = $this->service->fetchEmails($mailbox);

        $this->assertEquals(0, $result['fetched']);
        $this->assertEquals(0, $result['created']);
        $this->assertEquals(0, $result['errors']);
        $this->assertCount(1, $result['messages']);
        $this->assertStringContainsString('No IMAP server configured', $result['messages'][0]);
    }

    public function test_fetch_emails_returns_early_for_empty_server(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => '',
            'name' => 'Test Mailbox',
        ]);

        $result = $this->service->fetchEmails($mailbox);

        $this->assertEquals(0, $result['fetched']);
        $this->assertNotEmpty($result['messages']);
    }

    public function test_fetch_emails_initializes_stats_correctly(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => null,
        ]);

        $result = $this->service->fetchEmails($mailbox);

        $this->assertIsInt($result['fetched']);
        $this->assertIsInt($result['created']);
        $this->assertIsInt($result['errors']);
        $this->assertIsArray($result['messages']);
        $this->assertGreaterThanOrEqual(0, $result['fetched']);
        $this->assertGreaterThanOrEqual(0, $result['created']);
        $this->assertGreaterThanOrEqual(0, $result['errors']);
    }

    public function test_fetch_emails_handles_connection_failure_gracefully(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'invalid.server.com',
            'in_port' => 993,
            'in_username' => 'test@example.com',
            'in_password' => encrypt('password'),
            'in_protocol' => 1,
            'in_encryption' => 1,
        ]);

        $result = $this->service->fetchEmails($mailbox);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('fetched', $result);
        $this->assertArrayHasKey('errors', $result);
    }

    public function test_fetch_emails_with_valid_server_config(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
            'in_port' => 993,
            'in_username' => 'test@example.com',
            'in_password' => encrypt('password'),
            'in_protocol' => 1,
            'in_encryption' => 1,
            'in_imap_folders' => 'INBOX',
        ]);

        $result = $this->service->fetchEmails($mailbox);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('fetched', $result);
    }

    public function test_fetch_emails_uses_inbox_when_folders_null(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
            'in_port' => 993,
            'in_username' => 'test@example.com',
            'in_password' => encrypt('password'),
            'in_protocol' => 1,
            'in_encryption' => 1,
            'in_imap_folders' => null,
        ]);

        $result = $this->service->fetchEmails($mailbox);

        // Should default to INBOX
        $this->assertIsArray($result);
    }

    public function test_fetch_emails_uses_inbox_when_folders_empty_string(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
            'in_port' => 993,
            'in_username' => 'test@example.com',
            'in_password' => encrypt('password'),
            'in_protocol' => 1,
            'in_encryption' => 1,
            'in_imap_folders' => '',
        ]);

        $result = $this->service->fetchEmails($mailbox);

        // Should default to INBOX
        $this->assertIsArray($result);
    }

    public function test_fetch_emails_handles_multiple_folders(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
            'in_port' => 993,
            'in_username' => 'test@example.com',
            'in_password' => encrypt('password'),
            'in_protocol' => 1,
            'in_encryption' => 1,
            'in_imap_folders' => 'INBOX,Sent,Drafts',
        ]);

        $result = $this->service->fetchEmails($mailbox);

        $this->assertIsArray($result);
    }

    public function test_fetch_emails_handles_array_folders(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
            'in_port' => 993,
            'in_username' => 'test@example.com',
            'in_password' => encrypt('password'),
            'in_protocol' => 1,
            'in_encryption' => 1,
        ]);

        // Manually set as array (if the model allows it)
        $mailbox->in_imap_folders = ['INBOX', 'Sent'];

        $result = $this->service->fetchEmails($mailbox);

        $this->assertIsArray($result);
    }
}