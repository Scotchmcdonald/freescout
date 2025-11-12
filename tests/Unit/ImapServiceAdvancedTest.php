<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Mailbox;
use App\Services\ImapService;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\UnitTestCase;
use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\Exceptions\ConnectionFailedException;

class ImapServiceAdvancedTest extends UnitTestCase
{

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Create a mocked IMAP service that simulates connection failure
     */
    protected function createMockedServiceWithConnectionFailure(): ImapService
    {
        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('connect')
            ->andThrow(new ConnectionFailedException('Connection failed'));

        $service = Mockery::mock(ImapService::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $service->shouldReceive('createClient')
            ->andReturn($mockClient);

        return $service;
    }

    /** Test handling of empty IMAP server configuration */
    public function test_handles_missing_imap_server_configuration(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => null,
        ]);

        $service = $this->createMockedServiceWithConnectionFailure();
        $result = $service->fetchEmails($mailbox);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('fetched', $result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertEquals(0, $result['fetched']);
    }

    /** Test handling of invalid IMAP server hostname */
    public function test_handles_invalid_imap_hostname(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'invalid.nonexistent.server.local',
            'in_port' => 993,
            'in_username' => 'test@test.com',
            'in_password' => 'password',
            'in_protocol' => 1, // IMAP
            'in_encryption' => 1, // SSL
        ]);

        $service = $this->createMockedServiceWithConnectionFailure();
        $result = $service->fetchEmails($mailbox);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('errors', $result);
        // Connection should fail gracefully
    }

    /** Test handling of blank username */
    public function test_handles_blank_username(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.test.com',
            'in_port' => 993,
            'in_username' => '',
            'in_password' => 'password',
        ]);

        $service = $this->createMockedServiceWithConnectionFailure();
        $result = $service->fetchEmails($mailbox);

        $this->assertIsArray($result);
        // Should handle gracefully without crashing
    }

    /** Test handling of blank password */
    public function test_handles_blank_password(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.test.com',
            'in_port' => 993,
            'in_username' => 'test@test.com',
            'in_password' => '',
        ]);

        $service = $this->createMockedServiceWithConnectionFailure();
        $result = $service->fetchEmails($mailbox);

        $this->assertIsArray($result);
        // Should handle gracefully
    }

    /** Test handling of invalid port number */
    public function test_handles_invalid_port_number(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.test.com',
            'in_port' => 99999, // Invalid port
            'in_username' => 'test@test.com',
            'in_password' => 'password',
        ]);

        $service = $this->createMockedServiceWithConnectionFailure();
        $result = $service->fetchEmails($mailbox);

        $this->assertIsArray($result);
        // Should handle gracefully
    }

    /** Test handling of non-existent folder */
    public function test_handles_nonexistent_imap_folder(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.test.com',
            'in_port' => 993,
            'in_username' => 'test@test.com',
            'in_password' => 'password',
            'in_imap_folders' => 'NonExistentFolder,AnotherBadFolder',
        ]);

        $service = $this->createMockedServiceWithConnectionFailure();
        $result = $service->fetchEmails($mailbox);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('messages', $result);
        // Should log warning but not crash
    }

    /** Test handling of multiple folders */
    public function test_handles_multiple_imap_folders(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.test.com',
            'in_port' => 993,
            'in_username' => 'test@test.com',
            'in_password' => 'password',
            'in_imap_folders' => 'INBOX,Sent,Drafts',
        ]);

        $service = $this->createMockedServiceWithConnectionFailure();
        $result = $service->fetchEmails($mailbox);

        $this->assertIsArray($result);
        // Should attempt to process all folders
    }

    /** Test return structure is consistent */
    public function test_return_structure_is_always_consistent(): void
    {
        $mailbox1 = Mailbox::factory()->create(['in_server' => null]);
        $mailbox2 = Mailbox::factory()->create([
            'in_server' => 'test.com',
            'in_port' => 993,
        ]);

        $service1 = $this->createMockedServiceWithConnectionFailure();
        $service2 = $this->createMockedServiceWithConnectionFailure();

        $result1 = $service1->fetchEmails($mailbox1);
        $result2 = $service2->fetchEmails($mailbox2);

        // Both should have same structure
        $expectedKeys = ['fetched', 'created', 'errors', 'messages'];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $result1);
            $this->assertArrayHasKey($key, $result2);
        }
    }

    /** Test service does not throw exceptions */
    public function test_service_never_throws_exceptions(): void
    {
        $mailboxes = [
            Mailbox::factory()->create(['in_server' => null]),
            Mailbox::factory()->create(['in_server' => 'invalid.host']),
            Mailbox::factory()->create(['in_port' => 99999]), // Use an invalid port number
        ];

        $service = $this->createMockedServiceWithConnectionFailure();

        foreach ($mailboxes as $mailbox) {
            try {
                $result = $service->fetchEmails($mailbox);
                $this->assertIsArray($result);
            } catch (\Exception $e) {
                $this->fail('Service should not throw exceptions: '.$e->getMessage());
            }
        }
    }

    /** Test handling of various encryption types */
    public function test_handles_different_encryption_types(): void
    {
        $encryptionTypes = [0, 1, 2]; // None, SSL, TLS

        $service = $this->createMockedServiceWithConnectionFailure();

        foreach ($encryptionTypes as $encryption) {
            $mailbox = Mailbox::factory()->create([
                'in_server' => 'imap.test.com',
                'in_port' => 993,
                'in_encryption' => $encryption,
            ]);

            $result = $service->fetchEmails($mailbox);
            $this->assertIsArray($result);
        }
    }

    /** Test handling of various protocol types */
    public function test_handles_different_protocol_types(): void
    {
        $protocols = [1, 2]; // IMAP, POP3 (if supported)

        $service = $this->createMockedServiceWithConnectionFailure();

        foreach ($protocols as $protocol) {
            $mailbox = Mailbox::factory()->create([
                'in_server' => 'mail.test.com',
                'in_port' => 993,
                'in_protocol' => $protocol,
            ]);

            $result = $service->fetchEmails($mailbox);
            $this->assertIsArray($result);
        }
    }

    /** Test statistics are properly initialized */
    public function test_statistics_are_properly_initialized(): void
    {
        $mailbox = Mailbox::factory()->create(['in_server' => null]);

        $service = $this->createMockedServiceWithConnectionFailure();
        $result = $service->fetchEmails($mailbox);

        $this->assertEquals(0, $result['fetched']);
        $this->assertEquals(0, $result['created']);
        $this->assertEquals(0, $result['errors']);
        $this->assertIsArray($result['messages']);
    }

    /** Test logging occurs for connection attempts */
    public function test_logs_connection_attempts(): void
    {
        // When server is configured, it should log info about starting fetch
        Log::shouldReceive('info')->atLeast()->once();

        // When connection fails, it should log error
        Log::shouldReceive('error')->atLeast()->once();

        // Allow other log levels
        Log::shouldReceive('warning')->zeroOrMoreTimes();
        Log::shouldReceive('debug')->zeroOrMoreTimes();

        $mailbox = Mailbox::factory()->create([
            'in_server' => 'invalid.test.com',
            'in_port' => 993,
            'in_username' => 'test@test.com',
            'in_password' => 'test',
        ]);

        $service = $this->createMockedServiceWithConnectionFailure();
        $result = $service->fetchEmails($mailbox);

        // Verify the service returns proper structure even on failure
        $this->assertIsArray($result);
        $this->assertArrayHasKey('errors', $result);
    }
}
