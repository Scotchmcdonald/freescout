<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Mailbox;
use App\Services\ImapService;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\UnitTestCase;
use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\Folder;
use Webklex\PHPIMAP\Message;
use Webklex\PHPIMAP\Query\WhereQuery;
use Webklex\PHPIMAP\Support\MessageCollection;

class ImapServiceAddressParsingTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Log::shouldReceive('debug', 'info', 'warning', 'error')->byDefault();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function mailbox(): Mailbox
    {
        $mailbox = Mockery::mock(Mailbox::class)->makePartial();
        $mailbox->id = 1;
        $mailbox->name = 'Test Mailbox';
        $mailbox->in_server = 'imap.example.com';
        $mailbox->in_port = 993;
        $mailbox->in_username = 'test@example.com';
        $mailbox->in_password = encrypt('password');
        $mailbox->in_encryption = 1;
        $mailbox->in_imap_folders = null;

        return $mailbox;
    }

    public function test_fetch_emails_retries_on_charset_error_message(): void
    {
        $message = Mockery::mock(Message::class);
        $message->shouldReceive('getDate')->andReturn(now());
        $message->shouldReceive('getMessageId')->andReturn('<a@test>');
        $message->shouldReceive('setFlag')->with('Seen');

        $query1 = Mockery::mock(WhereQuery::class);
        $query1->shouldReceive('since')->andReturnSelf();
        $query1->shouldReceive('unseen')->andReturnSelf();
        $query1->shouldReceive('leaveUnread')->andReturnSelf();
        $query1->shouldReceive('get')->andThrow(new \Exception('The specified CHARSET is not supported'));

        $query2 = Mockery::mock(WhereQuery::class);
        $query2->shouldReceive('since')->andReturnSelf();
        $query2->shouldReceive('unseen')->andReturnSelf();
        $query2->shouldReceive('leaveUnread')->andReturnSelf();
        $query2->shouldReceive('get')->andReturn(new MessageCollection([$message]));

        $folder = Mockery::mock(Folder::class);
        $folder->shouldReceive('query')->andReturn($query1, $query2);

        $client = Mockery::mock(Client::class);
        $client->shouldReceive('connect');
        $client->shouldReceive('getFolder')->with('INBOX')->andReturn($folder);
        $client->shouldReceive('disconnect');

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('createClient')->andReturn($client);
        $service->shouldReceive('processMessage')->once();

        $result = $service->fetchEmails($this->mailbox());

        $this->assertSame(1, $result['fetched']);
        $this->assertSame(1, $result['created']);
        $this->assertSame(0, $result['errors']);
    }
}
