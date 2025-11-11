<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $user;
    protected Mailbox $mailbox1;
    protected Mailbox $mailbox2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->user = User::factory()->create(['role' => User::ROLE_USER]);

        $this->mailbox1 = Mailbox::factory()->create([
            'name' => 'Support Mailbox',
            'email' => 'support@example.com',
        ]);

        $this->mailbox2 = Mailbox::factory()->create([
            'name' => 'Sales Mailbox',
            'email' => 'sales@example.com',
        ]);

        // Create folders for mailboxes
        Folder::factory()->create([
            'mailbox_id' => $this->mailbox1->id,
            'type' => Folder::TYPE_INBOX,
        ]);

        Folder::factory()->create([
            'mailbox_id' => $this->mailbox2->id,
            'type' => Folder::TYPE_INBOX,
        ]);
    }

    /**
     * Test admin can view dashboard with all mailboxes.
     */
    public function test_admin_can_view_dashboard_with_all_mailboxes(): void
    {
        // Act
        $response = $this->actingAs($this->admin)->get(route('dashboard'));

        // Assert
        $response->assertOk();
        $response->assertViewHas('mailboxes', function ($mailboxes) {
            return $mailboxes->count() === 2;
        });
        $response->assertViewHas('user', function ($user) {
            return $user->id === $this->admin->id;
        });
    }

    /**
     * Test regular user can view dashboard with only assigned mailboxes.
     */
    public function test_user_can_view_dashboard_with_assigned_mailboxes_only(): void
    {
        // Arrange
        $this->mailbox1->users()->attach($this->user);

        // Act
        $response = $this->actingAs($this->user)->get(route('dashboard'));

        // Assert
        $response->assertOk();
        $response->assertViewHas('mailboxes', function ($mailboxes) {
            return $mailboxes->count() === 1;
        });
    }

    /**
     * Test dashboard displays active conversations count correctly.
     */
    public function test_dashboard_displays_active_conversations_count(): void
    {
        // Arrange
        $this->mailbox1->users()->attach($this->user);

        // Create active conversations
        Conversation::factory()->count(3)->create([
            'mailbox_id' => $this->mailbox1->id,
            'status' => Conversation::STATUS_ACTIVE,
            'state' => 2, // Published
        ]);

        // Create inactive conversation (should not be counted)
        Conversation::factory()->create([
            'mailbox_id' => $this->mailbox1->id,
            'status' => Conversation::STATUS_CLOSED,
            'state' => 2,
        ]);

        // Act
        $response = $this->actingAs($this->user)->get(route('dashboard'));

        // Assert
        $response->assertOk();
        $response->assertViewHas('activeConversations', 3);
    }

    /**
     * Test dashboard displays unassigned conversations count correctly.
     */
    public function test_dashboard_displays_unassigned_conversations_count(): void
    {
        // Arrange
        $this->mailbox1->users()->attach($this->user);

        // Create unassigned active conversations
        Conversation::factory()->count(2)->create([
            'mailbox_id' => $this->mailbox1->id,
            'user_id' => null,
            'status' => Conversation::STATUS_ACTIVE,
            'state' => 2,
        ]);

        // Create assigned conversation (should not be counted as unassigned)
        Conversation::factory()->create([
            'mailbox_id' => $this->mailbox1->id,
            'user_id' => $this->user->id,
            'status' => Conversation::STATUS_ACTIVE,
            'state' => 2,
        ]);

        // Act
        $response = $this->actingAs($this->user)->get(route('dashboard'));

        // Assert
        $response->assertOk();
        $response->assertViewHas('unassignedConversations', 2);
        $response->assertViewHas('activeConversations', 3); // Total active
    }

    /**
     * Test dashboard provides per-mailbox statistics.
     */
    public function test_dashboard_provides_per_mailbox_statistics(): void
    {
        // Arrange
        $this->mailbox1->users()->attach($this->user);
        $this->mailbox2->users()->attach($this->user);

        // Mailbox 1: 2 active, 1 unassigned
        Conversation::factory()->count(2)->create([
            'mailbox_id' => $this->mailbox1->id,
            'status' => Conversation::STATUS_ACTIVE,
            'state' => 2,
            'user_id' => $this->user->id,
        ]);
        Conversation::factory()->create([
            'mailbox_id' => $this->mailbox1->id,
            'status' => Conversation::STATUS_ACTIVE,
            'state' => 2,
            'user_id' => null,
        ]);

        // Mailbox 2: 1 active, 1 unassigned
        Conversation::factory()->create([
            'mailbox_id' => $this->mailbox2->id,
            'status' => Conversation::STATUS_ACTIVE,
            'state' => 2,
            'user_id' => null,
        ]);

        // Act
        $response = $this->actingAs($this->user)->get(route('dashboard'));

        // Assert
        $response->assertOk();
        $response->assertViewHas('stats', function ($stats) {
            return isset($stats[$this->mailbox1->id])
                && $stats[$this->mailbox1->id]['active'] === 3
                && $stats[$this->mailbox1->id]['unassigned'] === 1
                && isset($stats[$this->mailbox2->id])
                && $stats[$this->mailbox2->id]['active'] === 1
                && $stats[$this->mailbox2->id]['unassigned'] === 1;
        });
    }

    /**
     * Test dashboard only counts published conversations (not drafts).
     */
    public function test_dashboard_only_counts_published_conversations(): void
    {
        // Arrange
        $this->mailbox1->users()->attach($this->user);

        // Create published active conversation
        Conversation::factory()->create([
            'mailbox_id' => $this->mailbox1->id,
            'status' => Conversation::STATUS_ACTIVE,
            'state' => 2, // Published
        ]);

        // Create draft conversation (should not be counted)
        Conversation::factory()->create([
            'mailbox_id' => $this->mailbox1->id,
            'status' => Conversation::STATUS_ACTIVE,
            'state' => 1, // Draft
        ]);

        // Act
        $response = $this->actingAs($this->user)->get(route('dashboard'));

        // Assert
        $response->assertOk();
        $response->assertViewHas('activeConversations', 1); // Only published
    }

    /**
     * Test dashboard requires authentication.
     */
    public function test_dashboard_requires_authentication(): void
    {
        // Act
        $response = $this->get(route('dashboard'));

        // Assert
        $response->assertRedirect(route('login'));
    }

    /**
     * Test dashboard with no conversations shows zero counts.
     */
    public function test_dashboard_with_no_conversations_shows_zero_counts(): void
    {
        // Arrange
        $this->mailbox1->users()->attach($this->user);

        // Act
        $response = $this->actingAs($this->user)->get(route('dashboard'));

        // Assert
        $response->assertOk();
        $response->assertViewHas('activeConversations', 0);
        $response->assertViewHas('unassignedConversations', 0);
    }

    /**
     * Test dashboard handles user with no mailboxes.
     */
    public function test_dashboard_handles_user_with_no_mailboxes(): void
    {
        // Act
        $response = $this->actingAs($this->user)->get(route('dashboard'));

        // Assert
        $response->assertOk();
        $response->assertViewHas('mailboxes', function ($mailboxes) {
            return $mailboxes->count() === 0;
        });
        $response->assertViewHas('activeConversations', 0);
        $response->assertViewHas('unassignedConversations', 0);
    }

    /**
     * Test dashboard stats exclude closed conversations.
     */
    public function test_dashboard_stats_exclude_closed_conversations(): void
    {
        // Arrange
        $this->mailbox1->users()->attach($this->user);

        // Create closed conversations
        Conversation::factory()->count(3)->create([
            'mailbox_id' => $this->mailbox1->id,
            'status' => Conversation::STATUS_CLOSED,
            'state' => 2,
        ]);

        // Create one active conversation
        Conversation::factory()->create([
            'mailbox_id' => $this->mailbox1->id,
            'status' => Conversation::STATUS_ACTIVE,
            'state' => 2,
        ]);

        // Act
        $response = $this->actingAs($this->user)->get(route('dashboard'));

        // Assert
        $response->assertOk();
        $response->assertViewHas('activeConversations', 1); // Only active, not closed
    }
}
