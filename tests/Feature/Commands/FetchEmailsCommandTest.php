<?php

declare(strict_types=1);

namespace Tests\Feature\Commands;

use App\Models\Mailbox;
use App\Services\ImapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * Tests for the FetchEmails command.
 * This command fetches emails from mailbox IMAP servers.
 */
class FetchEmailsCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test command runs successfully with specific mailbox.
     */
    public function test_command_fetches_emails_from_specific_mailbox(): void
    {
        // Arrange
        $mailbox = Mailbox::factory()->create([
            'name' => 'Support Inbox',
            'email' => 'support@example.com',
            'in_server' => 'imap.example.com',
        ]);

        // Mock ImapService
        $this->mock(ImapService::class, function (MockInterface $mock) {
            $mock->shouldReceive('fetchEmails')
                ->once()
                ->andReturn([
                    'fetched' => 5,
                    'created' => 3,
                    'errors' => 0,
                    'messages' => [],
                ]);
        });

        // Act
        $this->artisan('freescout:fetch-emails', ['mailbox_id' => $mailbox->id])
            ->expectsOutput('Processing 1 mailbox(es)...')
            ->expectsOutput("Processing mailbox: Support Inbox (support@example.com)")
            ->expectsOutputToContain('Fetched: 5')
            ->expectsOutputToContain('Created: 3')
            ->expectsOutputToContain('Errors: 0')
            ->expectsOutputToContain('Total fetched: 5')
            ->expectsOutputToContain('Total created: 3')
            ->assertExitCode(0);
    }

    /**
     * Test command fetches from all mailboxes when no ID specified.
     */
    public function test_command_fetches_from_all_mailboxes_when_no_id_specified(): void
    {
        // Arrange
        Mailbox::factory()->create([
            'name' => 'Mailbox 1',
            'in_server' => 'imap1.example.com',
        ]);
        Mailbox::factory()->create([
            'name' => 'Mailbox 2',
            'in_server' => 'imap2.example.com',
        ]);

        // Mock ImapService
        $this->mock(ImapService::class, function (MockInterface $mock) {
            $mock->shouldReceive('fetchEmails')
                ->twice()
                ->andReturn([
                    'fetched' => 2,
                    'created' => 1,
                    'errors' => 0,
                    'messages' => [],
                ]);
        });

        // Act
        $this->artisan('freescout:fetch-emails')
            ->expectsOutput('Processing 2 mailbox(es)...')
            ->expectsOutputToContain('Mailbox 1')
            ->expectsOutputToContain('Mailbox 2')
            ->expectsOutputToContain('Total fetched: 4')
            ->expectsOutputToContain('Total created: 2')
            ->assertExitCode(0);
    }

    /**
     * Test command with test mode only tests connection.
     */
    public function test_command_test_mode_only_tests_connection(): void
    {
        // Arrange
        $mailbox = Mailbox::factory()->create([
            'name' => 'Test Mailbox',
            'in_server' => 'imap.example.com',
        ]);

        // Mock ImapService - should call testConnection, not fetchEmails
        $this->mock(ImapService::class, function (MockInterface $mock) {
            $mock->shouldReceive('testConnection')
                ->once()
                ->andReturn([
                    'success' => true,
                    'message' => 'Connected successfully. Found 10 messages in INBOX (3 unread).',
                ]);
            
            $mock->shouldNotReceive('fetchEmails');
        });

        // Act
        $this->artisan('freescout:fetch-emails', [
            'mailbox_id' => $mailbox->id,
            '--test' => true,
        ])
            ->expectsOutput('Processing 1 mailbox(es)...')
            ->expectsOutputToContain('✓ Connected successfully')
            ->assertExitCode(0);
    }

    /**
     * Test command handles connection failure in test mode.
     */
    public function test_command_handles_connection_failure_in_test_mode(): void
    {
        // Arrange
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'invalid.example.com',
        ]);

        // Mock ImapService
        $this->mock(ImapService::class, function (MockInterface $mock) {
            $mock->shouldReceive('testConnection')
                ->once()
                ->andReturn([
                    'success' => false,
                    'message' => 'Connection failed: Authentication error',
                ]);
        });

        // Act
        $this->artisan('freescout:fetch-emails', [
            'mailbox_id' => $mailbox->id,
            '--test' => true,
        ])
            ->expectsOutputToContain('✗ Connection failed')
            ->assertExitCode(1);
    }

    /**
     * Test command warns when no mailboxes configured.
     */
    public function test_command_warns_when_no_mailboxes_configured(): void
    {
        // No mailboxes created

        // Act
        $this->artisan('freescout:fetch-emails')
            ->expectsOutput('No mailboxes configured for IMAP.')
            ->assertExitCode(1);
    }

    /**
     * Test command skips mailboxes without IMAP server.
     */
    public function test_command_skips_mailboxes_without_imap_server(): void
    {
        // Arrange - Mailbox without IMAP server
        Mailbox::factory()->create([
            'in_server' => null,
        ]);
        Mailbox::factory()->create([
            'in_server' => '',
        ]);

        // Act
        $this->artisan('freescout:fetch-emails')
            ->expectsOutput('No mailboxes configured for IMAP.')
            ->assertExitCode(1);
    }

    /**
     * Test command handles errors during fetch.
     */
    public function test_command_handles_errors_during_fetch(): void
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
                    'created' => 5,
                    'errors' => 3,
                    'messages' => [
                        'Failed to parse email 1',
                        'Failed to parse email 2',
                        'Failed to parse email 3',
                    ],
                ]);
        });

        // Act
        $this->artisan('freescout:fetch-emails', ['mailbox_id' => $mailbox->id])
            ->expectsOutputToContain('Errors: 3')
            ->expectsOutputToContain('Failed to parse email 1')
            ->expectsOutputToContain('Failed to parse email 2')
            ->expectsOutputToContain('Failed to parse email 3')
            ->assertExitCode(1); // Exit code 1 because there were errors
    }

    /**
     * Test command displays summary statistics.
     */
    public function test_command_displays_summary_statistics(): void
    {
        // Arrange
        $mailbox1 = Mailbox::factory()->create(['in_server' => 'imap1.example.com']);
        $mailbox2 = Mailbox::factory()->create(['in_server' => 'imap2.example.com']);

        // Mock ImapService
        $this->mock(ImapService::class, function (MockInterface $mock) {
            // First mailbox
            $mock->shouldReceive('fetchEmails')
                ->once()
                ->andReturn([
                    'fetched' => 5,
                    'created' => 3,
                    'errors' => 1,
                    'messages' => ['Error message'],
                ]);
            
            // Second mailbox
            $mock->shouldReceive('fetchEmails')
                ->once()
                ->andReturn([
                    'fetched' => 8,
                    'created' => 4,
                    'errors' => 0,
                    'messages' => [],
                ]);
        });

        // Act
        $this->artisan('freescout:fetch-emails')
            ->expectsOutput('=== Summary ===')
            ->expectsOutputToContain('Total fetched: 13')
            ->expectsOutputToContain('Total created: 7')
            ->expectsOutputToContain('Total errors: 1')
            ->assertExitCode(1); // Exit code 1 because there was 1 error
    }

    /**
     * Test command handles zero new emails gracefully.
     */
    public function test_command_handles_zero_new_emails(): void
    {
        // Arrange
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
        ]);

        // Mock ImapService
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

        // Act
        $this->artisan('freescout:fetch-emails', ['mailbox_id' => $mailbox->id])
            ->expectsOutputToContain('Fetched: 0')
            ->expectsOutputToContain('Created: 0')
            ->expectsOutputToContain('Errors: 0')
            ->assertExitCode(0);
    }

    /**
     * Test command returns correct exit code on success.
     */
    public function test_command_returns_zero_exit_code_on_success(): void
    {
        // Arrange
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
        ]);

        // Mock ImapService with no errors
        $this->mock(ImapService::class, function (MockInterface $mock) {
            $mock->shouldReceive('fetchEmails')
                ->once()
                ->andReturn([
                    'fetched' => 5,
                    'created' => 5,
                    'errors' => 0,
                    'messages' => [],
                ]);
        });

        // Act & Assert
        $this->artisan('freescout:fetch-emails', ['mailbox_id' => $mailbox->id])
            ->assertExitCode(0);
    }

    /**
     * Test command with non-existent mailbox ID.
     */
    public function test_command_with_nonexistent_mailbox_id(): void
    {
        // Act
        $this->artisan('freescout:fetch-emails', ['mailbox_id' => 999])
            ->expectsOutput('No mailboxes configured for IMAP.')
            ->assertExitCode(1);
    }

    /**
     * Test command processes multiple mailboxes in sequence.
     */
    public function test_command_processes_multiple_mailboxes_in_sequence(): void
    {
        // Arrange
        $mailbox1 = Mailbox::factory()->create([
            'name' => 'Sales',
            'email' => 'sales@example.com',
            'in_server' => 'imap.example.com',
        ]);
        $mailbox2 = Mailbox::factory()->create([
            'name' => 'Support',
            'email' => 'support@example.com',
            'in_server' => 'imap.example.com',
        ]);
        $mailbox3 = Mailbox::factory()->create([
            'name' => 'Info',
            'email' => 'info@example.com',
            'in_server' => 'imap.example.com',
        ]);

        // Mock ImapService
        $this->mock(ImapService::class, function (MockInterface $mock) {
            $mock->shouldReceive('fetchEmails')
                ->times(3)
                ->andReturn([
                    'fetched' => 1,
                    'created' => 1,
                    'errors' => 0,
                    'messages' => [],
                ]);
        });

        // Act
        $this->artisan('freescout:fetch-emails')
            ->expectsOutput('Processing 3 mailbox(es)...')
            ->expectsOutputToContain('Sales (sales@example.com)')
            ->expectsOutputToContain('Support (support@example.com)')
            ->expectsOutputToContain('Info (info@example.com)')
            ->assertExitCode(0);
    }
}
