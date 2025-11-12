<?php

namespace Tests\Feature\Commands;

use App\Console\Commands\FetchEmails;
use App\Models\Mailbox;
use App\Models\User;
use App\Models\Option;
use App\Services\ImapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery\MockInterface;

class FetchEmailsCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test fetches emails from multiple IMAP folders.
     * 
     * @group imap
     */
    public function test_fetches_from_multiple_imap_folders()
    {
        // Arrange: Create a mailbox
        $mailbox = Mailbox::factory()->create([
            'name' => 'Support',
            'in_server' => 'imap.example.com',
            'in_imap_folders' => json_encode(['INBOX', 'Archive']),
        ]);

        // Mock ImapService to return stats indicating folders were processed
        $this->mock(ImapService::class, function (MockInterface $mock) {
            $mock->shouldReceive('fetchEmails')
                ->once()
                ->andReturn([
                    'fetched' => 10,
                    'created' => 8,
                    'errors' => 0,
                    'messages' => [],
                ]);
        });

        // Act & Assert
        $this->artisan('freescout:fetch-emails', ['mailbox_id' => $mailbox->id])
            ->expectsOutputToContain('Fetched: 10')
            ->expectsOutputToContain('Created: 8')
            ->assertExitCode(0);
    }

    /**
     * Test handles empty mailbox gracefully.
     * 
     * @group imap
     */
    public function test_handles_empty_mailbox_gracefully()
    {
        // Arrange
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
        ]);

        // Mock ImapService to return no emails
        $this->mock(ImapService::class, function (MockInterface $mock) {
            $mock->shouldReceive('fetchEmails')
                ->once()
                ->andReturn([
                    'fetched' => 0,
                    'created' => 0,
                    'errors' => 0,
                    'messages' => [],
                ]);
        });

        // Act & Assert
        $this->artisan('freescout:fetch-emails', ['mailbox_id' => $mailbox->id])
            ->expectsOutputToContain('Fetched: 0')
            ->expectsOutputToContain('Created: 0')
            ->expectsOutputToContain('Errors: 0')
            ->assertExitCode(0);
    }

    /**
     * Test connection test mode works correctly.
     * 
     * @group imap
     */
    public function test_connection_test_mode()
    {
        // Arrange
        $mailbox = Mailbox::factory()->create([
            'name' => 'Test Mailbox',
            'in_server' => 'imap.example.com',
        ]);

        // Mock ImapService test connection
        $this->mock(ImapService::class, function (MockInterface $mock) {
            $mock->shouldReceive('testConnection')
                ->once()
                ->andReturn([
                    'success' => true,
                    'message' => 'Connected successfully. Found 15 messages.',
                ]);

            // Should NOT call fetchEmails in test mode
            $mock->shouldNotReceive('fetchEmails');
        });

        // Act & Assert
        $this->artisan('freescout:fetch-emails', [
            'mailbox_id' => $mailbox->id,
            '--test' => true,
        ])
            ->expectsOutputToContain('✓ Connected successfully')
            ->doesntExpectOutput('=== Summary ===')
            ->assertExitCode(0);
    }

    /**
     * Test connection failure in test mode.
     * 
     * @group imap
     */
    public function test_connection_failure_in_test_mode()
    {
        // Arrange
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'invalid.example.com',
        ]);

        // Mock ImapService test connection failure
        $this->mock(ImapService::class, function (MockInterface $mock) {
            $mock->shouldReceive('testConnection')
                ->once()
                ->andReturn([
                    'success' => false,
                    'message' => 'Connection failed: Could not connect to server',
                ]);
        });

        // Act & Assert
        $this->artisan('freescout:fetch-emails', [
            'mailbox_id' => $mailbox->id,
            '--test' => true,
        ])
            ->expectsOutputToContain('✗ Connection failed')
            ->assertExitCode(1);
    }

    /**
     * Test fetches from all mailboxes when no ID specified.
     * 
     * @group imap
     */
    public function test_fetches_from_all_mailboxes()
    {
        // Arrange
        $mailbox1 = Mailbox::factory()->create([
            'name' => 'Sales',
            'in_server' => 'imap1.example.com',
        ]);
        $mailbox2 = Mailbox::factory()->create([
            'name' => 'Support',
            'in_server' => 'imap2.example.com',
        ]);

        // Mock ImapService
        $this->mock(ImapService::class, function (MockInterface $mock) {
            $mock->shouldReceive('fetchEmails')
                ->twice()
                ->andReturn([
                    'fetched' => 5,
                    'created' => 3,
                    'errors' => 0,
                    'messages' => [],
                ]);
        });

        // Act & Assert
        $this->artisan('freescout:fetch-emails')
            ->expectsOutput('Processing 2 mailbox(es)...')
            ->expectsOutputToContain('Sales')
            ->expectsOutputToContain('Support')
            ->expectsOutputToContain('Total fetched: 10')
            ->expectsOutputToContain('Total created: 6')
            ->assertExitCode(0);
    }

    /**
     * Test handles fetch errors and reports them.
     * 
     * @group imap
     */
    public function test_handles_fetch_errors()
    {
        // Arrange
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
        ]);

        // Mock ImapService with errors
        $this->mock(ImapService::class, function (MockInterface $mock) {
            $mock->shouldReceive('fetchEmails')
                ->once()
                ->andReturn([
                    'fetched' => 10,
                    'created' => 7,
                    'errors' => 3,
                    'messages' => [
                        'Failed to parse message 1',
                        'Failed to parse message 2',
                        'Failed to parse message 3',
                    ],
                ]);
        });

        // Act & Assert
        $this->artisan('freescout:fetch-emails', ['mailbox_id' => $mailbox->id])
            ->expectsOutputToContain('Errors: 3')
            ->expectsOutputToContain('Failed to parse message 1')
            ->expectsOutputToContain('Failed to parse message 2')
            ->expectsOutputToContain('Failed to parse message 3')
            ->assertExitCode(1);
    }

    /**
     * Test skips mailboxes without IMAP configuration.
     * 
     * @group imap
     */
    public function test_skips_mailboxes_without_imap_server()
    {
        // Arrange - mailboxes without IMAP server
        Mailbox::factory()->create(['in_server' => null]);
        Mailbox::factory()->create(['in_server' => '']);

        // Mock ImapService - should never be called
        $this->mock(ImapService::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('fetchEmails');
            $mock->shouldNotReceive('testConnection');
        });

        // Act & Assert
        $this->artisan('freescout:fetch-emails')
            ->expectsOutput('No mailboxes configured for IMAP.')
            ->assertExitCode(1);
    }
}
