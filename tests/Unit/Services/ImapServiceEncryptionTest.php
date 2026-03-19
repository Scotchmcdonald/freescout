<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Mailbox;
use App\Services\ImapService;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\UnitTestCase;
use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\Exceptions\ConnectionFailedException;
use Webklex\PHPIMAP\Folder;
use Webklex\PHPIMAP\Query\WhereQuery;
use Webklex\PHPIMAP\Support\MessageCollection;

class ImapServiceEncryptionTest extends UnitTestCase
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

    private function mailbox(array $attrs = []): Mailbox
    {
        $m = new Mailbox([
            'name' => 'Test Mailbox',
            'in_server' => $attrs['in_server'] ?? 'imap.example.com',
            'in_port' => 993,
            'in_username' => 'test@example.com',
            'in_password' => encrypt('password'),
            'in_encryption' => $attrs['in_encryption'] ?? '2',
            'in_imap_folders' => $attrs['in_imap_folders'] ?? null,
        ]);
        $m->id = 1;

        return $m;
    }

    public function test_fetch_emails_handles_special_character_folder_paths(): void
    {
        $query = Mockery::mock(WhereQuery::class);
        $query->shouldReceive('since')->andReturnSelf();
        $query->shouldReceive('unseen')->andReturnSelf();
        $query->shouldReceive('leaveUnread')->andReturnSelf();
        $query->shouldReceive('get')->andReturn(new MessageCollection([]));

        $folder = Mockery::mock(Folder::class);
        $folder->shouldReceive('query')->andReturn($query);

        $client = Mockery::mock(Client::class);
        $client->shouldReceive('connect');
        $client->shouldReceive('getFolder')->with('[Gmail]/Sent Mail')->andReturn($folder);
        $client->shouldReceive('getFolder')->with('[Gmail]/All Mail')->andReturn($folder);
        $client->shouldReceive('disconnect');

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('createClient')->andReturn($client);

        $result = $service->fetchEmails($this->mailbox(['in_imap_folders' => '[Gmail]/Sent Mail,[Gmail]/All Mail']));

        $this->assertSame(0, $result['errors']);
    }

    public function test_connection_failure_returns_structured_error_for_string_encryption_value(): void
    {
        $mailbox = $this->mailbox(['in_encryption' => '2']);
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('connect')->andThrow(new ConnectionFailedException('TLS handshake failed'));

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('createClient')->andReturn($client);

        $result = $service->fetchEmails($mailbox);

        $this->assertSame(0, $result['fetched']);
        $this->assertSame(1, $result['errors']);
        $this->assertStringContainsString('Connection failed', $result['messages'][0]);
    }
}
