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
 * Basic tests for ImapService::fetchEmails() to improve coverage.
 * These tests focus on core functionality without complex mocking scenarios.
 */
class ImapServiceFetchEmailsBasicTest extends TestCase
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

    public function test_successful_fetch_with_single_inbox_folder(): void
    {
        $mailbox = Mockery::mock(Mailbox::class)->makePartial();
        $mailbox->id = 1;
        $mailbox->in_server = 'imap.example.com';
        $mailbox->in_port = 993;
        $mailbox->in_username = 'user@example.com';
        $mailbox->in_password = encrypt('password');
        $mailbox->in_encryption = 1;
        $mailbox->imap_folder_path = null; // Default to INBOX
        $mailbox->fetch_since = null;

        $mockClient = Mockery::mock(Client::class);
        $mockFolder = Mockery::mock(Folder::class);
        $mockQuery = Mockery::mock(WhereQuery::class);
        $mockMessage = Mockery::mock(Message::class);

        $mockClient->shouldReceive('connect')->once();
        $mockClient->shouldReceive('disconnect')->once();
        $mockClient->shouldReceive('getLastError')->andReturn('');
        $mockClient->shouldReceive('getFolder')->with('INBOX')->once()->andReturn($mockFolder);

        $mockFolder->shouldReceive('query')->once()->andReturn($mockQuery);
        $mockQuery->shouldReceive('since')->once()->andReturnSelf();
        $mockQuery->shouldReceive('unseen')->once()->andReturnSelf();
        $mockQuery->shouldReceive('leaveUnread')->once()->andReturnSelf();
        $mockQuery->shouldReceive('get')->once()->andReturn(new MessageCollection([$mockMessage]));

        $mockMessage->shouldReceive('getDate')->andReturn(now());
        $mockMessage->shouldReceive('getMessageId')->andReturn('<test@example.com>');
        $mockMessage->shouldReceive('setFlag')->with('Seen')->once();
        $mockMessage->shouldReceive('getFrom')->andReturn([]);
        $mockMessage->shouldReceive('getTo')->andReturn([]);
        $mockMessage->shouldReceive('getCc')->andReturn([]);
        $mockMessage->shouldReceive('getBcc')->andReturn([]);
        $mockMessage->shouldReceive('getSubject')->andReturn('Test Subject');
        $mockMessage->shouldReceive('getTextBody')->andReturn('Test body');
        $mockMessage->shouldReceive('getHTMLBody')->andReturn('');
        $mockMessage->shouldReceive('hasHTMLBody')->andReturn(false);
        $mockMessage->shouldReceive('getInReplyTo')->andReturn('');
        $mockMessage->shouldReceive('getReferences')->andReturn([]);
        $mockMessage->shouldReceive('getHeader')->andReturn(null);
        $mockMessage->shouldReceive('getAttributes')->andReturn([]);
        $mockMessage->shouldReceive('hasAttachments')->andReturn(false);

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('createClient')->with($mailbox)->andReturn($mockClient);
        $service->shouldReceive('processMessage')->once();

        $result = $service->fetchEmails($mailbox);

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['fetched']);
        $this->assertEquals(1, $result['created']);
    }

    public function test_handles_message_processing_error(): void
    {
        $mailbox = Mockery::mock(Mailbox::class)->makePartial();
        $mailbox->id = 1;
        $mailbox->in_server = 'imap.example.com';
        $mailbox->in_port = 993;
        $mailbox->in_username = 'user@example.com';
        $mailbox->in_password = encrypt('password');
        $mailbox->in_encryption = 1;
        $mailbox->imap_folder_path = 'INBOX';
        $mailbox->fetch_since = null;

        $mockClient = Mockery::mock(Client::class);
        $mockFolder = Mockery::mock(Folder::class);
        $mockQuery = Mockery::mock(WhereQuery::class);
        $mockMessage = Mockery::mock(Message::class);

        $mockClient->shouldReceive('connect')->once();
        $mockClient->shouldReceive('disconnect')->once();
        $mockClient->shouldReceive('getLastError')->andReturn('');
        $mockClient->shouldReceive('getFolder')->with('INBOX')->andReturn($mockFolder);

        $mockFolder->shouldReceive('query')->andReturn($mockQuery);
        $mockQuery->shouldReceive('since')->andReturnSelf();
        $mockQuery->shouldReceive('unseen')->andReturnSelf();
        $mockQuery->shouldReceive('leaveUnread')->andReturnSelf();
        $mockQuery->shouldReceive('get')->andReturn(new MessageCollection([$mockMessage]));

        $mockMessage->shouldReceive('getDate')->andReturn(now());
        $mockMessage->shouldReceive('getMessageId')->andReturn('<test@example.com>');

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('createClient')->andReturn($mockClient);
        $service->shouldReceive('processMessage')->once()->andThrow(new \Exception('Processing error'));

        $result = $service->fetchEmails($mailbox);

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['fetched']);
        $this->assertEquals(0, $result['created']);
    }

    public function test_sorts_messages_by_date_chronologically(): void
    {
        $mailbox = Mockery::mock(Mailbox::class)->makePartial();
        $mailbox->id = 1;
        $mailbox->in_server = 'imap.example.com';
        $mailbox->in_port = 993;
        $mailbox->in_username = 'user@example.com';
        $mailbox->in_password = encrypt('password');
        $mailbox->in_encryption = 1;
        $mailbox->imap_folder_path = 'INBOX';
        $mailbox->fetch_since = null;

        $mockClient = Mockery::mock(Client::class);
        $mockFolder = Mockery::mock(Folder::class);
        $mockQuery = Mockery::mock(WhereQuery::class);

        $mockMessage1 = Mockery::mock(Message::class);
        $mockMessage2 = Mockery::mock(Message::class);
        $mockMessage3 = Mockery::mock(Message::class);

        $date1 = now()->subHours(3);
        $date2 = now()->subHours(2);
        $date3 = now()->subHours(1);

        $mockMessage1->shouldReceive('getDate')->andReturn($date2);
        $mockMessage1->shouldReceive('getMessageId')->andReturn('<msg1@example.com>');
        $mockMessage1->shouldReceive('setFlag')->with('Seen');
        $mockMessage1->shouldReceive('hasAttachments')->andReturn(false);

        $mockMessage2->shouldReceive('getDate')->andReturn($date1);
        $mockMessage2->shouldReceive('getMessageId')->andReturn('<msg2@example.com>');
        $mockMessage2->shouldReceive('setFlag')->with('Seen');
        $mockMessage2->shouldReceive('hasAttachments')->andReturn(false);

        $mockMessage3->shouldReceive('getDate')->andReturn($date3);
        $mockMessage3->shouldReceive('getMessageId')->andReturn('<msg3@example.com>');
        $mockMessage3->shouldReceive('setFlag')->with('Seen');
        $mockMessage3->shouldReceive('hasAttachments')->andReturn(false);

        $mockClient->shouldReceive('connect')->once();
        $mockClient->shouldReceive('disconnect')->once();
        $mockClient->shouldReceive('getLastError')->andReturn('');
        $mockClient->shouldReceive('getFolder')->with('INBOX')->andReturn($mockFolder);

        $mockFolder->shouldReceive('query')->andReturn($mockQuery);
        $mockQuery->shouldReceive('since')->andReturnSelf();
        $mockQuery->shouldReceive('unseen')->andReturnSelf();
        $mockQuery->shouldReceive('leaveUnread')->andReturnSelf();
        $mockQuery->shouldReceive('get')->andReturn(new MessageCollection([$mockMessage1, $mockMessage2, $mockMessage3]));

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('createClient')->andReturn($mockClient);

        $processedOrder = [];
        $service->shouldReceive('processMessage')->andReturnUsing(function ($mailbox, $message) use (&$processedOrder) {
            $processedOrder[] = $message->getMessageId();
        });

        $service->fetchEmails($mailbox);

        // Verify messages were processed in chronological order (oldest first)
        $this->assertEquals('<msg2@example.com>', $processedOrder[0]);
        $this->assertEquals('<msg1@example.com>', $processedOrder[1]);
        $this->assertEquals('<msg3@example.com>', $processedOrder[2]);
    }

    public function test_marks_messages_as_seen_after_processing(): void
    {
        $mailbox = Mockery::mock(Mailbox::class)->makePartial();
        $mailbox->id = 1;
        $mailbox->in_server = 'imap.example.com';
        $mailbox->in_port = 993;
        $mailbox->in_username = 'user@example.com';
        $mailbox->in_password = encrypt('password');
        $mailbox->in_encryption = 1;
        $mailbox->imap_folder_path = 'INBOX';
        $mailbox->fetch_since = null;

        $mockClient = Mockery::mock(Client::class);
        $mockFolder = Mockery::mock(Folder::class);
        $mockQuery = Mockery::mock(WhereQuery::class);
        $mockMessage = Mockery::mock(Message::class);

        $mockMessage->shouldReceive('getDate')->andReturn(now());
        $mockMessage->shouldReceive('getMessageId')->andReturn('<test@example.com>');
        $mockMessage->shouldReceive('setFlag')->once()->with('Seen');
        $mockMessage->shouldReceive('hasAttachments')->andReturn(false);

        $mockClient->shouldReceive('connect')->once();
        $mockClient->shouldReceive('disconnect')->once();
        $mockClient->shouldReceive('getLastError')->andReturn('');
        $mockClient->shouldReceive('getFolder')->with('INBOX')->andReturn($mockFolder);

        $mockFolder->shouldReceive('query')->andReturn($mockQuery);
        $mockQuery->shouldReceive('since')->andReturnSelf();
        $mockQuery->shouldReceive('unseen')->andReturnSelf();
        $mockQuery->shouldReceive('leaveUnread')->andReturnSelf();
        $mockQuery->shouldReceive('get')->andReturn(new MessageCollection([$mockMessage]));

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('createClient')->andReturn($mockClient);
        $service->shouldReceive('processMessage')->once();

        $service->fetchEmails($mailbox);

        // setFlag('Seen') expectation verified by Mockery
        $this->expectNotToPerformAssertions();
    }

    public function test_handles_general_exception_during_fetch(): void
    {
        $mailbox = Mockery::mock(Mailbox::class)->makePartial();
        $mailbox->id = 1;
        $mailbox->in_server = 'imap.example.com';
        $mailbox->in_port = 993;
        $mailbox->in_username = 'user@example.com';
        $mailbox->in_password = encrypt('password');
        $mailbox->in_encryption = 1;
        $mailbox->imap_folder_path = 'INBOX';
        $mailbox->fetch_since = null;

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('createClient')->with($mailbox)->andThrow(new \Exception('General error'));

        $result = $service->fetchEmails($mailbox);

        $this->assertIsArray($result);
        $this->assertEquals(0, $result['fetched']);
        $this->assertEquals(0, $result['created']);
    }

    protected function tearDown(): void
    {
        try {
            Mockery::close();
        } finally {
            parent::tearDown();
        }
    }
}
