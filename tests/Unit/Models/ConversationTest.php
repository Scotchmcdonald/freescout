<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Folder;
use App\Models\Mailbox;
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

    public function test_is_active_returns_true_for_status_1(): void
    {
        $conversation = Conversation::factory()->active()->create();

        $this->assertTrue($conversation->isActive());
    }

    public function test_is_active_returns_false_for_other_statuses(): void
    {
        $pending = Conversation::factory()->create(['status' => 2]);
        $closed = Conversation::factory()->create(['status' => 3]);
        $spam = Conversation::factory()->spam()->create();

        $this->assertFalse($pending->isActive());
        $this->assertFalse($closed->isActive());
        $this->assertFalse($spam->isActive());
    }

    public function test_is_closed_returns_true_for_status_3(): void
    {
        $conversation = Conversation::factory()->create(['status' => 3]);

        $this->assertTrue($conversation->isClosed());
    }

    public function test_is_closed_returns_false_for_other_statuses(): void
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
        $conversation = new Conversation;
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

    // getStatusName() tests - 57% coverage

    public function test_get_status_name_returns_active_for_status_1(): void
    {
        $conversation = Conversation::factory()->create([
            'status' => Conversation::STATUS_ACTIVE,
        ]);

        $statusName = $conversation->getStatusName();

        $this->assertIsString($statusName);
        // Laravel's __ function returns the key if no translation exists
        $this->assertTrue(in_array($statusName, ['Active', __('Active')]));
    }

    public function test_get_status_name_returns_pending_for_status_2(): void
    {
        $conversation = Conversation::factory()->create([
            'status' => Conversation::STATUS_PENDING,
        ]);

        $statusName = $conversation->getStatusName();

        $this->assertIsString($statusName);
        $this->assertTrue(in_array($statusName, ['Pending', __('Pending')]));
    }

    public function test_get_status_name_returns_closed_for_status_3(): void
    {
        $conversation = Conversation::factory()->create([
            'status' => Conversation::STATUS_CLOSED,
        ]);

        $statusName = $conversation->getStatusName();

        $this->assertIsString($statusName);
        $this->assertTrue(in_array($statusName, ['Closed', __('Closed')]));
    }

    public function test_get_status_name_returns_spam_for_status_4(): void
    {
        $conversation = Conversation::factory()->create([
            'status' => Conversation::STATUS_SPAM,
        ]);

        $statusName = $conversation->getStatusName();

        $this->assertIsString($statusName);
        $this->assertTrue(in_array($statusName, ['Spam', __('Spam')]));
    }

    public function test_get_status_name_returns_unknown_for_invalid_status(): void
    {
        $conversation = Conversation::factory()->create([
            'status' => 999, // Invalid status
        ]);

        $statusName = $conversation->getStatusName();

        $this->assertIsString($statusName);
        $this->assertTrue(in_array($statusName, ['Unknown', __('Unknown')]));
    }

    // getStatusColor() tests - 57% coverage

    public function test_get_status_color_returns_blue_for_active(): void
    {
        $conversation = Conversation::factory()->create([
            'status' => Conversation::STATUS_ACTIVE,
        ]);

        $color = $conversation->getStatusColor();

        $this->assertEquals('#3f8abf', $color);
    }

    public function test_get_status_color_returns_yellow_for_pending(): void
    {
        $conversation = Conversation::factory()->create([
            'status' => Conversation::STATUS_PENDING,
        ]);

        $color = $conversation->getStatusColor();

        $this->assertEquals('#e6b216', $color);
    }

    public function test_get_status_color_returns_green_for_closed(): void
    {
        $conversation = Conversation::factory()->create([
            'status' => Conversation::STATUS_CLOSED,
        ]);

        $color = $conversation->getStatusColor();

        $this->assertEquals('#5cb85c', $color);
    }

    public function test_get_status_color_returns_red_for_spam(): void
    {
        $conversation = Conversation::factory()->create([
            'status' => Conversation::STATUS_SPAM,
        ]);

        $color = $conversation->getStatusColor();

        $this->assertEquals('#d9534f', $color);
    }

    public function test_get_status_color_returns_grey_for_unknown_status(): void
    {
        $conversation = Conversation::factory()->create([
            'status' => 999, // Invalid status
        ]);

        $color = $conversation->getStatusColor();

        $this->assertEquals('#777777', $color);
    }

    public function test_get_status_color_returns_valid_hex_code(): void
    {
        $conversation = Conversation::factory()->create([
            'status' => Conversation::STATUS_ACTIVE,
        ]);

        $color = $conversation->getStatusColor();

        // Verify it's a valid hex color code
        $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/i', $color);
    }

    // Additional edge case tests for getStatusName and getStatusColor

    public function test_get_status_name_handles_negative_status(): void
    {
        $conversation = Conversation::factory()->create([
            'status' => -1, // Negative status
        ]);

        $statusName = $conversation->getStatusName();

        // Should return Unknown for invalid status
        $this->assertTrue(in_array($statusName, ['Unknown', __('Unknown')]));
    }

    public function test_get_status_name_handles_very_large_status_number(): void
    {
        $conversation = Conversation::factory()->create([
            'status' => PHP_INT_MAX, // Maximum integer
        ]);

        $statusName = $conversation->getStatusName();

        // Should return Unknown for invalid status
        $this->assertTrue(in_array($statusName, ['Unknown', __('Unknown')]));
    }

    public function test_get_status_color_handles_negative_status(): void
    {
        $conversation = Conversation::factory()->create([
            'status' => -1,
        ]);

        $color = $conversation->getStatusColor();

        // Should return default grey color
        $this->assertEquals('#777777', $color);
        $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/i', $color);
    }

    public function test_get_status_name_all_defined_statuses_return_translation(): void
    {
        $statuses = [
            Conversation::STATUS_ACTIVE,
            Conversation::STATUS_PENDING,
            Conversation::STATUS_CLOSED,
            Conversation::STATUS_SPAM,
        ];

        foreach ($statuses as $status) {
            $conversation = Conversation::factory()->create(['status' => $status]);
            $statusName = $conversation->getStatusName();

            // Should return a non-empty string
            $this->assertNotEmpty($statusName);
            $this->assertIsString($statusName);
        }
    }

    public function test_get_status_color_all_defined_statuses_return_valid_hex(): void
    {
        $statuses = [
            Conversation::STATUS_ACTIVE,
            Conversation::STATUS_PENDING,
            Conversation::STATUS_CLOSED,
            Conversation::STATUS_SPAM,
        ];

        foreach ($statuses as $status) {
            $conversation = Conversation::factory()->create(['status' => $status]);
            $color = $conversation->getStatusColor();

            // Should return valid hex color
            $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/i', $color);
        }
    }

    public function test_get_status_color_returns_unique_colors_for_different_statuses(): void
    {
        $activeColor = Conversation::factory()->create(['status' => Conversation::STATUS_ACTIVE])->getStatusColor();
        $pendingColor = Conversation::factory()->create(['status' => Conversation::STATUS_PENDING])->getStatusColor();
        $closedColor = Conversation::factory()->create(['status' => Conversation::STATUS_CLOSED])->getStatusColor();
        $spamColor = Conversation::factory()->create(['status' => Conversation::STATUS_SPAM])->getStatusColor();

        // All colors should be different
        $colors = [$activeColor, $pendingColor, $closedColor, $spamColor];
        $uniqueColors = array_unique($colors);

        $this->assertCount(4, $uniqueColors, 'Each status should have a unique color');
    }
}
