<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Mailbox;
use App\Models\User;
use App\Services\ImapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery\MockInterface;

/**
 * Tests for IMAP connection testing and folder retrieval in MailboxesController.
 *
 * @group imap
 */
class MailboxConnectionImapTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test AJAX fetch test succeeds with valid credentials.
     */
    public function test_ajax_fetch_test_succeeds_with_valid_credentials()
    {
        // Arrange
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
            'in_port' => 993,
            'in_username' => 'test@example.com',
            'in_password' => 'password123',
        ]);

        // Mock ImapService
        $this->mock(ImapService::class, function (MockInterface $mock) {
            $mock->shouldReceive('testConnection')
                ->once()
                ->andReturn([
                    'success' => true,
                    'message' => 'Connected successfully. Found 25 messages in INBOX.',
                ]);
        });

        // Act
        $response = $this->actingAs($admin)->postJson(route('mailboxes.ajax'), [
            'action' => 'fetch_test',
            'mailbox_id' => $mailbox->id,
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
        ]);
    }

    /**
     * Test AJAX fetch test fails with invalid credentials.
     */
    public function test_ajax_fetch_test_fails_with_invalid_credentials()
    {
        // Arrange
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
            'in_port' => 993,
            'in_username' => 'test@example.com',
            'in_password' => 'wrongpassword',
        ]);

        // Mock ImapService
        $this->mock(ImapService::class, function (MockInterface $mock) {
            $mock->shouldReceive('testConnection')
                ->once()
                ->andReturn([
                    'success' => false,
                    'message' => 'Authentication failed: Invalid credentials',
                ]);
        });

        // Act
        $response = $this->actingAs($admin)->postJson(route('mailboxes.ajax'), [
            'action' => 'fetch_test',
            'mailbox_id' => $mailbox->id,
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'error',
            'msg' => 'Authentication failed: Invalid credentials',
        ]);
    }

    /**
     * Test AJAX retrieval of IMAP folders.
     */
    public function test_ajax_retrieves_imap_folders()
    {
        // Arrange
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
            'in_port' => 993,
        ]);

        // Mock ImapService
        $this->mock(ImapService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getFolders')
                ->once()
                ->andReturn([
                    'success' => true,
                    'folders' => ['INBOX', 'Sent', 'Drafts', 'Trash', 'Archive'],
                ]);
        });

        // Act
        $response = $this->actingAs($admin)->postJson(route('mailboxes.ajax'), [
            'action' => 'imap_folders',
            'mailbox_id' => $mailbox->id,
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'folders' => ['INBOX', 'Sent', 'Drafts', 'Trash', 'Archive'],
        ]);
    }

    /**
     * Test folder retrieval handles connection errors.
     */
    public function test_ajax_folder_retrieval_handles_connection_errors()
    {
        // Arrange
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'invalid.example.com',
        ]);

        // Mock ImapService
        $this->mock(ImapService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getFolders')
                ->once()
                ->andReturn([
                    'success' => false,
                    'message' => 'Could not connect to server',
                    'folders' => [],
                ]);
        });

        // Act
        $response = $this->actingAs($admin)->postJson(route('mailboxes.ajax'), [
            'action' => 'imap_folders',
            'mailbox_id' => $mailbox->id,
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure(['msg']);
    }

    /**
     * Test non-admin users cannot access fetch test.
     */
    public function test_non_admin_cannot_access_fetch_test()
    {
        // Arrange
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $mailbox = Mailbox::factory()->create();

        // Act
        $response = $this->actingAs($user)->postJson(route('mailboxes.ajax'), [
            'action' => 'fetch_test',
            'mailbox_id' => $mailbox->id,
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'error',
            'msg' => 'Not enough permissions',
        ]);
    }

    /**
     * Test non-admin users cannot retrieve IMAP folders.
     */
    public function test_non_admin_cannot_retrieve_imap_folders()
    {
        // Arrange
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $mailbox = Mailbox::factory()->create();

        // Act
        $response = $this->actingAs($user)->postJson(route('mailboxes.ajax'), [
            'action' => 'imap_folders',
            'mailbox_id' => $mailbox->id,
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'msg' => 'Not enough permissions',
        ]);
    }

    /**
     * Test fetch test handles timeouts gracefully.
     */
    public function test_fetch_test_handles_timeout()
    {
        // Arrange
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'slow-server.example.com',
        ]);

        // Mock ImapService
        $this->mock(ImapService::class, function (MockInterface $mock) {
            $mock->shouldReceive('testConnection')
                ->once()
                ->andReturn([
                    'success' => false,
                    'message' => 'Connection timeout after 30 seconds',
                ]);
        });

        // Act
        $response = $this->actingAs($admin)->postJson(route('mailboxes.ajax'), [
            'action' => 'fetch_test',
            'mailbox_id' => $mailbox->id,
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'error',
        ]);
        $this->assertStringContainsString('timeout', strtolower($response->json('msg')));
    }

    /**
     * Test retrieving nested IMAP folders.
     */
    public function test_ajax_retrieves_nested_imap_folders()
    {
        // Arrange
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
        ]);

        // Mock ImapService
        $this->mock(ImapService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getFolders')
                ->once()
                ->andReturn([
                    'success' => true,
                    'folders' => [
                        'INBOX',
                        'INBOX/Archive',
                        'INBOX/Archive/2024',
                        'Sent',
                        'Drafts',
                    ],
                ]);
        });

        // Act
        $response = $this->actingAs($admin)->postJson(route('mailboxes.ajax'), [
            'action' => 'imap_folders',
            'mailbox_id' => $mailbox->id,
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonFragment(['INBOX/Archive/2024']);
    }

    /**
     * Test folder retrieval with no folders (empty mailbox).
     */
    public function test_ajax_handles_empty_folder_list()
    {
        // Arrange
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
        ]);

        // Mock ImapService
        $this->mock(ImapService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getFolders')
                ->once()
                ->andReturn([
                    'success' => true,
                    'folders' => [],
                ]);
        });

        // Act
        $response = $this->actingAs($admin)->postJson(route('mailboxes.ajax'), [
            'action' => 'imap_folders',
            'mailbox_id' => $mailbox->id,
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'folders' => [],
        ]);
    }

    /**
     * Test fetch test with mailbox not found.
     */
    public function test_fetch_test_with_nonexistent_mailbox()
    {
        // Arrange
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        // Act
        $response = $this->actingAs($admin)->postJson(route('mailboxes.ajax'), [
            'action' => 'fetch_test',
            'mailbox_id' => 999999,
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'error',
            'msg' => 'Mailbox not found',
        ]);
    }
}
