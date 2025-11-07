<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationStateFilteringTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Mailbox $mailbox;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->mailbox = Mailbox::factory()->create();
        $this->mailbox->users()->attach($this->user);

        Folder::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'type' => Folder::TYPE_INBOX,
        ]);
    }

    /** @test */
    public function index_only_shows_published_conversations(): void
    {
        // Arrange
        $published = Conversation::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'state' => 2, // Published
            'subject' => 'Published Conversation',
        ]);

        $draft = Conversation::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'state' => 1, // Draft
            'subject' => 'Draft Conversation',
        ]);

        $deleted = Conversation::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'state' => 3, // Deleted
            'subject' => 'Deleted Conversation',
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->get(route('conversations.index', $this->mailbox));

        // Assert
        $response->assertOk();
        $response->assertSee('Published Conversation');
        $response->assertDontSee('Draft Conversation');
        $response->assertDontSee('Deleted Conversation');
    }

    /** @test */
    public function search_only_returns_published_conversations(): void
    {
        // Arrange
        $published = Conversation::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'state' => 2, // Published
            'subject' => 'Search term published',
        ]);

        $draft = Conversation::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'state' => 1, // Draft
            'subject' => 'Search term draft',
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->get(route('conversations.search', ['q' => 'search term']));

        // Assert
        $response->assertOk();
        $response->assertSee('Search term published');
        $response->assertDontSee('Search term draft');
    }

    /** @test */
    public function conversations_ordered_by_last_reply_desc(): void
    {
        // Arrange
        $old = Conversation::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'state' => 2,
            'subject' => 'Old Conversation',
            'last_reply_at' => now()->subDays(5),
        ]);

        $recent = Conversation::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'state' => 2,
            'subject' => 'Recent Conversation',
            'last_reply_at' => now()->subHour(),
        ]);

        $newest = Conversation::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'state' => 2,
            'subject' => 'Newest Conversation',
            'last_reply_at' => now(),
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->get(route('conversations.index', $this->mailbox));

        // Assert
        $response->assertOk();
        
        // Check order in the HTML content
        $content = $response->getContent();
        $newestPos = strpos($content, 'Newest Conversation');
        $recentPos = strpos($content, 'Recent Conversation');
        $oldPos = strpos($content, 'Old Conversation');

        $this->assertNotFalse($newestPos);
        $this->assertNotFalse($recentPos);
        $this->assertNotFalse($oldPos);
        
        // Newest should appear before recent, which should appear before old
        $this->assertLessThan($recentPos, $newestPos);
        $this->assertLessThan($oldPos, $recentPos);
    }

    /** @test */
    public function index_paginates_conversations(): void
    {
        // Arrange - Create more than one page of conversations (50 per page)
        Conversation::factory()->count(55)->create([
            'mailbox_id' => $this->mailbox->id,
            'state' => 2,
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->get(route('conversations.index', $this->mailbox));

        // Assert
        $response->assertOk();
        $response->assertViewHas('conversations');
        
        $conversations = $response->viewData('conversations');
        $this->assertCount(50, $conversations); // First page should have 50
        $this->assertEquals(55, $conversations->total()); // Total should be 55
    }

    /** @test */
    public function can_view_second_page_of_conversations(): void
    {
        // Arrange
        Conversation::factory()->count(55)->create([
            'mailbox_id' => $this->mailbox->id,
            'state' => 2,
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->get(route('conversations.index', $this->mailbox) . '?page=2');

        // Assert
        $response->assertOk();
        $conversations = $response->viewData('conversations');
        $this->assertCount(5, $conversations); // Second page should have 5
    }
}
