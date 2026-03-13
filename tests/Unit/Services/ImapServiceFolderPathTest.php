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
use Webklex\PHPIMAP\Query\WhereQuery;
use Webklex\PHPIMAP\Support\MessageCollection;

/**
 * Comprehensive tests for folder path handling in ImapService::fetchEmails().
 * Tests various input types: null, string, array, empty, whitespace, etc.
 */
class ImapServiceFolderPathTest extends TestCase
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
        $mailbox->in_server = $attrs['in_server'] ?? 'imap.example.com';
        $mailbox->in_port = $attrs['in_port'] ?? 993;
        $mailbox->in_username = $attrs['in_username'] ?? 'test@example.com';
        $mailbox->in_password = $attrs['in_password'] ?? encrypt('password');
        $mailbox->in_encryption = $attrs['in_encryption'] ?? 1;
        $mailbox->in_imap_folders = $attrs['in_imap_folders'] ?? null;

        return $mailbox;
    }

    /**
     * Helper to create a standard mock client that expects folder queries
     */
    protected function createMockClientForFolders(array $folderNames): Client
    {
        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('connect')->once();
        $mockClient->shouldReceive('getLastError')->andReturn('');
        $mockClient->shouldReceive('disconnect')->once();

        foreach ($folderNames as $folderName) {
            $mockQuery = Mockery::mock(WhereQuery::class);
            $mockQuery->shouldReceive('since')->once()->andReturnSelf();
            $mockQuery->shouldReceive('unseen')->once()->andReturnSelf();
            $mockQuery->shouldReceive('leaveUnread')->once()->andReturnSelf();
            $mockQuery->shouldReceive('get')->once()->andReturn(new MessageCollection([]));

            $mockFolder = Mockery::mock(Folder::class);
            $mockFolder->shouldReceive('query')->once()->andReturn($mockQuery);

            $mockClient->shouldReceive('getFolder')->with($folderName)->once()->andReturn($mockFolder);
        }

        return $mockClient;
    }

    public function test_defaults_to_inbox_when_folders_is_null(): void
    {
        $mailbox = $this->createMockMailbox(['in_imap_folders' => null]);
        $mockClient = $this->createMockClientForFolders(['INBOX']);

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('createClient')->with($mailbox)->andReturn($mockClient);

        $result = $service->fetchEmails($mailbox);

        $this->assertEquals(0, $result['fetched']);
        $this->assertEquals(0, $result['errors']);
    }

    public function test_defaults_to_inbox_when_folders_is_empty_string(): void
    {
        $mailbox = $this->createMockMailbox(['in_imap_folders' => '']);
        $mockClient = $this->createMockClientForFolders(['INBOX']);

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('createClient')->with($mailbox)->andReturn($mockClient);

        $result = $service->fetchEmails($mailbox);

        $this->assertEquals(0, $result['fetched']);
    }

    public function test_defaults_to_inbox_when_folders_is_whitespace(): void
    {
        $mailbox = $this->createMockMailbox(['in_imap_folders' => '   ']);
        $mockClient = $this->createMockClientForFolders(['INBOX']);

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('createClient')->with($mailbox)->andReturn($mockClient);

        $result = $service->fetchEmails($mailbox);

        $this->assertEquals(0, $result['fetched']);
    }

    public function test_handles_single_folder_as_string(): void
    {
        $mailbox = $this->createMockMailbox(['in_imap_folders' => 'INBOX']);
        $mockClient = $this->createMockClientForFolders(['INBOX']);

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('createClient')->with($mailbox)->andReturn($mockClient);

        $result = $service->fetchEmails($mailbox);

        $this->assertEquals(0, $result['fetched']);
    }

    public function test_handles_multiple_folders_as_comma_separated_string(): void
    {
        $mailbox = $this->createMockMailbox(['in_imap_folders' => 'INBOX,Sent,Drafts']);
        $mockClient = $this->createMockClientForFolders(['INBOX', 'Sent', 'Drafts']);

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('createClient')->with($mailbox)->andReturn($mockClient);

        $result = $service->fetchEmails($mailbox);

        $this->assertEquals(0, $result['fetched']);
    }

    public function test_handles_folders_as_array(): void
    {
        $mailbox = $this->createMockMailbox(['in_imap_folders' => ['INBOX', 'Sent', 'Archive']]);
        $mockClient = $this->createMockClientForFolders(['INBOX', 'Sent', 'Archive']);

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('createClient')->with($mailbox)->andReturn($mockClient);

        $result = $service->fetchEmails($mailbox);

        $this->assertEquals(0, $result['fetched']);
    }

    public function test_handles_empty_array_defaults_to_inbox(): void
    {
        $mailbox = $this->createMockMailbox(['in_imap_folders' => []]);
        $mockClient = $this->createMockClientForFolders(['INBOX']);

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('createClient')->with($mailbox)->andReturn($mockClient);

        $result = $service->fetchEmails($mailbox);

        $this->assertEquals(0, $result['fetched']);
    }

    public function test_handles_folders_with_spaces_in_string(): void
    {
        $mailbox = $this->createMockMailbox(['in_imap_folders' => 'INBOX, Sent, Drafts']);
        // Note: spaces after commas are preserved in the split
        $mockClient = $this->createMockClientForFolders(['INBOX', ' Sent', ' Drafts']);

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('createClient')->with($mailbox)->andReturn($mockClient);

        $result = $service->fetchEmails($mailbox);

        $this->assertEquals(0, $result['fetched']);
    }

    public function test_handles_folders_with_special_characters(): void
    {
        $mailbox = $this->createMockMailbox(['in_imap_folders' => '[Gmail]/Sent Mail,[Gmail]/All Mail']);
        $mockClient = $this->createMockClientForFolders(['[Gmail]/Sent Mail', '[Gmail]/All Mail']);

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('createClient')->with($mailbox)->andReturn($mockClient);

        $result = $service->fetchEmails($mailbox);

        $this->assertEquals(0, $result['fetched']);
    }

    public function test_skips_folder_when_not_found(): void
    {
        $mailbox = $this->createMockMailbox(['in_imap_folders' => 'INBOX,NonExistent,Sent']);

        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('connect')->once();
        $mockClient->shouldReceive('getLastError')->andReturn('');
        $mockClient->shouldReceive('disconnect')->once();

        // INBOX exists
        $mockQuery1 = Mockery::mock(WhereQuery::class);
        $mockQuery1->shouldReceive('since')->once()->andReturnSelf();
        $mockQuery1->shouldReceive('unseen')->once()->andReturnSelf();
        $mockQuery1->shouldReceive('leaveUnread')->once()->andReturnSelf();
        $mockQuery1->shouldReceive('get')->once()->andReturn(new MessageCollection([]));

        $mockFolder1 = Mockery::mock(Folder::class);
        $mockFolder1->shouldReceive('query')->once()->andReturn($mockQuery1);

        $mockClient->shouldReceive('getFolder')->with('INBOX')->once()->andReturn($mockFolder1);

        // NonExistent returns null
        $mockClient->shouldReceive('getFolder')->with('NonExistent')->once()->andReturn(null);

        // Sent exists
        $mockQuery3 = Mockery::mock(WhereQuery::class);
        $mockQuery3->shouldReceive('since')->once()->andReturnSelf();
        $mockQuery3->shouldReceive('unseen')->once()->andReturnSelf();
        $mockQuery3->shouldReceive('leaveUnread')->once()->andReturnSelf();
        $mockQuery3->shouldReceive('get')->once()->andReturn(new MessageCollection([]));

        $mockFolder3 = Mockery::mock(Folder::class);
        $mockFolder3->shouldReceive('query')->once()->andReturn($mockQuery3);

        $mockClient->shouldReceive('getFolder')->with('Sent')->once()->andReturn($mockFolder3);

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('createClient')->with($mailbox)->andReturn($mockClient);

        $result = $service->fetchEmails($mailbox);

        // Should process INBOX and Sent, skip NonExistent
        $this->assertEquals(0, $result['fetched']);
        $this->assertEquals(0, $result['errors']);
    }

    public function test_handles_folder_with_trailing_comma(): void
    {
        $mailbox = $this->createMockMailbox(['in_imap_folders' => 'INBOX,Sent,']);
        // Trailing comma creates an empty string in the array
        $mockClient = $this->createMockClientForFolders(['INBOX', 'Sent', '']);

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('createClient')->with($mailbox)->andReturn($mockClient);

        $result = $service->fetchEmails($mailbox);

        $this->assertEquals(0, $result['fetched']);
    }

    public function test_handles_folder_with_leading_comma(): void
    {
        $mailbox = $this->createMockMailbox(['in_imap_folders' => ',INBOX,Sent']);
        // Leading comma creates an empty string in the array
        $mockClient = $this->createMockClientForFolders(['', 'INBOX', 'Sent']);

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('createClient')->with($mailbox)->andReturn($mockClient);

        $result = $service->fetchEmails($mailbox);

        $this->assertEquals(0, $result['fetched']);
    }

    public function test_handles_folders_with_multiple_commas(): void
    {
        $mailbox = $this->createMockMailbox(['in_imap_folders' => 'INBOX,,Sent']);
        // Double comma creates an empty string in the array
        $mockClient = $this->createMockClientForFolders(['INBOX', '', 'Sent']);

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('createClient')->with($mailbox)->andReturn($mockClient);

        $result = $service->fetchEmails($mailbox);

        $this->assertEquals(0, $result['fetched']);
    }

    public function test_handles_single_folder_array(): void
    {
        $mailbox = $this->createMockMailbox(['in_imap_folders' => ['INBOX']]);
        $mockClient = $this->createMockClientForFolders(['INBOX']);

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('createClient')->with($mailbox)->andReturn($mockClient);

        $result = $service->fetchEmails($mailbox);

        $this->assertEquals(0, $result['fetched']);
    }

    public function test_handles_nested_folder_paths(): void
    {
        $mailbox = $this->createMockMailbox(['in_imap_folders' => 'INBOX,INBOX/Archive,INBOX/Archive/2024']);
        $mockClient = $this->createMockClientForFolders(['INBOX', 'INBOX/Archive', 'INBOX/Archive/2024']);

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('createClient')->with($mailbox)->andReturn($mockClient);

        $result = $service->fetchEmails($mailbox);

        $this->assertEquals(0, $result['fetched']);
    }
}
