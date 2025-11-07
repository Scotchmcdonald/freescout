<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Mailbox;
use App\Models\User;
use App\Services\ImapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * Tests for the Mailbox fetch emails API endpoint.
 * This endpoint allows manual email fetching from IMAP server.
 */
class MailboxFetchEmailsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $regularUser;
    protected Mailbox $mailbox;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->regularUser = User::factory()->create(['role' => User::ROLE_USER]);
        $this->mailbox = Mailbox::factory()->create();
    }

    /**
     * Test admin can trigger manual email fetch.
     */
    public function test_admin_can_trigger_manual_email_fetch(): void
    {
        // Arrange
        $this->actingAs($this->admin);

        // Mock the ImapService
        $this->mock(ImapService::class, function (MockInterface $mock) {
            $mock->shouldReceive('fetchEmails')
                ->once()
                ->andReturn([
                    'fetched' => 5,
                    'created' => 3,
                ]);
        });

        // Act
        $response = $this->postJson(route('mailboxes.fetch-emails', $this->mailbox));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'stats' => [
                'fetched' => 5,
                'created' => 3,
            ],
        ]);
        $response->assertJsonFragment(['message' => 'Successfully fetched 5 emails. Created 3 new conversations.']);
    }

    /**
     * Test non-admin cannot trigger manual email fetch.
     */
    public function test_non_admin_cannot_trigger_manual_email_fetch(): void
    {
        // Arrange
        $this->actingAs($this->regularUser);

        // Act
        $response = $this->postJson(route('mailboxes.fetch-emails', $this->mailbox));

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'Unauthorized access.',
        ]);
    }

    /**
     * Test fetch emails returns error when IMAP connection fails.
     */
    public function test_fetch_emails_returns_error_on_imap_failure(): void
    {
        // Arrange
        $this->actingAs($this->admin);

        // Mock the ImapService to throw exception
        $this->mock(ImapService::class, function (MockInterface $mock) {
            $mock->shouldReceive('fetchEmails')
                ->once()
                ->andThrow(new \Exception('IMAP connection failed'));
        });

        // Act
        $response = $this->postJson(route('mailboxes.fetch-emails', $this->mailbox));

        // Assert
        $response->assertStatus(500);
        $response->assertJson([
            'success' => false,
        ]);
        $response->assertJsonFragment(['message' => 'Failed to fetch emails: IMAP connection failed']);
    }

    /**
     * Test unauthenticated user cannot trigger email fetch.
     */
    public function test_unauthenticated_user_cannot_trigger_email_fetch(): void
    {
        // Act
        $response = $this->postJson(route('mailboxes.fetch-emails', $this->mailbox));

        // Assert
        $response->assertStatus(401);
    }

    /**
     * Test fetch emails with zero new emails.
     */
    public function test_fetch_emails_with_zero_new_emails(): void
    {
        // Arrange
        $this->actingAs($this->admin);

        // Mock the ImapService
        $this->mock(ImapService::class, function (MockInterface $mock) {
            $mock->shouldReceive('fetchEmails')
                ->once()
                ->andReturn([
                    'fetched' => 0,
                    'created' => 0,
                ]);
        });

        // Act
        $response = $this->postJson(route('mailboxes.fetch-emails', $this->mailbox));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'stats' => [
                'fetched' => 0,
                'created' => 0,
            ],
        ]);
    }
}
