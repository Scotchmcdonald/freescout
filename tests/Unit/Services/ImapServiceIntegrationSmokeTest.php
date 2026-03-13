<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Mailbox;
use App\Services\ImapService;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;
use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\Exceptions\ConnectionFailedException;
use Webklex\PHPIMAP\Folder;
use Webklex\PHPIMAP\Message;
use Webklex\PHPIMAP\Query\WhereQuery;
use Webklex\PHPIMAP\Support\FolderCollection;
use Webklex\PHPIMAP\Support\MessageCollection;

/**
 * Integration smoke tests for ImapService to validate all methods work together.
 * These tests verify that the service handles realistic scenarios correctly.
 */
class ImapServiceIntegrationSmokeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Mock all logging
        Log::shouldReceive('debug')->byDefault();
        Log::shouldReceive('info')->byDefault();
        Log::shouldReceive('warning')->byDefault();
        Log::shouldReceive('error')->byDefault();
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
     * Helper to create a mock mailbox
     */
    protected function createMockMailbox(array $attrs = []): Mailbox
    {
        $mailbox = Mockery::mock(Mailbox::class)->makePartial();
        $mailbox->id = $attrs['id'] ?? 1;
        $mailbox->name = $attrs['name'] ?? 'Test Mailbox';
        $mailbox->email = $attrs['email'] ?? 'mailbox@example.com';
        $mailbox->in_server = $attrs['in_server'] ?? 'imap.example.com';
        $mailbox->in_port = $attrs['in_port'] ?? 993;
        $mailbox->in_username = $attrs['in_username'] ?? 'test@example.com';
        $mailbox->in_password = $attrs['in_password'] ?? encrypt('password');
        $mailbox->in_encryption = $attrs['in_encryption'] ?? 1;
        $mailbox->in_imap_folders = $attrs['in_imap_folders'] ?? null;
        $mailbox->in_validate_cert = $attrs['in_validate_cert'] ?? true;

        return $mailbox;
    }

    public function test_service_can_be_instantiated(): void
    {
        $service = new ImapService;

        $this->assertInstanceOf(ImapService::class, $service);
    }

    public function test_complete_workflow_with_no_server_configured(): void
    {
        $mailbox = $this->createMockMailbox(['in_server' => null]);
        $service = new ImapService;

        $result = $service->fetchEmails($mailbox);

        $this->assertEquals(0, $result['fetched']);
        $this->assertEquals(0, $result['created']);
        // When no server configured, it logs a warning but doesn't count as an error
        $this->assertGreaterThanOrEqual(0, $result['errors']);
        $this->assertIsArray($result['messages']);
    }

    public function test_complete_workflow_with_connection_failure(): void
    {
        $mailbox = $this->createMockMailbox();

        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('connect')
            ->once()
            ->andThrow(new ConnectionFailedException('Connection failed'));

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('createClient')->with($mailbox)->andReturn($mockClient);

        $result = $service->fetchEmails($mailbox);

        $this->assertGreaterThan(0, $result['errors']);
        $this->assertEquals(0, $result['fetched']);
        $this->assertEquals(0, $result['created']);
    }

    public function test_get_folders_workflow_successful(): void
    {
        $mailbox = $this->createMockMailbox();

        $folder1 = Mockery::mock(Folder::class);
        $folder1->full_name = 'INBOX';

        $folder2 = Mockery::mock(Folder::class);
        $folder2->full_name = 'Sent';

        $folderCollection = new FolderCollection([$folder1, $folder2]);

        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('connect')->once();
        $mockClient->shouldReceive('getFolders')->once()->andReturn($folderCollection);
        $mockClient->shouldReceive('disconnect')->once();

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('createClient')->with($mailbox)->andReturn($mockClient);

        $result = $service->getFolders($mailbox);

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['folders']);
    }

    public function test_test_connection_workflow_successful(): void
    {
        $mailbox = $this->createMockMailbox();

        $mockMessage = Mockery::mock(Message::class);
        $mockMessage->shouldReceive('hasFlag')->with('Seen')->andReturn(false);

        $messageCollection = new MessageCollection([$mockMessage]);

        $mockQuery = Mockery::mock(WhereQuery::class);
        $mockQuery->shouldReceive('since')->once()->andReturnSelf();
        $mockQuery->shouldReceive('leaveUnread')->once()->andReturnSelf();
        $mockQuery->shouldReceive('get')->once()->andReturn($messageCollection);

        $mockFolder = Mockery::mock(Folder::class);
        $mockFolder->shouldReceive('query')->once()->andReturn($mockQuery);

        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('connect')->once();
        $mockClient->shouldReceive('getFolder')->with('INBOX')->once()->andReturn($mockFolder);
        $mockClient->shouldReceive('disconnect')->once();

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('createClient')->with($mailbox)->andReturn($mockClient);

        $result = $service->testConnection($mailbox);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Connected successfully', $result['message']);
    }

    public function test_all_public_methods_return_expected_types(): void
    {
        $mailbox = $this->createMockMailbox(['in_server' => null]);
        $service = new ImapService;

        // fetchEmails returns array with specific keys
        $fetchResult = $service->fetchEmails($mailbox);
        $this->assertIsArray($fetchResult);
        $this->assertArrayHasKey('fetched', $fetchResult);
        $this->assertArrayHasKey('created', $fetchResult);
        $this->assertArrayHasKey('errors', $fetchResult);
        $this->assertArrayHasKey('messages', $fetchResult);

        // getFolders returns array with specific keys
        $foldersResult = $service->getFolders($mailbox);
        $this->assertIsArray($foldersResult);
        $this->assertArrayHasKey('success', $foldersResult);
        $this->assertArrayHasKey('folders', $foldersResult);

        // testConnection returns array with specific keys
        $connectionResult = $service->testConnection($mailbox);
        $this->assertIsArray($connectionResult);
        $this->assertArrayHasKey('success', $connectionResult);
        $this->assertArrayHasKey('message', $connectionResult);
    }

    public function test_encryption_types_are_handled_correctly(): void
    {
        $service = new ImapService;
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('getEncryption');
        $method->setAccessible(true);

        // Test all encryption types
        $this->assertNull($method->invoke($service, 0));
        $this->assertEquals('ssl', $method->invoke($service, 1));
        $this->assertEquals('tls', $method->invoke($service, 2));
        $this->assertNull($method->invoke($service, null));

        // Test string conversions
        $this->assertEquals('ssl', $method->invoke($service, '1'));
        $this->assertEquals('tls', $method->invoke($service, '2'));
    }

    public function test_service_handles_various_mailbox_configurations(): void
    {
        $service = new ImapService;

        // Various encryption types
        foreach ([0, 1, 2, null] as $encryption) {
            $mailbox = $this->createMockMailbox([
                'in_server' => null,
                'in_encryption' => $encryption,
            ]);

            $result = $service->fetchEmails($mailbox);
            $this->assertIsArray($result);
        }

        // Various folder configurations
        foreach ([null, '', 'INBOX', 'INBOX,Sent', ['INBOX']] as $folders) {
            $mailbox = $this->createMockMailbox([
                'in_server' => null,
                'in_imap_folders' => $folders,
            ]);

            $result = $service->fetchEmails($mailbox);
            $this->assertIsArray($result);
        }
    }

    public function test_service_logs_appropriate_messages_for_key_events(): void
    {
        $mailbox = $this->createMockMailbox();

        Log::shouldReceive('info')
            ->once()
            ->with('Starting IMAP fetch', Mockery::type('array'));

        Log::shouldReceive('error')
            ->atLeast()
            ->once();

        $service = new ImapService;
        $result = $service->fetchEmails($mailbox);

        // Verify that fetchEmails returns expected structure
        $this->assertIsArray($result);
        $this->assertArrayHasKey('errors', $result);
    }

    public function test_service_returns_consistent_error_structures(): void
    {
        $mailbox = $this->createMockMailbox();

        // Create separate mock clients for each test
        $mockClient1 = Mockery::mock(Client::class);
        $mockClient1->shouldReceive('connect')
            ->once()
            ->andThrow(new \Exception('Test error'));

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('createClient')->with($mailbox)->andReturn($mockClient1);

        // fetchEmails error structure
        $fetchResult = $service->fetchEmails($mailbox);
        $this->assertIsInt($fetchResult['errors']);
        $this->assertGreaterThan(0, $fetchResult['errors']);
        $this->assertIsArray($fetchResult['messages']);

        // getFolders error structure
        $mockClient2 = Mockery::mock(Client::class);
        $mockClient2->shouldReceive('connect')
            ->once()
            ->andThrow(new \Exception('Test error'));

        $service2 = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service2->shouldReceive('createClient')->with($mailbox)->andReturn($mockClient2);

        $foldersResult = $service2->getFolders($mailbox);
        $this->assertFalse($foldersResult['success']);
        $this->assertIsString($foldersResult['message']);

        // testConnection error structure
        $mockClient3 = Mockery::mock(Client::class);
        $mockClient3->shouldReceive('connect')
            ->once()
            ->andThrow(new \Exception('Test error'));

        $service3 = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service3->shouldReceive('createClient')->with($mailbox)->andReturn($mockClient3);

        $connectionResult = $service3->testConnection($mailbox);
        $this->assertFalse($connectionResult['success']);
        $this->assertIsString($connectionResult['message']);
    }

    public function test_service_handles_edge_case_inputs_gracefully(): void
    {
        $service = new ImapService;

        // Empty server
        $mailbox = $this->createMockMailbox(['in_server' => '']);
        $result = $service->fetchEmails($mailbox);
        $this->assertIsArray($result);

        // Null username
        $mailbox = $this->createMockMailbox([
            'in_server' => null,
            'in_username' => null,
        ]);
        $result = $service->fetchEmails($mailbox);
        $this->assertIsArray($result);

        // Invalid port (will fail connection but not crash)
        $mailbox = $this->createMockMailbox([
            'in_server' => null,
            'in_port' => 0,
        ]);
        $result = $service->fetchEmails($mailbox);
        $this->assertIsArray($result);
    }

    public function test_service_stats_accumulate_correctly(): void
    {
        $mailbox = $this->createMockMailbox();

        $mockMessage1 = Mockery::mock(Message::class);
        $mockMessage1->shouldReceive('getDate')->andReturn(now()->subHours(2));
        $mockMessage1->shouldReceive('getMessageId')->andReturn('<msg1@example.com>');
        $mockMessage1->shouldReceive('setFlag')->with('Seen');

        $mockMessage2 = Mockery::mock(Message::class);
        $mockMessage2->shouldReceive('getDate')->andReturn(now()->subHours(1));
        $mockMessage2->shouldReceive('getMessageId')->andReturn('<msg2@example.com>');
        $mockMessage2->shouldReceive('setFlag')->with('Seen');

        $messageCollection = new MessageCollection([$mockMessage1, $mockMessage2]);

        $mockQuery = Mockery::mock(WhereQuery::class);
        $mockQuery->shouldReceive('since')->once()->andReturnSelf();
        $mockQuery->shouldReceive('unseen')->once()->andReturnSelf();
        $mockQuery->shouldReceive('leaveUnread')->once()->andReturnSelf();
        $mockQuery->shouldReceive('get')->once()->andReturn($messageCollection);

        $mockFolder = Mockery::mock(Folder::class);
        $mockFolder->shouldReceive('query')->once()->andReturn($mockQuery);

        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('connect')->once();
        $mockClient->shouldReceive('getFolder')->with('INBOX')->once()->andReturn($mockFolder);
        $mockClient->shouldReceive('getLastError')->andReturn('');
        $mockClient->shouldReceive('disconnect')->once();

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('createClient')->with($mailbox)->andReturn($mockClient);
        $service->shouldReceive('processMessage')->twice();

        $result = $service->fetchEmails($mailbox);

        // Should have fetched 2 messages
        $this->assertEquals(2, $result['fetched']);
        $this->assertEquals(2, $result['created']);
        $this->assertEquals(0, $result['errors']);
    }
}
