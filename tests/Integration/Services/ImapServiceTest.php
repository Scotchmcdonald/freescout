<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use App\Models\Mailbox;
use App\Services\ImapService;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\IntegrationTestCase;
use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\Exceptions\ConnectionFailedException;
use Webklex\PHPIMAP\Folder;
use Webklex\PHPIMAP\Message;
use Webklex\PHPIMAP\Query\WhereQuery;
use Webklex\PHPIMAP\Support\FolderCollection;
use Webklex\PHPIMAP\Support\MessageCollection;

/**
 * Public API tests for ImapService covering fetchEmails, getFolders and testConnection.
 * All tests use a mocked IMAP client — no real network connections are made.
 */
class ImapServiceTest extends IntegrationTestCase
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

    // ── helpers ──────────────────────────────────────────────────────────────

    private function mockMailbox(array $attrs = []): Mailbox
    {
        $m = new Mailbox([
            'name' => $attrs['name'] ?? 'Test',
            'in_server' => $attrs['in_server'] ?? 'imap.example.com',
            'in_port' => $attrs['in_port'] ?? 993,
            'in_username' => $attrs['in_username'] ?? 'user@example.com',
            'in_password' => $attrs['in_password'] ?? encrypt('pass'),
            'in_encryption' => $attrs['in_encryption'] ?? 1,
            'in_imap_folders' => $attrs['in_imap_folders'] ?? null,
            'in_validate_cert' => $attrs['in_validate_cert'] ?? true,
        ]);
        $m->id = $attrs['id'] ?? 1;

        return $m;
    }

    private function mockService(Client $client): ImapService
    {
        $svc = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $svc->shouldReceive('createClient')->andReturn($client);

        return $svc;
    }

    private function mockConnectedClient(array $folderNames = ['INBOX'], int $msgCount = 0): Client
    {
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('connect');
        $client->shouldReceive('disconnect')->byDefault();
        $client->shouldReceive('getLastError')->andReturn('')->byDefault();

        foreach ($folderNames as $name) {
            $query = Mockery::mock(WhereQuery::class);
            $query->shouldReceive('since')->andReturnSelf();
            $query->shouldReceive('unseen')->andReturnSelf();
            $query->shouldReceive('leaveUnread')->andReturnSelf();
            $query->shouldReceive('get')->andReturn(new MessageCollection([]));

            $folder = Mockery::mock(Folder::class);
            $folder->shouldReceive('query')->andReturn($query);

            $client->shouldReceive('getFolder')->with($name)->andReturn($folder);
        }

        return $client;
    }

    // ── fetchEmails ───────────────────────────────────────────────────────────

    public function test_fetch_emails_returns_early_for_null_server(): void
    {
        $mailbox = Mailbox::factory()->create(['in_server' => null]);
        Log::shouldReceive('warning')->once();

        $result = (new ImapService)->fetchEmails($mailbox);

        $this->assertEquals(0, $result['fetched']);
        $this->assertEquals(0, $result['errors']);
        $this->assertStringContainsString('No IMAP server configured', $result['messages'][0]);
    }

    public function test_fetch_emails_fetches_from_inbox_by_default(): void
    {
        $mailbox = $this->mockMailbox(['in_imap_folders' => null]);
        $client = $this->mockConnectedClient(['INBOX']);
        $service = $this->mockService($client);

        $result = $service->fetchEmails($mailbox);

        $this->assertEquals(0, $result['fetched']);
        $this->assertEquals(0, $result['errors']);
    }

    public function test_fetch_emails_handles_connection_failure(): void
    {
        $mailbox = $this->mockMailbox();
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('connect')->andThrow(new ConnectionFailedException('Timeout'));

        $result = $this->mockService($client)->fetchEmails($mailbox);

        $this->assertEquals(0, $result['fetched']);
        $this->assertGreaterThan(0, $result['errors']);
    }

    // ── getFolders ────────────────────────────────────────────────────────────

    public function test_get_folders_returns_folder_names(): void
    {
        $mailbox = $this->mockMailbox();

        $f1 = Mockery::mock(Folder::class);
        $f1->full_name = 'INBOX';
        $f2 = Mockery::mock(Folder::class);
        $f2->full_name = 'Sent';

        $client = Mockery::mock(Client::class);
        $client->shouldReceive('connect');
        $client->shouldReceive('getFolders')->andReturn(new FolderCollection([$f1, $f2]));
        $client->shouldReceive('disconnect');

        $result = $this->mockService($client)->getFolders($mailbox);

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['folders']);
        $this->assertEquals('INBOX', $result['folders'][0]);
    }

    public function test_get_folders_returns_failure_on_connection_error(): void
    {
        $mailbox = $this->mockMailbox();
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('connect')->andThrow(new ConnectionFailedException('No route'));

        $result = $this->mockService($client)->getFolders($mailbox);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Connection failed', $result['message']);
        $this->assertEmpty($result['folders']);
    }

    // ── testConnection ────────────────────────────────────────────────────────

    public function test_test_connection_returns_success_with_message_count(): void
    {
        $mailbox = $this->mockMailbox();

        $msg1 = Mockery::mock(Message::class);
        $msg1->shouldReceive('hasFlag')->with('Seen')->andReturn(false);
        $msg2 = Mockery::mock(Message::class);
        $msg2->shouldReceive('hasFlag')->with('Seen')->andReturn(true);

        $query = Mockery::mock(WhereQuery::class);
        $query->shouldReceive('since')->andReturnSelf();
        $query->shouldReceive('leaveUnread')->andReturnSelf();
        $query->shouldReceive('get')->andReturn(new MessageCollection([$msg1, $msg2]));

        $folder = Mockery::mock(Folder::class);
        $folder->shouldReceive('query')->andReturn($query);

        $client = Mockery::mock(Client::class);
        $client->shouldReceive('connect');
        $client->shouldReceive('getFolder')->with('INBOX')->andReturn($folder);
        $client->shouldReceive('disconnect');

        $result = $this->mockService($client)->testConnection($mailbox);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('2 messages', $result['message']);
        $this->assertStringContainsString('1 unread', $result['message']);
    }

    public function test_test_connection_returns_failure_on_auth_error(): void
    {
        $mailbox = $this->mockMailbox();
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('connect')->andThrow(new ConnectionFailedException('Auth failed'));

        $result = $this->mockService($client)->testConnection($mailbox);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Connection failed', $result['message']);
    }

    public function test_test_connection_returns_failure_when_inbox_not_found(): void
    {
        $mailbox = $this->mockMailbox();
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('connect');
        $client->shouldReceive('getFolder')->with('INBOX')->andReturn(null);

        $result = $this->mockService($client)->testConnection($mailbox);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('INBOX', $result['message']);
    }
}
