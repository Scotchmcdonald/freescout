<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Mailbox;
use App\Services\ImapService;
use Mockery;
use Tests\TestCase;
use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\Folder;
use Webklex\PHPIMAP\Message;
use Webklex\PHPIMAP\Query\WhereQuery;
use Webklex\PHPIMAP\Support\MessageCollection;
use Webklex\PHPIMAP\Exceptions\ConnectionFailedException;

/**
 * Comprehensive tests for ImapService::testConnection() method.
 * This method currently has ~21% coverage and needs additional testing.
 */
class ImapServiceTestConnectionTest extends TestCase
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
     * Helper to create a mock mailbox
     */
    protected function createMockMailbox(): Mailbox
    {
        $mailbox = Mockery::mock(Mailbox::class)->makePartial();
        $mailbox->id = 1;
        $mailbox->in_server = 'imap.example.com';
        $mailbox->in_port = 993;
        $mailbox->in_username = 'test@example.com';
        $mailbox->in_password = encrypt('password');
        $mailbox->in_encryption = 1;
        $mailbox->in_validate_cert = true;

        return $mailbox;
    }

    public function test_connection_succeeds_with_inbox_access(): void
    {
        $mailbox = $this->createMockMailbox();

        $mockMessage1 = Mockery::mock(Message::class);
        $mockMessage1->shouldReceive('hasFlag')->with('Seen')->andReturn(false);
        
        $mockMessage2 = Mockery::mock(Message::class);
        $mockMessage2->shouldReceive('hasFlag')->with('Seen')->andReturn(true);

        $messageCollection = new MessageCollection([$mockMessage1, $mockMessage2]);

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
        $this->assertStringContainsString('2 messages', $result['message']);
        $this->assertStringContainsString('1 unread', $result['message']);
    }

    public function test_connection_succeeds_with_no_messages(): void
    {
        $mailbox = $this->createMockMailbox();

        $messageCollection = new MessageCollection([]);

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
        $this->assertStringContainsString('0 messages', $result['message']);
        $this->assertStringContainsString('0 unread', $result['message']);
    }

    public function test_connection_succeeds_with_all_messages_read(): void
    {
        $mailbox = $this->createMockMailbox();

        $mockMessage1 = Mockery::mock(Message::class);
        $mockMessage1->shouldReceive('hasFlag')->with('Seen')->andReturn(true);
        
        $mockMessage2 = Mockery::mock(Message::class);
        $mockMessage2->shouldReceive('hasFlag')->with('Seen')->andReturn(true);
        
        $mockMessage3 = Mockery::mock(Message::class);
        $mockMessage3->shouldReceive('hasFlag')->with('Seen')->andReturn(true);

        $messageCollection = new MessageCollection([$mockMessage1, $mockMessage2, $mockMessage3]);

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
        $this->assertStringContainsString('3 messages', $result['message']);
        $this->assertStringContainsString('0 unread', $result['message']);
    }

    public function test_connection_handles_charset_error_and_retries(): void
    {
        $mailbox = $this->createMockMailbox();

        $mockMessage = Mockery::mock(Message::class)->makePartial();
        $mockMessage->allows('hasFlag')->with('Seen')->andReturn(false);

        $messageCollection = new MessageCollection([$mockMessage]);

        // First query fails with charset error
        $mockQuery1 = Mockery::mock(WhereQuery::class);
        $mockQuery1->allows('since')->andReturnSelf();
        $mockQuery1->allows('leaveUnread')->andReturnSelf();
        $mockQuery1->allows('get')->andThrow(new \Exception('The specified charset is not supported'));

        // Second query succeeds (retry without charset)
        $mockQuery2 = Mockery::mock(WhereQuery::class);
        $mockQuery2->allows('since')->andReturnSelf();
        $mockQuery2->allows('leaveUnread')->andReturnSelf();
        $mockQuery2->allows('get')->andReturn($messageCollection);

        $mockFolder = Mockery::mock(Folder::class);
        $mockFolder->allows('query')->andReturn($mockQuery1, $mockQuery2);

        $mockClient = Mockery::mock(Client::class);
        $mockClient->allows('connect');
        $mockClient->allows('getFolder')->with('INBOX')->andReturn($mockFolder);
        $mockClient->allows('disconnect');

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->allows('createClient')->with($mailbox)->andReturn($mockClient);

        $result = $service->testConnection($mailbox);

        // Verify behavior: connection succeeds after charset retry
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('1 messages', $result['message']);
        $this->assertStringContainsString('1 unread', $result['message']);
    }

    public function test_connection_fails_when_inbox_not_found(): void
    {
        $mailbox = $this->createMockMailbox();

        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('connect')->once();
        $mockClient->shouldReceive('getFolder')->with('INBOX')->once()->andReturn(null);

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('createClient')->with($mailbox)->andReturn($mockClient);

        $result = $service->testConnection($mailbox);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Could not access INBOX folder', $result['message']);
    }

    public function test_connection_handles_connection_failure(): void
    {
        $mailbox = $this->createMockMailbox();

        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('connect')
            ->once()
            ->andThrow(new ConnectionFailedException('Connection timeout'));

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('createClient')->with($mailbox)->andReturn($mockClient);

        $result = $service->testConnection($mailbox);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Connection failed', $result['message']);
        $this->assertStringContainsString('Connection timeout', $result['message']);
    }

    public function test_connection_handles_general_exception(): void
    {
        $mailbox = $this->createMockMailbox();

        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('connect')->once();
        $mockClient->shouldReceive('getFolder')
            ->with('INBOX')
            ->once()
            ->andThrow(new \Exception('Unexpected error'));

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('createClient')->with($mailbox)->andReturn($mockClient);

        $result = $service->testConnection($mailbox);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Error:', $result['message']);
        $this->assertStringContainsString('Unexpected error', $result['message']);
    }

    public function test_connection_result_structure_always_has_required_keys(): void
    {
        $mailbox = $this->createMockMailbox();

        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('connect')
            ->once()
            ->andThrow(new \Exception('Test error'));

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('createClient')->with($mailbox)->andReturn($mockClient);

        $result = $service->testConnection($mailbox);

        // Even on error, result should have proper structure
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertIsBool($result['success']);
        $this->assertIsString($result['message']);
    }

    public function test_connection_charset_error_with_uppercase(): void
    {
        $mailbox = $this->createMockMailbox();

        $mockMessage = Mockery::mock(Message::class)->makePartial();
        $mockMessage->allows('hasFlag')->with('Seen')->andReturn(true);

        $messageCollection = new MessageCollection([$mockMessage]);

        // First query fails with CHARSET in uppercase
        $mockQuery1 = Mockery::mock(WhereQuery::class);
        $mockQuery1->allows('since')->andReturnSelf();
        $mockQuery1->allows('leaveUnread')->andReturnSelf();
        $mockQuery1->allows('get')->andThrow(new \Exception('The specified CHARSET is not supported'));

        // Second query succeeds (retry)
        $mockQuery2 = Mockery::mock(WhereQuery::class);
        $mockQuery2->allows('since')->andReturnSelf();
        $mockQuery2->allows('leaveUnread')->andReturnSelf();
        $mockQuery2->allows('get')->andReturn($messageCollection);

        $mockFolder = Mockery::mock(Folder::class);
        $mockFolder->allows('query')->andReturn($mockQuery1, $mockQuery2);

        $mockClient = Mockery::mock(Client::class);
        $mockClient->allows('connect');
        $mockClient->allows('getFolder')->with('INBOX')->andReturn($mockFolder);
        $mockClient->allows('disconnect');

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->allows('createClient')->with($mailbox)->andReturn($mockClient);

        $result = $service->testConnection($mailbox);

        // Verify behavior: uppercase CHARSET error is handled case-insensitively
        $this->assertTrue($result['success']);
    }

    public function test_connection_charset_error_rethrows_on_second_failure(): void
    {
        $mailbox = $this->createMockMailbox();

        // First query fails with charset error
        $mockQuery1 = Mockery::mock(WhereQuery::class);
        $mockQuery1->allows('since')->andReturnSelf();
        $mockQuery1->allows('leaveUnread')->andReturnSelf();
        $mockQuery1->allows('get')->andThrow(new \Exception('The specified charset is not supported'));

        // Second query also fails (different error)
        $mockQuery2 = Mockery::mock(WhereQuery::class);
        $mockQuery2->allows('since')->andReturnSelf();
        $mockQuery2->allows('leaveUnread')->andReturnSelf();
        $mockQuery2->allows('get')->andThrow(new \Exception('Network error'));

        $mockFolder = Mockery::mock(Folder::class);
        $mockFolder->allows('query')->andReturn($mockQuery1, $mockQuery2);

        $mockClient = Mockery::mock(Client::class);
        $mockClient->allows('connect');
        $mockClient->allows('getFolder')->with('INBOX')->andReturn($mockFolder);

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->allows('createClient')->with($mailbox)->andReturn($mockClient);

        $result = $service->testConnection($mailbox);

        // Verify behavior: second failure is reported correctly
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Network error', $result['message']);
    }

    public function test_connection_succeeds_with_large_message_count(): void
    {
        $mailbox = $this->createMockMailbox();

        $messages = [];
        for ($i = 0; $i < 100; $i++) {
            $mockMessage = Mockery::mock(Message::class);
            $mockMessage->shouldReceive('hasFlag')->with('Seen')->andReturn($i % 2 === 0);
            $messages[] = $mockMessage;
        }

        $messageCollection = new MessageCollection($messages);

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
        $this->assertStringContainsString('100 messages', $result['message']);
        $this->assertStringContainsString('50 unread', $result['message']);
    }

    public function test_connection_non_charset_exception_not_retried(): void
    {
        $mailbox = $this->createMockMailbox();

        // Query fails with non-charset error
        $mockQuery = Mockery::mock(WhereQuery::class);
        $mockQuery->shouldReceive('since')->once()->andReturnSelf();
        $mockQuery->shouldReceive('leaveUnread')->once()->andReturnSelf();
        $mockQuery->shouldReceive('get')->once()->andThrow(new \Exception('Invalid search criteria'));

        $mockFolder = Mockery::mock(Folder::class);
        $mockFolder->shouldReceive('query')->once()->andReturn($mockQuery);

        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('connect')->once();
        $mockClient->shouldReceive('getFolder')->with('INBOX')->once()->andReturn($mockFolder);

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('createClient')->with($mailbox)->andReturn($mockClient);

        $result = $service->testConnection($mailbox);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Invalid search criteria', $result['message']);
    }
}
