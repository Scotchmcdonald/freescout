<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Mailbox;
use App\Services\ImapService;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;
use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\Folder;
use Webklex\PHPIMAP\Message;
use Webklex\PHPIMAP\Query\WhereQuery;
use Webklex\PHPIMAP\Support\MessageCollection;

/**
 * Comprehensive tests for charset error handling and retry logic in ImapService::fetchEmails().
 * This covers an important code path for MS mailboxes that don't support charset.
 */
class ImapServiceCharsetRetryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock all logging to avoid expectations issues
        Log::shouldReceive('debug')->byDefault();
        Log::shouldReceive('info')->byDefault();
        Log::shouldReceive('warning')->byDefault();
        Log::shouldReceive('error')->byDefault();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Helper to create a mock mailbox
     */
    protected function createMockMailbox(): Mailbox
    {
        $mailbox = Mockery::mock(Mailbox::class)->makePartial();
        $mailbox->id = 1;
        $mailbox->name = 'Test Mailbox';
        $mailbox->in_server = 'imap.example.com';
        $mailbox->in_port = 993;
        $mailbox->in_username = 'test@example.com';
        $mailbox->in_password = encrypt('password');
        $mailbox->in_encryption = 1;
        $mailbox->in_imap_folders = null; // Will default to INBOX

        return $mailbox;
    }

    public function test_retries_without_charset_on_charset_error(): void
    {
        $mailbox = $this->createMockMailbox();

        $mockMessage = Mockery::mock(Message::class);
        $mockMessage->shouldReceive('getDate')->andReturn(now());
        $mockMessage->shouldReceive('getMessageId')->andReturn('<test@example.com>');
        $mockMessage->shouldReceive('setFlag')->with('Seen');

        // First query fails with charset error
        $mockQuery1 = Mockery::mock(WhereQuery::class);
        $mockQuery1->shouldReceive('since')->once()->andReturnSelf();
        $mockQuery1->shouldReceive('unseen')->once()->andReturnSelf();
        $mockQuery1->shouldReceive('leaveUnread')->once()->andReturnSelf();
        $mockQuery1->shouldReceive('get')->once()->andThrow(
            new \Exception('The specified charset is not supported')
        );

        // Second query succeeds with setCharset(null)
        $mockQuery2 = Mockery::mock(WhereQuery::class);
        $mockQuery2->shouldReceive('since')->once()->andReturnSelf();
        $mockQuery2->shouldReceive('unseen')->once()->andReturnSelf();
        $mockQuery2->shouldReceive('leaveUnread')->once()->andReturnSelf();
        $mockQuery2->shouldReceive('setCharset')->once()->with(null)->andReturnSelf();
        $mockQuery2->shouldReceive('get')->once()->andReturn(new MessageCollection([$mockMessage]));

        $mockFolder = Mockery::mock(Folder::class);
        $mockFolder->shouldReceive('query')->twice()->andReturn($mockQuery1, $mockQuery2);

        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('connect')->once();
        $mockClient->shouldReceive('getFolder')->with('INBOX')->once()->andReturn($mockFolder);
        $mockClient->shouldReceive('getLastError')->andReturn('The specified charset is not supported');
        $mockClient->shouldReceive('disconnect')->once();

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('createClient')->with($mailbox)->andReturn($mockClient);
        $service->shouldReceive('processMessage')->once();

        $result = $service->fetchEmails($mailbox);

        $this->assertEquals(1, $result['fetched']);
        $this->assertEquals(1, $result['created']);
        $this->assertEquals(0, $result['errors']);
    }

    public function test_retries_without_charset_when_get_last_error_contains_charset(): void
    {
        $mailbox = $this->createMockMailbox();

        $mockMessage = Mockery::mock(Message::class);
        $mockMessage->shouldReceive('getDate')->andReturn(now());
        $mockMessage->shouldReceive('getMessageId')->andReturn('<test@example.com>');
        $mockMessage->shouldReceive('setFlag')->with('Seen');

        // First query throws exception with charset in message (since method_exists fails on mocks)
        $mockQuery1 = Mockery::mock(WhereQuery::class);
        $mockQuery1->shouldReceive('since')->once()->andReturnSelf();
        $mockQuery1->shouldReceive('unseen')->once()->andReturnSelf();
        $mockQuery1->shouldReceive('leaveUnread')->once()->andReturnSelf();
        $mockQuery1->shouldReceive('get')->once()->andThrow(
            new \Exception('IMAP Error: The specified charset is not supported')
        );

        // Second query with setCharset(null) succeeds
        $mockQuery2 = Mockery::mock(WhereQuery::class);
        $mockQuery2->shouldReceive('since')->once()->andReturnSelf();
        $mockQuery2->shouldReceive('unseen')->once()->andReturnSelf();
        $mockQuery2->shouldReceive('leaveUnread')->once()->andReturnSelf();
        $mockQuery2->shouldReceive('setCharset')->once()->with(null)->andReturnSelf();
        $mockQuery2->shouldReceive('get')->once()->andReturn(new MessageCollection([$mockMessage]));

        $mockFolder = Mockery::mock(Folder::class);
        $mockFolder->shouldReceive('query')->twice()->andReturn($mockQuery1, $mockQuery2);

        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('connect')->once();
        $mockClient->shouldReceive('getFolder')->with('INBOX')->once()->andReturn($mockFolder);
        $mockClient->shouldReceive('disconnect')->once();

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('createClient')->with($mailbox)->andReturn($mockClient);
        $service->shouldReceive('processMessage')->once();

        $result = $service->fetchEmails($mailbox);

        // After retry, should have fetched 1 message
        $this->assertEquals(1, $result['fetched']);
    }

    public function test_does_not_retry_when_no_charset_error(): void
    {
        $mailbox = $this->createMockMailbox();

        $mockMessage = Mockery::mock(Message::class);
        $mockMessage->shouldReceive('getDate')->andReturn(now());
        $mockMessage->shouldReceive('getMessageId')->andReturn('<test@example.com>');
        $mockMessage->shouldReceive('setFlag')->with('Seen');

        // Query succeeds on first try
        $mockQuery = Mockery::mock(WhereQuery::class);
        $mockQuery->shouldReceive('since')->once()->andReturnSelf();
        $mockQuery->shouldReceive('unseen')->once()->andReturnSelf();
        $mockQuery->shouldReceive('leaveUnread')->once()->andReturnSelf();
        $mockQuery->shouldReceive('get')->once()->andReturn(new MessageCollection([$mockMessage]));

        $mockFolder = Mockery::mock(Folder::class);
        $mockFolder->shouldReceive('query')->once()->andReturn($mockQuery);

        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('connect')->once();
        $mockClient->shouldReceive('getFolder')->with('INBOX')->once()->andReturn($mockFolder);
        $mockClient->shouldReceive('getLastError')->andReturn('');
        $mockClient->shouldReceive('disconnect')->once();

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('createClient')->with($mailbox)->andReturn($mockClient);
        $service->shouldReceive('processMessage')->once();

        $result = $service->fetchEmails($mailbox);

        $this->assertEquals(1, $result['fetched']);
        $this->assertEquals(1, $result['created']);
    }

    public function test_charset_error_case_insensitive_matching(): void
    {
        $mailbox = $this->createMockMailbox();

        $mockMessage = Mockery::mock(Message::class);
        $mockMessage->shouldReceive('getDate')->andReturn(now());
        $mockMessage->shouldReceive('getMessageId')->andReturn('<test@example.com>');
        $mockMessage->shouldReceive('setFlag')->with('Seen');

        // First query fails with uppercase CHARSET
        $mockQuery1 = Mockery::mock(WhereQuery::class);
        $mockQuery1->shouldReceive('since')->once()->andReturnSelf();
        $mockQuery1->shouldReceive('unseen')->once()->andReturnSelf();
        $mockQuery1->shouldReceive('leaveUnread')->once()->andReturnSelf();
        $mockQuery1->shouldReceive('get')->once()->andThrow(
            new \Exception('The specified CHARSET is not supported')
        );

        // Second query succeeds
        $mockQuery2 = Mockery::mock(WhereQuery::class);
        $mockQuery2->shouldReceive('since')->once()->andReturnSelf();
        $mockQuery2->shouldReceive('unseen')->once()->andReturnSelf();
        $mockQuery2->shouldReceive('leaveUnread')->once()->andReturnSelf();
        $mockQuery2->shouldReceive('setCharset')->once()->with(null)->andReturnSelf();
        $mockQuery2->shouldReceive('get')->once()->andReturn(new MessageCollection([$mockMessage]));

        $mockFolder = Mockery::mock(Folder::class);
        $mockFolder->shouldReceive('query')->twice()->andReturn($mockQuery1, $mockQuery2);

        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('connect')->once();
        $mockClient->shouldReceive('getFolder')->with('INBOX')->once()->andReturn($mockFolder);
        $mockClient->shouldReceive('getLastError')->andReturn('The specified CHARSET is not supported');
        $mockClient->shouldReceive('disconnect')->once();

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('createClient')->with($mailbox)->andReturn($mockClient);
        $service->shouldReceive('processMessage')->once();

        $result = $service->fetchEmails($mailbox);

        $this->assertEquals(1, $result['fetched']);
    }

    public function test_charset_retry_with_multiple_folders(): void
    {
        $mailbox = $this->createMockMailbox();
        $mailbox->in_imap_folders = 'INBOX,Sent';

        $mockMessage = Mockery::mock(Message::class);
        $mockMessage->shouldReceive('getDate')->andReturn(now());
        $mockMessage->shouldReceive('getMessageId')->andReturn('<test@example.com>');
        $mockMessage->shouldReceive('setFlag')->with('Seen');

        // INBOX folder - fails then succeeds with retry
        $mockQuery1Inbox = Mockery::mock(WhereQuery::class);
        $mockQuery1Inbox->shouldReceive('since')->once()->andReturnSelf();
        $mockQuery1Inbox->shouldReceive('unseen')->once()->andReturnSelf();
        $mockQuery1Inbox->shouldReceive('leaveUnread')->once()->andReturnSelf();
        $mockQuery1Inbox->shouldReceive('get')->once()->andThrow(
            new \Exception('The specified charset is not supported')
        );

        $mockQuery2Inbox = Mockery::mock(WhereQuery::class);
        $mockQuery2Inbox->shouldReceive('since')->once()->andReturnSelf();
        $mockQuery2Inbox->shouldReceive('unseen')->once()->andReturnSelf();
        $mockQuery2Inbox->shouldReceive('leaveUnread')->once()->andReturnSelf();
        $mockQuery2Inbox->shouldReceive('setCharset')->once()->with(null)->andReturnSelf();
        $mockQuery2Inbox->shouldReceive('get')->once()->andReturn(new MessageCollection([$mockMessage]));

        $mockFolderInbox = Mockery::mock(Folder::class);
        $mockFolderInbox->shouldReceive('query')->twice()->andReturn($mockQuery1Inbox, $mockQuery2Inbox);

        // Sent folder - also fails then succeeds with retry
        $mockQuery1Sent = Mockery::mock(WhereQuery::class);
        $mockQuery1Sent->shouldReceive('since')->once()->andReturnSelf();
        $mockQuery1Sent->shouldReceive('unseen')->once()->andReturnSelf();
        $mockQuery1Sent->shouldReceive('leaveUnread')->once()->andReturnSelf();
        $mockQuery1Sent->shouldReceive('get')->once()->andThrow(
            new \Exception('The specified charset is not supported')
        );

        $mockQuery2Sent = Mockery::mock(WhereQuery::class);
        $mockQuery2Sent->shouldReceive('since')->once()->andReturnSelf();
        $mockQuery2Sent->shouldReceive('unseen')->once()->andReturnSelf();
        $mockQuery2Sent->shouldReceive('leaveUnread')->once()->andReturnSelf();
        $mockQuery2Sent->shouldReceive('setCharset')->once()->with(null)->andReturnSelf();
        $mockQuery2Sent->shouldReceive('get')->once()->andReturn(new MessageCollection([]));

        $mockFolderSent = Mockery::mock(Folder::class);
        $mockFolderSent->shouldReceive('query')->twice()->andReturn($mockQuery1Sent, $mockQuery2Sent);

        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('connect')->once();
        $mockClient->shouldReceive('getFolder')->with('INBOX')->once()->andReturn($mockFolderInbox);
        $mockClient->shouldReceive('getFolder')->with('Sent')->once()->andReturn($mockFolderSent);
        $mockClient->shouldReceive('getLastError')->andReturn('The specified charset is not supported');
        $mockClient->shouldReceive('disconnect')->once();

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('createClient')->with($mailbox)->andReturn($mockClient);
        $service->shouldReceive('processMessage')->once();

        $result = $service->fetchEmails($mailbox);

        $this->assertEquals(1, $result['fetched']);
        $this->assertEquals(1, $result['created']);
    }

    public function test_charset_error_without_method_exists_check(): void
    {
        $mailbox = $this->createMockMailbox();

        $mockMessage = Mockery::mock(Message::class);
        $mockMessage->shouldReceive('getDate')->andReturn(now());
        $mockMessage->shouldReceive('getMessageId')->andReturn('<test@example.com>');
        $mockMessage->shouldReceive('setFlag')->with('Seen');

        // First query fails with charset in exception message
        $mockQuery1 = Mockery::mock(WhereQuery::class);
        $mockQuery1->shouldReceive('since')->once()->andReturnSelf();
        $mockQuery1->shouldReceive('unseen')->once()->andReturnSelf();
        $mockQuery1->shouldReceive('leaveUnread')->once()->andReturnSelf();
        $mockQuery1->shouldReceive('get')->once()->andThrow(
            new \Exception('CHARSET not supported')
        );

        // Second query succeeds
        $mockQuery2 = Mockery::mock(WhereQuery::class);
        $mockQuery2->shouldReceive('since')->once()->andReturnSelf();
        $mockQuery2->shouldReceive('unseen')->once()->andReturnSelf();
        $mockQuery2->shouldReceive('leaveUnread')->once()->andReturnSelf();
        $mockQuery2->shouldReceive('setCharset')->once()->with(null)->andReturnSelf();
        $mockQuery2->shouldReceive('get')->once()->andReturn(new MessageCollection([$mockMessage]));

        $mockFolder = Mockery::mock(Folder::class);
        $mockFolder->shouldReceive('query')->twice()->andReturn($mockQuery1, $mockQuery2);

        // Client without getLastError method - exception message has 'charset' so retry happens
        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('connect')->once();
        $mockClient->shouldReceive('getFolder')->with('INBOX')->once()->andReturn($mockFolder);
        // Do NOT set up getLastError - method_exists check should handle this
        $mockClient->shouldReceive('disconnect')->once();

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('createClient')->with($mailbox)->andReturn($mockClient);
        $service->shouldReceive('processMessage')->once();

        $result = $service->fetchEmails($mailbox);

        // Message fetched after retry
        $this->assertEquals(1, $result['fetched']);
        $this->assertEquals(1, $result['created']);
    }
}
