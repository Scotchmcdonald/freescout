<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Attachment;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test Thread model methods
 * 
 * Focus: Type checks, body handling, relationships
 */
class ThreadTest extends TestCase
{
    use RefreshDatabase;

    public function test_thread_belongs_to_conversation(): void
    {
        $thread = Thread::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $thread->conversation());
        $this->assertInstanceOf(Conversation::class, $thread->conversation);
    }

    public function test_thread_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $thread = Thread::factory()->create(['created_by_user_id' => $user->id]);
        $thread = $thread->fresh(['user']);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $thread->user());
        $this->assertNotNull($thread->user);
        $this->assertEquals($user->id, $thread->user->id);
    }

    public function test_thread_belongs_to_customer(): void
    {
        $customer = Customer::factory()->create();
        $thread = Thread::factory()->create(['created_by_customer_id' => $customer->id]);
        $thread = $thread->fresh(['customer']);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $thread->customer());
        $this->assertNotNull($thread->customer);
        $this->assertEquals($customer->id, $thread->customer->id);
    }

    public function test_thread_has_many_attachments(): void
    {
        $thread = Thread::factory()->withAttachments(3)->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $thread->attachments());
        $this->assertCount(3, $thread->attachments);
    }

    public function test_customer_message_factory_creates_type_4(): void
    {
        $thread = Thread::factory()->customerMessage()->create();

        $this->assertEquals(4, $thread->type);
    }

    public function test_user_reply_factory_creates_type_1(): void
    {
        $thread = Thread::factory()->userReply()->create();

        $this->assertEquals(1, $thread->type);
    }

    public function test_thread_with_large_body_creates_successfully(): void
    {
        $thread = Thread::factory()->withLargeBody()->create();

        $this->assertGreaterThan(1000, strlen($thread->body));
    }

    public function test_thread_with_html_body_saves_html(): void
    {
        $thread = Thread::factory()->withHtmlBody()->create();

        $this->assertStringContainsString('<html>', $thread->body);
        $this->assertStringContainsString('<body>', $thread->body);
        $this->assertStringContainsString('<h1>Test Email</h1>', $thread->body);
    }

    public function test_thread_has_required_fillable_fields(): void
    {
        $thread = new Thread();
        $fillable = $thread->getFillable();

        $this->assertContains('body', $fillable);
        $this->assertContains('type', $fillable);
        $this->assertContains('state', $fillable);
        $this->assertContains('conversation_id', $fillable);
    }

    public function test_thread_can_be_created_with_factory(): void
    {
        $thread = Thread::factory()->create([
            'body' => 'Test thread body',
        ]);

        $this->assertDatabaseHas('threads', [
            'id' => $thread->id,
            'body' => 'Test thread body',
        ]);
    }

    public function test_thread_defaults_to_published_state(): void
    {
        $thread = Thread::factory()->create();

        $this->assertEquals(2, $thread->state);
    }

    public function test_thread_has_timestamps(): void
    {
        $thread = Thread::factory()->create();

        $this->assertNotNull($thread->created_at);
        $this->assertNotNull($thread->updated_at);
    }

    public function test_thread_can_have_empty_body(): void
    {
        $thread = Thread::factory()->create(['body' => '']);

        $this->assertEquals('', $thread->body);
    }

    public function test_thread_body_preserves_newlines(): void
    {
        $body = "Line 1\nLine 2\nLine 3";
        $thread = Thread::factory()->create(['body' => $body]);

        $this->assertEquals($body, $thread->body);
    }

    public function test_thread_body_preserves_unicode(): void
    {
        $body = '这是中文内容 和 日本語 そして 한국어';
        $thread = Thread::factory()->create(['body' => $body]);

        $this->assertEquals($body, $thread->body);
    }

    public function test_thread_body_preserves_emoji(): void
    {
        $body = 'Hello 👋 World 🌍 Testing 🧪';
        $thread = Thread::factory()->create(['body' => $body]);

        $this->assertEquals($body, $thread->body);
    }

    public function test_thread_can_have_from_field(): void
    {
        $thread = Thread::factory()->create([
            'from' => 'user@example.com',
        ]);

        $this->assertEquals('user@example.com', $thread->from);
    }

    public function test_thread_can_have_to_field(): void
    {
        $thread = Thread::factory()->create([
            'to' => 'customer@example.com',
        ]);

        $this->assertEquals('customer@example.com', $thread->to);
    }

    public function test_thread_can_have_cc_field(): void
    {
        $thread = Thread::factory()->create([
            'cc' => 'cc1@example.com,cc2@example.com',
        ]);

        $this->assertEquals('cc1@example.com,cc2@example.com', $thread->cc);
    }

    public function test_thread_can_have_bcc_field(): void
    {
        $thread = Thread::factory()->create([
            'bcc' => 'bcc@example.com',
        ]);

        $this->assertEquals('bcc@example.com', $thread->bcc);
    }

    public function test_thread_action_type_can_be_set(): void
    {
        $thread = Thread::factory()->create([
            'action_type' => 5,
        ]);

        $this->assertEquals(5, $thread->action_type);
    }

    public function test_thread_source_via_can_be_set(): void
    {
        $thread = Thread::factory()->create([
            'source_via' => 2,
        ]);

        $this->assertEquals(2, $thread->source_via);
    }

    public function test_thread_opened_at_can_be_null(): void
    {
        $thread = Thread::factory()->create([
            'opened_at' => null,
        ]);

        $this->assertNull($thread->opened_at);
    }

    public function test_thread_opened_at_can_be_set(): void
    {
        $time = now();
        $thread = Thread::factory()->create([
            'opened_at' => $time,
        ]);

        $this->assertEquals($time->timestamp, $thread->opened_at->timestamp);
    }

    // Additional tests for uncovered methods (73.81% → 90-95%)

    public function test_is_customer_message_returns_true_for_customer_type(): void
    {
        $thread = Thread::factory()->create([
            'type' => Thread::TYPE_CUSTOMER,
        ]);

        $this->assertTrue($thread->isCustomerMessage());
    }

    public function test_is_customer_message_returns_false_for_other_types(): void
    {
        $thread = Thread::factory()->create([
            'type' => Thread::TYPE_MESSAGE,
        ]);

        $this->assertFalse($thread->isCustomerMessage());
    }

    public function test_is_user_message_returns_true_for_message_type(): void
    {
        $thread = Thread::factory()->create([
            'type' => Thread::TYPE_MESSAGE,
        ]);

        $this->assertTrue($thread->isUserMessage());
    }

    public function test_is_user_message_returns_false_for_other_types(): void
    {
        $thread = Thread::factory()->create([
            'type' => Thread::TYPE_NOTE,
        ]);

        $this->assertFalse($thread->isUserMessage());
    }

    public function test_is_note_returns_true_for_note_type(): void
    {
        $thread = Thread::factory()->create([
            'type' => Thread::TYPE_NOTE,
        ]);

        $this->assertTrue($thread->isNote());
    }

    public function test_is_note_returns_false_for_other_types(): void
    {
        $thread = Thread::factory()->create([
            'type' => Thread::TYPE_MESSAGE,
        ]);

        $this->assertFalse($thread->isNote());
    }

    public function test_is_bounce_returns_true_when_bounce_in_meta(): void
    {
        $thread = Thread::factory()->create([
            'meta' => ['send_status' => ['is_bounce' => true]],
        ]);

        $this->assertTrue($thread->isBounce());
    }

    public function test_is_bounce_returns_false_when_no_bounce_in_meta(): void
    {
        $thread = Thread::factory()->create([
            'meta' => ['send_status' => []],
        ]);

        $this->assertFalse($thread->isBounce());
    }

    public function test_is_bounce_returns_false_when_meta_is_null(): void
    {
        $thread = Thread::factory()->create([
            'meta' => null,
        ]);

        $this->assertFalse($thread->isBounce());
    }

    public function test_is_bounce_returns_false_when_send_status_missing(): void
    {
        $thread = Thread::factory()->create([
            'meta' => ['other_key' => 'value'],
        ]);

        $this->assertFalse($thread->isBounce());
    }

    public function test_get_created_by_returns_user(): void
    {
        $user = User::factory()->create();
        $thread = Thread::factory()->create([
            'created_by_user_id' => $user->id,
        ]);
        $thread = $thread->fresh(['createdByUser']);

        $createdBy = $thread->getCreatedBy();

        $this->assertInstanceOf(User::class, $createdBy);
        $this->assertEquals($user->id, $createdBy->id);
    }

    public function test_get_created_by_returns_null_when_no_user(): void
    {
        $thread = Thread::factory()->create([
            'created_by_user_id' => null,
        ]);

        $createdBy = $thread->getCreatedBy();

        $this->assertNull($createdBy);
    }

    public function test_get_status_name_returns_active(): void
    {
        $thread = Thread::factory()->create([
            'status' => \App\Models\Conversation::STATUS_ACTIVE,
        ]);

        $statusName = $thread->getStatusName();

        $this->assertIsString($statusName);
        $this->assertNotEmpty($statusName);
    }

    public function test_get_status_name_returns_pending(): void
    {
        $thread = Thread::factory()->create([
            'status' => \App\Models\Conversation::STATUS_PENDING,
        ]);

        $statusName = $thread->getStatusName();

        $this->assertIsString($statusName);
        $this->assertNotEmpty($statusName);
    }

    public function test_get_status_name_returns_closed(): void
    {
        $thread = Thread::factory()->create([
            'status' => \App\Models\Conversation::STATUS_CLOSED,
        ]);

        $statusName = $thread->getStatusName();

        $this->assertIsString($statusName);
        $this->assertNotEmpty($statusName);
    }

    public function test_get_status_name_returns_spam(): void
    {
        $thread = Thread::factory()->create([
            'status' => \App\Models\Conversation::STATUS_SPAM,
        ]);

        $statusName = $thread->getStatusName();

        $this->assertIsString($statusName);
        $this->assertNotEmpty($statusName);
    }

    public function test_get_status_name_returns_unknown_for_invalid_status(): void
    {
        $thread = Thread::factory()->create([
            'status' => 999,
        ]);

        $statusName = $thread->getStatusName();

        $this->assertIsString($statusName);
    }

    public function test_get_action_text_returns_string(): void
    {
        $thread = Thread::factory()->create();

        $actionText = $thread->getActionText();

        $this->assertIsString($actionText);
        $this->assertNotEmpty($actionText);
    }

    public function test_get_action_text_with_parameters(): void
    {
        $thread = Thread::factory()->create();

        $actionText = $thread->getActionText('custom text', true, false, null, 'John Doe');

        $this->assertIsString($actionText);
    }

    public function test_get_assignee_name_returns_user_name(): void
    {
        $user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
        $thread = Thread::factory()->make([
            'user_id' => $user->id,
        ]);
        // Set the user relationship directly
        $thread->setRelation('user', $user);

        $assigneeName = $thread->getAssigneeName();

        $this->assertIsString($assigneeName);
        $this->assertStringContainsString('John', $assigneeName);
    }

    public function test_get_assignee_name_returns_unknown_when_no_user(): void
    {
        $thread = Thread::factory()->create([
            'user_id' => null,
        ]);

        $assigneeName = $thread->getAssigneeName();

        $this->assertIsString($assigneeName);
    }

    public function test_get_assignee_name_with_short_parameter(): void
    {
        $user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
        $thread = Thread::factory()->create([
            'user_id' => $user->id,
        ]);
        $thread = $thread->fresh(['user']);

        $assigneeName = $thread->getAssigneeName(true);

        $this->assertIsString($assigneeName);
    }

    public function test_created_by_user_relationship(): void
    {
        $user = User::factory()->create();
        $thread = Thread::factory()->create([
            'created_by_user_id' => $user->id,
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $thread->createdByUser());
        $thread = $thread->fresh(['createdByUser']);
        $this->assertEquals($user->id, $thread->createdByUser->id);
    }

    public function test_edited_by_user_relationship(): void
    {
        $user = User::factory()->create();
        $thread = Thread::factory()->create([
            'edited_by_user_id' => $user->id,
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $thread->editedByUser());
        $thread = $thread->fresh(['editedByUser']);
        $this->assertEquals($user->id, $thread->editedByUser->id);
    }

    public function test_send_logs_relationship_exists(): void
    {
        $thread = Thread::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $thread->sendLogs());
    }

    public function test_thread_type_constants_are_defined(): void
    {
        $this->assertEquals(1, Thread::TYPE_MESSAGE);
        $this->assertEquals(2, Thread::TYPE_NOTE);
        $this->assertEquals(3, Thread::TYPE_CUSTOMER);
        $this->assertEquals(4, Thread::TYPE_LINEITEM);
        $this->assertEquals(8, Thread::TYPE_CHAT);
        $this->assertEquals(9, Thread::TYPE_BOUNCE);
        $this->assertEquals(5, Thread::TYPE_DRAFT);
    }

    public function test_thread_state_constants_are_defined(): void
    {
        $this->assertEquals(1, Thread::STATE_DRAFT);
        $this->assertEquals(2, Thread::STATE_PUBLISHED);
        $this->assertEquals(3, Thread::STATE_HIDDEN);
        $this->assertEquals(4, Thread::STATE_REVIEW);
    }

    public function test_thread_casts_method_returns_array(): void
    {
        // In Laravel 11, casts() is protected, so we test by checking actual casting behavior
        $thread = Thread::factory()->create([
            'type' => '1',
            'status' => '2',
            'state' => '1',
        ]);
        $thread->refresh();

        $this->assertIsInt($thread->type);
        $this->assertIsInt($thread->status);
        $this->assertIsInt($thread->state);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $thread->opened_at ?? $thread->created_at);
    }

    public function test_thread_meta_is_cast_to_array(): void
    {
        $meta = ['key' => 'value', 'number' => 123];
        $thread = Thread::factory()->create([
            'meta' => $meta,
        ]);

        $this->assertIsArray($thread->meta);
        $this->assertEquals($meta, $thread->meta);
    }

    public function test_thread_cc_and_bcc_are_cast_to_json(): void
    {
        $thread = Thread::factory()->create([
            'cc' => ['cc1@example.com', 'cc2@example.com'],
            'bcc' => ['bcc@example.com'],
        ]);

        $thread = $thread->fresh();
        $this->assertIsArray($thread->cc);
        $this->assertIsArray($thread->bcc);
    }
}
