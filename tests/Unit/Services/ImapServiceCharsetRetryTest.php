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

        $mockMessage = Mockery::mock(Message::class)->makePartial();
        $mockMessage->allows('getDate')->andReturn(now());
        $mockMessage->allows('getMessageId')->andReturn('<test@example.com>');
        $mockMessage->allows('setFlag');

        // First query fails with charset error
        $mockQuery1 = Mockery::mock(WhereQuery::class);
        $mockQuery1->allows('since')->andReturnSelf();
        $mockQuery1->allows('unseen')->andReturnSelf();
        $mockQuery1->allows('leaveUnread')->andReturnSelf();
        $mockQuery1->allows('get')->andThrow(
            new \Exception('The specified charset is not supported')
        );

        // Second query succeeds (retry without charset)
        $mockQuery2 = Mockery::mock(WhereQuery::class);
        $mockQuery2->allows('since')->andReturnSelf();
        $mockQuery2->allows('unseen')->andReturnSelf();
        $mockQuery2->allows('leaveUnread')->andReturnSelf();
        $mockQuery2->allows('get')->andReturn(new MessageCollection([$mockMessage]));

        $mockFolder = Mockery::mock(Folder::class);
        $mockFolder->allows('query')->andReturn($mockQuery1, $mockQuery2);

        $mockClient = Mockery::mock(Client::class);
        $mockClient->allows('connect');
        $mockClient->allows('getFolder')->with('INBOX')->andReturn($mockFolder);
        $mockClient->allows('getLastError')->andReturn('The specified charset is not supported');
        $mockClient->allows('disconnect');

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->allows('createClient')->with($mailbox)->andReturn($mockClient);
        $service->allows('processMessage');

        $result = $service->fetchEmails($mailbox);

        // Verify behavior: charset error is handled and message is fetched
        $this->assertEquals(1, $result['fetched']);
        $this->assertEquals(1, $result['created']);
        $this->assertEquals(0, $result['errors']);
    }

    public function test_retries_without_charset_when_get_last_error_contains_charset(): void
    {
        $mailbox = $this->createMockMailbox();

        $mockMessage = Mockery::mock(Message::class)->makePartial();
        $mockMessage->allows('getDate')->andReturn(now());
        $mockMessage->allows('getMessageId')->andReturn('<test@example.com>');
        $mockMessage->allows('setFlag');

        // First query throws exception with charset in message
        $mockQuery1 = Mockery::mock(WhereQuery::class);
        $mockQuery1->allows('since')->andReturnSelf();
        $mockQuery1->allows('unseen')->andReturnSelf();
        $mockQuery1->allows('leaveUnread')->andReturnSelf();
        $mockQuery1->allows('get')->andThrow(
            new \Exception('IMAP Error: The specified charset is not supported')
        );

        // Second query succeeds (retry without charset)
        $mockQuery2 = Mockery::mock(WhereQuery::class);
        $mockQuery2->allows('since')->andReturnSelf();
        $mockQuery2->allows('unseen')->andReturnSelf();
        $mockQuery2->allows('leaveUnread')->andReturnSelf();
        $mockQuery2->allows('get')->andReturn(new MessageCollection([$mockMessage]));

        $mockFolder = Mockery::mock(Folder::class);
        $mockFolder->allows('query')->andReturn($mockQuery1, $mockQuery2);

        $mockClient = Mockery::mock(Client::class);
        $mockClient->allows('connect');
        $mockClient->allows('getFolder')->with('INBOX')->andReturn($mockFolder);
        $mockClient->allows('disconnect');

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->allows('createClient')->with($mailbox)->andReturn($mockClient);
        $service->allows('processMessage');

        $result = $service->fetchEmails($mailbox);

        // Verify behavior: message fetched after charset error retry
        $this->assertEquals(1, $result['fetched']);
    }

    public function test_does_not_retry_when_no_charset_error(): void
    {
        $mailbox = $this->createMockMailbox();

        $mockMessage = Mockery::mock(Message::class)->makePartial();
        $mockMessage->allows('getDate')->andReturn(now());
        $mockMessage->allows('getMessageId')->andReturn('<test@example.com>');
        $mockMessage->allows('setFlag');

        // Query succeeds on first try
        $mockQuery = Mockery::mock(WhereQuery::class);
        $mockQuery->allows('since')->andReturnSelf();
        $mockQuery->allows('unseen')->andReturnSelf();
        $mockQuery->allows('leaveUnread')->andReturnSelf();
        $mockQuery->allows('get')->andReturn(new MessageCollection([$mockMessage]));

        $mockFolder = Mockery::mock(Folder::class);
        $mockFolder->allows('query')->andReturn($mockQuery);

        $mockClient = Mockery::mock(Client::class);
        $mockClient->allows('connect');
        $mockClient->allows('getFolder')->with('INBOX')->andReturn($mockFolder);
        $mockClient->allows('getLastError')->andReturn('');
        $mockClient->allows('disconnect');

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->allows('createClient')->with($mailbox)->andReturn($mockClient);
        $service->allows('processMessage');

        $result = $service->fetchEmails($mailbox);

        // Verify behavior: message fetched successfully without retry
        $this->assertEquals(1, $result['fetched']);
        $this->assertEquals(1, $result['created']);
    }

    public function test_charset_error_case_insensitive_matching(): void
    {
        $mailbox = $this->createMockMailbox();

        $mockMessage = Mockery::mock(Message::class)->makePartial();
        $mockMessage->allows('getDate')->andReturn(now());
        $mockMessage->allows('getMessageId')->andReturn('<test@example.com>');
        $mockMessage->allows('setFlag');

        // First query fails with uppercase CHARSET
        $mockQuery1 = Mockery::mock(WhereQuery::class);
        $mockQuery1->allows('since')->andReturnSelf();
        $mockQuery1->allows('unseen')->andReturnSelf();
        $mockQuery1->allows('leaveUnread')->andReturnSelf();
        $mockQuery1->allows('get')->andThrow(
            new \Exception('The specified CHARSET is not supported')
        );

        // Second query succeeds (retry)
        $mockQuery2 = Mockery::mock(WhereQuery::class);
        $mockQuery2->allows('since')->andReturnSelf();
        $mockQuery2->allows('unseen')->andReturnSelf();
        $mockQuery2->allows('leaveUnread')->andReturnSelf();
        $mockQuery2->allows('get')->andReturn(new MessageCollection([$mockMessage]));

        $mockFolder = Mockery::mock(Folder::class);
        $mockFolder->allows('query')->andReturn($mockQuery1, $mockQuery2);

        $mockClient = Mockery::mock(Client::class);
        $mockClient->allows('connect');
        $mockClient->allows('getFolder')->with('INBOX')->andReturn($mockFolder);
        $mockClient->allows('getLastError')->andReturn('The specified CHARSET is not supported');
        $mockClient->allows('disconnect');

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->allows('createClient')->with($mailbox)->andReturn($mockClient);
        $service->allows('processMessage');

        $result = $service->fetchEmails($mailbox);

        // Verify behavior: charset error (uppercase) is handled case-insensitively
        $this->assertEquals(1, $result['fetched']);
    }

    public function test_charset_retry_with_multiple_folders(): void
    {
        $mailbox = $this->createMockMailbox();
        $mailbox->in_imap_folders = 'INBOX,Sent';

        $mockMessage = Mockery::mock(Message::class)->makePartial();
        $mockMessage->allows('getDate')->andReturn(now());
        $mockMessage->allows('getMessageId')->andReturn('<test@example.com>');
        $mockMessage->allows('setFlag');

        // INBOX folder - fails then succeeds with retry
        $mockQuery1Inbox = Mockery::mock(WhereQuery::class);
        $mockQuery1Inbox->allows('since')->andReturnSelf();
        $mockQuery1Inbox->allows('unseen')->andReturnSelf();
        $mockQuery1Inbox->allows('leaveUnread')->andReturnSelf();
        $mockQuery1Inbox->allows('get')->andThrow(
            new \Exception('The specified charset is not supported')
        );

        $mockQuery2Inbox = Mockery::mock(WhereQuery::class);
        $mockQuery2Inbox->allows('since')->andReturnSelf();
        $mockQuery2Inbox->allows('unseen')->andReturnSelf();
        $mockQuery2Inbox->allows('leaveUnread')->andReturnSelf();
        $mockQuery2Inbox->allows('get')->andReturn(new MessageCollection([$mockMessage]));

        $mockFolderInbox = Mockery::mock(Folder::class);
        $mockFolderInbox->allows('query')->andReturn($mockQuery1Inbox, $mockQuery2Inbox);

        // Sent folder - also fails then succeeds with retry
        $mockQuery1Sent = Mockery::mock(WhereQuery::class);
        $mockQuery1Sent->allows('since')->andReturnSelf();
        $mockQuery1Sent->allows('unseen')->andReturnSelf();
        $mockQuery1Sent->allows('leaveUnread')->andReturnSelf();
        $mockQuery1Sent->allows('get')->andThrow(
            new \Exception('The specified charset is not supported')
        );

        $mockQuery2Sent = Mockery::mock(WhereQuery::class);
        $mockQuery2Sent->allows('since')->andReturnSelf();
        $mockQuery2Sent->allows('unseen')->andReturnSelf();
        $mockQuery2Sent->allows('leaveUnread')->andReturnSelf();
        $mockQuery2Sent->allows('get')->andReturn(new MessageCollection([]));

        $mockFolderSent = Mockery::mock(Folder::class);
        $mockFolderSent->allows('query')->andReturn($mockQuery1Sent, $mockQuery2Sent);

        $mockClient = Mockery::mock(Client::class);
        $mockClient->allows('connect');
        $mockClient->allows('getFolder')->with('INBOX')->andReturn($mockFolderInbox);
        $mockClient->allows('getFolder')->with('Sent')->andReturn($mockFolderSent);
        $mockClient->allows('getLastError')->andReturn('The specified charset is not supported');
        $mockClient->allows('disconnect');

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->allows('createClient')->with($mailbox)->andReturn($mockClient);
        $service->allows('processMessage');

        $result = $service->fetchEmails($mailbox);

        // Verify behavior: charset retry works across multiple folders
        $this->assertEquals(1, $result['fetched']);
        $this->assertEquals(1, $result['created']);
    }

    public function test_charset_error_without_method_exists_check(): void
    {
        $mailbox = $this->createMockMailbox();

        $mockMessage = Mockery::mock(Message::class)->makePartial();
        $mockMessage->allows('getDate')->andReturn(now());
        $mockMessage->allows('getMessageId')->andReturn('<test@example.com>');
        $mockMessage->allows('setFlag');

        // First query fails with charset in exception message
        $mockQuery1 = Mockery::mock(WhereQuery::class);
        $mockQuery1->allows('since')->andReturnSelf();
        $mockQuery1->allows('unseen')->andReturnSelf();
        $mockQuery1->allows('leaveUnread')->andReturnSelf();
        $mockQuery1->allows('get')->andThrow(
            new \Exception('CHARSET not supported')
        );

        // Second query succeeds (retry)
        $mockQuery2 = Mockery::mock(WhereQuery::class);
        $mockQuery2->allows('since')->andReturnSelf();
        $mockQuery2->allows('unseen')->andReturnSelf();
        $mockQuery2->allows('leaveUnread')->andReturnSelf();
        $mockQuery2->allows('get')->andReturn(new MessageCollection([$mockMessage]));

        $mockFolder = Mockery::mock(Folder::class);
        $mockFolder->allows('query')->andReturn($mockQuery1, $mockQuery2);

        // Client without getLastError method - exception message has 'charset' so retry happens
        $mockClient = Mockery::mock(Client::class);
        $mockClient->allows('connect');
        $mockClient->allows('getFolder')->with('INBOX')->andReturn($mockFolder);
        // Do NOT set up getLastError - method_exists check should handle this
        $mockClient->allows('disconnect');

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->allows('createClient')->with($mailbox)->andReturn($mockClient);
        $service->allows('processMessage');

        $result = $service->fetchEmails($mailbox);

        // Verify behavior: message fetched after charset retry (even without getLastError)
        $this->assertEquals(1, $result['fetched']);
        $this->assertEquals(1, $result['created']);
    }
}
