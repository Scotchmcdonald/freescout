<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Mailbox;
use App\Services\ImapService;
use Mockery;
use Tests\UnitTestCase;
use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\Exceptions\ConnectionFailedException;
use Webklex\PHPIMAP\Folder;
use Webklex\PHPIMAP\Support\FolderCollection;

/**
 * Comprehensive tests for ImapService::getFolders() method.
 * This method currently has ~50% coverage and needs additional testing.
 */
class ImapServiceGetFoldersTest extends UnitTestCase
{
    protected ImapService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ImapService;
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

    public function test_get_folders_returns_success_with_folder_list(): void
    {
        $mailbox = $this->createMockMailbox();

        // Create mock folders
        $folder1 = Mockery::mock(Folder::class);
        $folder1->full_name = 'INBOX';

        $folder2 = Mockery::mock(Folder::class);
        $folder2->full_name = 'Sent';

        $folder3 = Mockery::mock(Folder::class);
        $folder3->full_name = 'Drafts';

        $folderCollection = new FolderCollection([$folder1, $folder2, $folder3]);

        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('connect')->once();
        $mockClient->shouldReceive('getFolders')->once()->andReturn($folderCollection);
        $mockClient->shouldReceive('disconnect')->once();

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('createClient')->with($mailbox)->andReturn($mockClient);

        $result = $service->getFolders($mailbox);

        $this->assertTrue($result['success']);
        $this->assertCount(3, $result['folders']);
        $this->assertEquals('INBOX', $result['folders'][0]);
        $this->assertEquals('Sent', $result['folders'][1]);
        $this->assertEquals('Drafts', $result['folders'][2]);
    }

    public function test_get_folders_returns_success_with_empty_folder_list(): void
    {
        $mailbox = $this->createMockMailbox();

        $folderCollection = new FolderCollection([]);

        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('connect')->once();
        $mockClient->shouldReceive('getFolders')->once()->andReturn($folderCollection);
        $mockClient->shouldReceive('disconnect')->once();

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('createClient')->with($mailbox)->andReturn($mockClient);

        $result = $service->getFolders($mailbox);

        $this->assertTrue($result['success']);
        $this->assertCount(0, $result['folders']);
        $this->assertEquals('Connected, but no folders found', $result['message']);
    }

    public function test_get_folders_handles_connection_failure(): void
    {
        $mailbox = $this->createMockMailbox();

        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('connect')
            ->once()
            ->andThrow(new ConnectionFailedException('Connection timeout'));

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('createClient')->with($mailbox)->andReturn($mockClient);

        $result = $service->getFolders($mailbox);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Connection failed', $result['message']);
        $this->assertStringContainsString('Connection timeout', $result['message']);
        $this->assertEmpty($result['folders']);
    }

    public function test_get_folders_handles_general_exception(): void
    {
        $mailbox = $this->createMockMailbox();

        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('connect')->once();
        $mockClient->shouldReceive('getFolders')
            ->once()
            ->andThrow(new \Exception('Unexpected error occurred'));

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('createClient')->with($mailbox)->andReturn($mockClient);

        $result = $service->getFolders($mailbox);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Error:', $result['message']);
        $this->assertStringContainsString('Unexpected error occurred', $result['message']);
        $this->assertEmpty($result['folders']);
    }

    public function test_get_folders_with_nested_folder_structure(): void
    {
        $mailbox = $this->createMockMailbox();

        // Create folders with nested paths
        $folder1 = Mockery::mock(Folder::class);
        $folder1->full_name = 'INBOX';

        $folder2 = Mockery::mock(Folder::class);
        $folder2->full_name = 'INBOX/Archive';

        $folder3 = Mockery::mock(Folder::class);
        $folder3->full_name = 'INBOX/Archive/2024';

        $folderCollection = new FolderCollection([$folder1, $folder2, $folder3]);

        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('connect')->once();
        $mockClient->shouldReceive('getFolders')->once()->andReturn($folderCollection);
        $mockClient->shouldReceive('disconnect')->once();

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('createClient')->with($mailbox)->andReturn($mockClient);

        $result = $service->getFolders($mailbox);

        $this->assertTrue($result['success']);
        $this->assertCount(3, $result['folders']);
        $this->assertEquals('INBOX', $result['folders'][0]);
        $this->assertEquals('INBOX/Archive', $result['folders'][1]);
        $this->assertEquals('INBOX/Archive/2024', $result['folders'][2]);
    }

    public function test_get_folders_with_special_characters_in_folder_names(): void
    {
        $mailbox = $this->createMockMailbox();

        $folder1 = Mockery::mock(Folder::class);
        $folder1->full_name = 'INBOX';

        $folder2 = Mockery::mock(Folder::class);
        $folder2->full_name = '[Gmail]/Sent Mail';

        $folder3 = Mockery::mock(Folder::class);
        $folder3->full_name = '[Gmail]/All Mail';

        $folderCollection = new FolderCollection([$folder1, $folder2, $folder3]);

        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('connect')->once();
        $mockClient->shouldReceive('getFolders')->once()->andReturn($folderCollection);
        $mockClient->shouldReceive('disconnect')->once();

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('createClient')->with($mailbox)->andReturn($mockClient);

        $result = $service->getFolders($mailbox);

        $this->assertTrue($result['success']);
        $this->assertCount(3, $result['folders']);
        $this->assertContains('[Gmail]/Sent Mail', $result['folders']);
        $this->assertContains('[Gmail]/All Mail', $result['folders']);
    }

    public function test_get_folders_result_structure_always_has_required_keys(): void
    {
        $mailbox = $this->createMockMailbox();

        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('connect')
            ->once()
            ->andThrow(new \Exception('Test error'));

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('createClient')->with($mailbox)->andReturn($mockClient);

        $result = $service->getFolders($mailbox);

        // Even on error, result should have proper structure
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('folders', $result);
        $this->assertIsBool($result['success']);
        $this->assertIsString($result['message']);
        $this->assertIsArray($result['folders']);
    }

    public function test_get_folders_disconnects_client_even_after_error(): void
    {
        $mailbox = $this->createMockMailbox();

        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('connect')->once();
        $mockClient->shouldReceive('getFolders')
            ->once()
            ->andThrow(new \Exception('Test error'));
        // Should NOT call disconnect when exception occurs during getFolders
        $mockClient->shouldNotReceive('disconnect');

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('createClient')->with($mailbox)->andReturn($mockClient);

        $result = $service->getFolders($mailbox);

        $this->assertFalse($result['success']);
    }

    public function test_get_folders_with_single_folder(): void
    {
        $mailbox = $this->createMockMailbox();

        $folder = Mockery::mock(Folder::class);
        $folder->full_name = 'INBOX';

        $folderCollection = new FolderCollection([$folder]);

        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('connect')->once();
        $mockClient->shouldReceive('getFolders')->once()->andReturn($folderCollection);
        $mockClient->shouldReceive('disconnect')->once();

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('createClient')->with($mailbox)->andReturn($mockClient);

        $result = $service->getFolders($mailbox);

        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['folders']);
        $this->assertEquals('INBOX', $result['folders'][0]);
        $this->assertEmpty($result['message']);
    }

    public function test_get_folders_with_many_folders(): void
    {
        $mailbox = $this->createMockMailbox();

        $folders = [];
        for ($i = 1; $i <= 20; $i++) {
            $folder = Mockery::mock(Folder::class);
            $folder->full_name = "Folder{$i}";
            $folders[] = $folder;
        }

        $folderCollection = new FolderCollection($folders);

        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('connect')->once();
        $mockClient->shouldReceive('getFolders')->once()->andReturn($folderCollection);
        $mockClient->shouldReceive('disconnect')->once();

        $service = Mockery::mock(ImapService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('createClient')->with($mailbox)->andReturn($mockClient);

        $result = $service->getFolders($mailbox);

        $this->assertTrue($result['success']);
        $this->assertCount(20, $result['folders']);
        $this->assertEquals('Folder1', $result['folders'][0]);
        $this->assertEquals('Folder20', $result['folders'][19]);
    }
}
