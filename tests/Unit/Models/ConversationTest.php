<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test Conversation model methods
 * 
 * Focus: Status checks, folder updates, relationships
 */
class ConversationTest extends TestCase
{
    use RefreshDatabase;

    public function test_isActive_returns_true_for_status_1(): void
    {
        $conversation = Conversation::factory()->active()->create();

        $this->assertTrue($conversation->isActive());
    }

    public function test_isActive_returns_false_for_other_statuses(): void
    {
        $pending = Conversation::factory()->create(['status' => 2]);
        $closed = Conversation::factory()->create(['status' => 3]);
        $spam = Conversation::factory()->spam()->create();

        $this->assertFalse($pending->isActive());
        $this->assertFalse($closed->isActive());
        $this->assertFalse($spam->isActive());
    }

    public function test_isClosed_returns_true_for_status_3(): void
    {
        $conversation = Conversation::factory()->create(['status' => 3]);

        $this->assertTrue($conversation->isClosed());
    }

    public function test_isClosed_returns_false_for_other_statuses(): void
    {
        $active = Conversation::factory()->active()->create();
        $pending = Conversation::factory()->create(['status' => 2]);
        $spam = Conversation::factory()->spam()->create();

        $this->assertFalse($active->isClosed());
        $this->assertFalse($pending->isClosed());
        $this->assertFalse($spam->isClosed());
    }

    public function test_folder_relationship_loads(): void
    {
        $conversation = Conversation::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $conversation->folder());
    }

    public function test_mailbox_relationship_loads(): void
    {
        $conversation = Conversation::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $conversation->mailbox());
        $this->assertInstanceOf(Mailbox::class, $conversation->mailbox);
    }

    public function test_user_relationship_loads(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $conversation->user());
        $this->assertEquals($user->id, $conversation->user->id);
    }

    public function test_customer_relationship_loads(): void
    {
        $conversation = Conversation::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $conversation->customer());
        $this->assertInstanceOf(Customer::class, $conversation->customer);
    }

    public function test_threads_relationship_returns_all_threads(): void
    {
        $conversation = Conversation::factory()->withThreads(5)->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $conversation->threads());
        $this->assertCount(5, $conversation->threads);
    }

    public function test_followers_relationship_loads(): void
    {
        $conversation = Conversation::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $conversation->followers());
    }

    public function test_conversation_has_required_fillable_fields(): void
    {
        $conversation = new Conversation();
        $fillable = $conversation->getFillable();

        $this->assertContains('subject', $fillable);
        $this->assertContains('status', $fillable);
        $this->assertContains('state', $fillable);
        $this->assertContains('mailbox_id', $fillable);
        $this->assertContains('customer_id', $fillable);
    }

    public function test_conversation_can_be_created_with_factory(): void
    {
        $conversation = Conversation::factory()->create([
            'subject' => 'Test Conversation',
        ]);

        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'subject' => 'Test Conversation',
        ]);
    }

    public function test_active_factory_state_creates_active_conversation(): void
    {
        $conversation = Conversation::factory()->active()->create();

        $this->assertEquals(1, $conversation->status);
        $this->assertTrue($conversation->isActive());
    }

    public function test_spam_factory_state_creates_spam_conversation(): void
    {
        $conversation = Conversation::factory()->spam()->create();

        $this->assertEquals(4, $conversation->status);
    }

    public function test_draft_factory_state_creates_draft_conversation(): void
    {
        $conversation = Conversation::factory()->draft()->create();

        $this->assertEquals(1, $conversation->state);
    }

    public function test_conversation_with_unicode_subject(): void
    {
        $conversation = Conversation::factory()->withUnicodeSubject()->create();

        $this->assertStringContainsString('🎉', $conversation->subject);
        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
        ]);
    }

    public function test_conversation_with_large_thread_count(): void
    {
        $conversation = Conversation::factory()->withLargeThreadCount()->create();

        $this->assertEquals(100, $conversation->threads_count);
    }

    public function test_conversation_number_is_unique(): void
    {
        $conv1 = Conversation::factory()->create();
        $conv2 = Conversation::factory()->create();

        $this->assertNotEquals($conv1->number, $conv2->number);
    }

    public function test_conversation_has_timestamps(): void
    {
        $conversation = Conversation::factory()->create();

        $this->assertNotNull($conversation->created_at);
        $this->assertNotNull($conversation->updated_at);
    }

    public function test_conversation_can_have_closed_by_user(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create([
            'closed_by_user_id' => $user->id,
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $conversation->closedByUser());
    }

    public function test_conversation_can_have_created_by_user(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create([
            'created_by_user_id' => $user->id,
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $conversation->createdByUser());
    }

    public function test_conversation_defaults_to_published_state(): void
    {
        $conversation = Conversation::factory()->create();

        $this->assertEquals(2, $conversation->state);
    }

    public function test_conversation_has_customer_email_field(): void
    {
        $conversation = Conversation::factory()->create([
            'customer_email' => 'test@example.com',
        ]);

        $this->assertEquals('test@example.com', $conversation->customer_email);
    }

    public function test_conversation_preview_can_be_set(): void
    {
        $conversation = Conversation::factory()->create([
            'preview' => 'This is a preview of the conversation',
        ]);

        $this->assertEquals('This is a preview of the conversation', $conversation->preview);
    }

    public function test_conversation_last_reply_at_tracks_latest_reply(): void
    {
        $time = now()->subHours(2);
        $conversation = Conversation::factory()->create([
            'last_reply_at' => $time,
        ]);

        $this->assertEquals($time->timestamp, $conversation->last_reply_at->timestamp);
    }
}
