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

    public function thread_belongs_to_conversation(): void
    {
        $thread = Thread::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $thread->conversation());
        $this->assertInstanceOf(Conversation::class, $thread->conversation);
    }

    public function thread_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $thread = Thread::factory()->create(['created_by_user_id' => $user->id]);
        $thread = $thread->fresh(['user']);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $thread->user());
        $this->assertNotNull($thread->user);
        $this->assertEquals($user->id, $thread->user->id);
    }

    public function thread_belongs_to_customer(): void
    {
        $customer = Customer::factory()->create();
        $thread = Thread::factory()->create(['created_by_customer_id' => $customer->id]);
        $thread = $thread->fresh(['customer']);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $thread->customer());
        $this->assertNotNull($thread->customer);
        $this->assertEquals($customer->id, $thread->customer->id);
    }

    public function thread_has_many_attachments(): void
    {
        $thread = Thread::factory()->withAttachments(3)->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $thread->attachments());
        $this->assertCount(3, $thread->attachments);
    }

    public function customer_message_factory_creates_type_4(): void
    {
        $thread = Thread::factory()->customerMessage()->create();

        $this->assertEquals(4, $thread->type);
    }

    public function user_reply_factory_creates_type_1(): void
    {
        $thread = Thread::factory()->userReply()->create();

        $this->assertEquals(1, $thread->type);
    }

    public function thread_with_large_body_creates_successfully(): void
    {
        $thread = Thread::factory()->withLargeBody()->create();

        $this->assertGreaterThan(1000, strlen($thread->body));
    }

    public function thread_with_html_body_saves_html(): void
    {
        $thread = Thread::factory()->withHtmlBody()->create();

        $this->assertStringContainsString('<html>', $thread->body);
        $this->assertStringContainsString('<body>', $thread->body);
        $this->assertStringContainsString('<h1>Test Email</h1>', $thread->body);
    }

    public function thread_has_required_fillable_fields(): void
    {
        $thread = new Thread();
        $fillable = $thread->getFillable();

        $this->assertContains('body', $fillable);
        $this->assertContains('type', $fillable);
        $this->assertContains('state', $fillable);
        $this->assertContains('conversation_id', $fillable);
    }

    public function thread_can_be_created_with_factory(): void
    {
        $thread = Thread::factory()->create([
            'body' => 'Test thread body',
        ]);

        $this->assertDatabaseHas('threads', [
            'id' => $thread->id,
            'body' => 'Test thread body',
        ]);
    }

    public function thread_defaults_to_published_state(): void
    {
        $thread = Thread::factory()->create();

        $this->assertEquals(2, $thread->state);
    }

    public function thread_has_timestamps(): void
    {
        $thread = Thread::factory()->create();

        $this->assertNotNull($thread->created_at);
        $this->assertNotNull($thread->updated_at);
    }

    public function thread_can_have_empty_body(): void
    {
        $thread = Thread::factory()->create(['body' => '']);

        $this->assertEquals('', $thread->body);
    }

    public function thread_body_preserves_newlines(): void
    {
        $body = "Line 1\nLine 2\nLine 3";
        $thread = Thread::factory()->create(['body' => $body]);

        $this->assertEquals($body, $thread->body);
    }

    public function thread_body_preserves_unicode(): void
    {
        $body = '这是中文内容 和 日本語 そして 한국어';
        $thread = Thread::factory()->create(['body' => $body]);

        $this->assertEquals($body, $thread->body);
    }

    public function thread_body_preserves_emoji(): void
    {
        $body = 'Hello 👋 World 🌍 Testing 🧪';
        $thread = Thread::factory()->create(['body' => $body]);

        $this->assertEquals($body, $thread->body);
    }

    public function thread_can_have_from_field(): void
    {
        $thread = Thread::factory()->create([
            'from' => 'user@example.com',
        ]);

        $this->assertEquals('user@example.com', $thread->from);
    }

    public function thread_can_have_to_field(): void
    {
        $thread = Thread::factory()->create([
            'to' => 'customer@example.com',
        ]);

        $this->assertEquals('customer@example.com', $thread->to);
    }

    public function thread_can_have_cc_field(): void
    {
        $thread = Thread::factory()->create([
            'cc' => 'cc1@example.com,cc2@example.com',
        ]);

        $this->assertEquals('cc1@example.com,cc2@example.com', $thread->cc);
    }

    public function thread_can_have_bcc_field(): void
    {
        $thread = Thread::factory()->create([
            'bcc' => 'bcc@example.com',
        ]);

        $this->assertEquals('bcc@example.com', $thread->bcc);
    }

    public function thread_action_type_can_be_set(): void
    {
        $thread = Thread::factory()->create([
            'action_type' => 5,
        ]);

        $this->assertEquals(5, $thread->action_type);
    }

    public function thread_source_via_can_be_set(): void
    {
        $thread = Thread::factory()->create([
            'source_via' => 2,
        ]);

        $this->assertEquals(2, $thread->source_via);
    }

    public function thread_opened_at_can_be_null(): void
    {
        $thread = Thread::factory()->create([
            'opened_at' => null,
        ]);

        $this->assertNull($thread->opened_at);
    }

    public function thread_opened_at_can_be_set(): void
    {
        $time = now();
        $thread = Thread::factory()->create([
            'opened_at' => $time,
        ]);

        $this->assertEquals($time->timestamp, $thread->opened_at->timestamp);
    }
}
