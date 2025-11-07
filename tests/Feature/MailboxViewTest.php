<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Mailbox;
use App\Models\User;
use App\Services\ImapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Comprehensive tests for Mailbox show/view functionality.
 * Tests the mailbox detail view and conversation listing.
 */
class MailboxViewTest extends TestCase
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
        $this->mailbox = Mailbox::factory()->create(['name' => 'Support Mailbox']);
    }

    /**
     * Test admin can view mailbox detail page.
     */
    public function test_admin_can_view_mailbox_detail(): void
    {
        // Arrange
        $this->actingAs($this->admin);

        // Act
        $response = $this->get(route('mailboxes.view', $this->mailbox));

        // Assert
        $response->assertStatus(200);
        $response->assertSee('Support Mailbox');
    }

    /**
     * Test user with access can view mailbox detail.
     */
    public function test_user_with_access_can_view_mailbox_detail(): void
    {
        // Arrange
        $this->mailbox->users()->attach($this->regularUser);
        $this->actingAs($this->regularUser);

        // Act
        $response = $this->get(route('mailboxes.view', $this->mailbox));

        // Assert
        $response->assertStatus(200);
    }

    /**
     * Test user without access cannot view mailbox detail.
     */
    public function test_user_without_access_cannot_view_mailbox_detail(): void
    {
        // Arrange
        $this->actingAs($this->regularUser);

        // Act
        $response = $this->get(route('mailboxes.view', $this->mailbox));

        // Assert
        $response->assertStatus(403);
    }

    /**
     * Test unauthenticated user cannot view mailbox detail.
     */
    public function test_unauthenticated_user_cannot_view_mailbox_detail(): void
    {
        // Act
        $response = $this->get(route('mailboxes.view', $this->mailbox));

        // Assert
        $response->assertRedirect(route('login'));
    }

    /**
     * Test admin can view mailbox settings page.
     */
    public function test_admin_can_view_mailbox_settings_page(): void
    {
        // Arrange
        $this->actingAs($this->admin);

        // Act
        $response = $this->get(route('mailboxes.settings', $this->mailbox));

        // Assert
        $response->assertStatus(200);
    }

    /**
     * Test non-admin cannot view mailbox settings page.
     */
    public function test_non_admin_cannot_view_mailbox_settings_page(): void
    {
        // Arrange
        $this->actingAs($this->regularUser);

        // Act
        $response = $this->get(route('mailboxes.settings', $this->mailbox));

        // Assert
        $response->assertStatus(403);
    }

    /**
     * Test mailbox index shows only accessible mailboxes for regular user.
     */
    public function test_mailbox_index_shows_only_accessible_mailboxes_for_user(): void
    {
        // Arrange
        $mailbox1 = Mailbox::factory()->create(['name' => 'Accessible Mailbox']);
        $mailbox2 = Mailbox::factory()->create(['name' => 'Inaccessible Mailbox']);
        
        $mailbox1->users()->attach($this->regularUser);
        
        $this->actingAs($this->regularUser);

        // Act
        $response = $this->get(route('mailboxes.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertSee('Accessible Mailbox');
        $response->assertDontSee('Inaccessible Mailbox');
    }

    /**
     * Test mailbox index shows all mailboxes for admin.
     */
    public function test_mailbox_index_shows_all_mailboxes_for_admin(): void
    {
        // Arrange
        $mailbox1 = Mailbox::factory()->create(['name' => 'Mailbox One']);
        $mailbox2 = Mailbox::factory()->create(['name' => 'Mailbox Two']);
        
        $this->actingAs($this->admin);

        // Act
        $response = $this->get(route('mailboxes.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertSee('Mailbox One');
        $response->assertSee('Mailbox Two');
    }

    /**
     * Test mailbox index requires authentication.
     */
    public function test_mailbox_index_requires_authentication(): void
    {
        // Act
        $response = $this->get(route('mailboxes.index'));

        // Assert
        $response->assertRedirect(route('login'));
    }
}
