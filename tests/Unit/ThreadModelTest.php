<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Attachment;
use App\Models\Conversation;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThreadModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_thread_has_conversation_id(): void
    {
        $thread = new Thread(['conversation_id' => 123]);
        $this->assertEquals(123, $thread->conversation_id);
    }

    public function test_thread_has_user_id(): void
    {
        $thread = new Thread(['user_id' => 456]);
        $this->assertEquals(456, $thread->user_id);
    }

    public function test_thread_has_customer_id(): void
    {
        $thread = new Thread(['customer_id' => 789]);
        $this->assertEquals(789, $thread->customer_id);
    }

    public function test_thread_has_type(): void
    {
        $thread = new Thread(['type' => 1]);
        $this->assertEquals(1, $thread->type);
    }

    public function test_thread_has_body(): void
    {
        $thread = new Thread(['body' => 'Test message body']);
        $this->assertEquals('Test message body', $thread->body);
    }

    public function test_thread_has_from(): void
    {
        $thread = new Thread(['from' => 'user@example.com']);
        $this->assertEquals('user@example.com', $thread->from);
    }

    public function test_thread_has_to(): void
    {
        $thread = new Thread(['to' => 'support@example.com']);
        $this->assertEquals('support@example.com', $thread->to);
    }

    public function test_thread_has_cc(): void
    {
        $thread = new Thread(['cc' => 'cc@example.com']);
        $this->assertEquals('cc@example.com', $thread->cc);
    }

    public function test_thread_has_bcc(): void
    {
        $thread = new Thread(['bcc' => 'bcc@example.com']);
        $this->assertEquals('bcc@example.com', $thread->bcc);
    }

    public function test_thread_has_headers(): void
    {
        $thread = new Thread(['headers' => 'X-Custom: value']);
        $this->assertEquals('X-Custom: value', $thread->headers);
    }

    public function test_is_customer_message_returns_true_for_customer_type(): void
    {
        $thread = Thread::factory()->make(['type' => 4]);

        $this->assertTrue($thread->isCustomerMessage());
    }

    public function test_is_customer_message_returns_false_for_non_customer_type(): void
    {
        $thread = Thread::factory()->make(['type' => 1]);
        $this->assertFalse($thread->isCustomerMessage());

        $thread = Thread::factory()->make(['type' => 2]);
        $this->assertFalse($thread->isCustomerMessage());
    }

    public function test_is_user_message_returns_true_for_user_type(): void
    {
        $thread = Thread::factory()->make(['type' => 1]);

        $this->assertTrue($thread->isUserMessage());
    }

    public function test_is_user_message_returns_false_for_non_user_type(): void
    {
        $thread = Thread::factory()->make(['type' => 2]);
        $this->assertFalse($thread->isUserMessage());

        $thread = Thread::factory()->make(['type' => 4]);
        $this->assertFalse($thread->isUserMessage());
    }

    public function test_is_note_returns_true_for_note_type(): void
    {
        $thread = Thread::factory()->make(['type' => 2]);

        $this->assertTrue($thread->isNote());
    }

    public function test_is_note_returns_false_for_non_note_type(): void
    {
        $thread = Thread::factory()->make(['type' => 1]);
        $this->assertFalse($thread->isNote());

        $thread = Thread::factory()->make(['type' => 4]);
        $this->assertFalse($thread->isNote());
    }

    public function test_to_cc_bcc_cast_to_json(): void
    {
        $to = ['to@example.com'];
        $cc = ['cc1@example.com', 'cc2@example.com'];
        $bcc = ['bcc@example.com'];

        $thread = Thread::factory()->create([
            'to' => $to,
            'cc' => $cc,
            'bcc' => $bcc,
        ]);

        $this->assertIsArray($thread->to);
        $this->assertIsArray($thread->cc);
        $this->assertIsArray($thread->bcc);
        $this->assertEquals($to, $thread->to);
        $this->assertEquals($cc, $thread->cc);
        $this->assertEquals($bcc, $thread->bcc);
    }

    public function test_meta_cast_to_array(): void
    {
        $meta = ['key1' => 'value1', 'key2' => 'value2'];
        $thread = Thread::factory()->create(['meta' => $meta]);

        $this->assertIsArray($thread->meta);
        $this->assertEquals($meta, $thread->meta);
    }

    public function test_belongs_to_conversation(): void
    {
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->for($conversation)->create();

        $this->assertInstanceOf(Conversation::class, $thread->conversation);
        $this->assertEquals($conversation->id, $thread->conversation->id);
    }

    public function test_belongs_to_created_by_user(): void
    {
        $user = User::factory()->create();
        $thread = Thread::factory()->create(['created_by_user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $thread->createdByUser);
        $this->assertEquals($user->id, $thread->createdByUser->id);
    }

    public function test_belongs_to_edited_by_user(): void
    {
        $user = User::factory()->create();
        $thread = Thread::factory()->create(['edited_by_user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $thread->editedByUser);
        $this->assertEquals($user->id, $thread->editedByUser->id);
    }

    public function test_has_many_attachments(): void
    {
        $thread = Thread::factory()->create();
        $attachment1 = Attachment::factory()->for($thread)->create();
        $attachment2 = Attachment::factory()->for($thread)->create();

        $this->assertCount(2, $thread->attachments);
        $this->assertTrue($thread->attachments->contains($attachment1));
        $this->assertTrue($thread->attachments->contains($attachment2));
    }

    public function test_is_bounce_returns_false_when_no_bounce_in_meta(): void
    {
        $thread = Thread::factory()->create(['meta' => []]);

        $this->assertFalse($thread->isBounce());
    }

    public function test_is_bounce_returns_true_when_bounce_in_meta(): void
    {
        $thread = Thread::factory()->create([
            'meta' => ['send_status' => ['is_bounce' => true]],
        ]);

        $this->assertTrue($thread->isBounce());
    }

    public function test_is_bounce_returns_false_when_bounce_is_false_in_meta(): void
    {
        $thread = Thread::factory()->create([
            'meta' => ['send_status' => ['is_bounce' => false]],
        ]);

        $this->assertFalse($thread->isBounce());
    }

    public function test_is_bounce_handles_null_meta(): void
    {
        $thread = Thread::factory()->create(['meta' => null]);

        $this->assertFalse($thread->isBounce());
    }

    public function test_thread_with_null_opened_at(): void
    {
        $thread = Thread::factory()->create(['opened_at' => null]);

        $this->assertNull($thread->opened_at);
    }

    public function test_thread_with_opened_at_datetime(): void
    {
        $openedAt = now()->subHours(2);
        $thread = Thread::factory()->create(['opened_at' => $openedAt]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $thread->opened_at);
        $this->assertEquals($openedAt->toDateTimeString(), $thread->opened_at->toDateTimeString());
    }

    public function test_thread_type_constants(): void
    {
        // Test that type field accepts various numeric values
        $thread1 = Thread::factory()->make(['type' => 1]);
        $this->assertEquals(1, $thread1->type);
        $this->assertTrue($thread1->isUserMessage());

        $thread2 = Thread::factory()->make(['type' => 2]);
        $this->assertEquals(2, $thread2->type);
        $this->assertTrue($thread2->isNote());

        $thread4 = Thread::factory()->make(['type' => 4]);
        $this->assertEquals(4, $thread4->type);
        $this->assertTrue($thread4->isCustomerMessage());
    }

    public function test_thread_with_null_customer_id(): void
    {
        $thread = Thread::factory()->create(['created_by_customer_id' => null]);

        $this->assertNull($thread->created_by_customer_id);
        $this->assertNull($thread->customer);
    }

    public function test_thread_with_null_user_id(): void
    {
        $thread = Thread::factory()->create(['created_by_user_id' => null]);

        $this->assertNull($thread->created_by_user_id);
        $this->assertNull($thread->user);
    }

    public function test_thread_meta_with_nested_arrays(): void
    {
        $complexMeta = [
            'custom_data' => [
                'key1' => 'value1',
                'nested' => ['deep' => 'value'],
            ],
            'send_status' => [
                'is_bounce' => false,
                'attempts' => 3,
            ],
        ];

        $thread = Thread::factory()->create(['meta' => $complexMeta]);

        $this->assertIsArray($thread->meta);
        $this->assertEquals($complexMeta, $thread->meta);
    }

    public function test_thread_first_flag_cast_to_boolean(): void
    {
        $thread = Thread::factory()->create(['first' => true]);
        $this->assertIsBool($thread->first);
        $this->assertTrue($thread->first);

        $thread2 = Thread::factory()->create(['first' => false]);
        $this->assertIsBool($thread2->first);
        $this->assertFalse($thread2->first);
    }

    public function test_thread_has_attachments_flag_cast_to_boolean(): void
    {
        $thread = Thread::factory()->create(['has_attachments' => true]);
        $this->assertIsBool($thread->has_attachments);
        $this->assertTrue($thread->has_attachments);

        $thread2 = Thread::factory()->create(['has_attachments' => false]);
        $this->assertIsBool($thread2->has_attachments);
        $this->assertFalse($thread2->has_attachments);
    }

    public function test_thread_with_empty_to_cc_bcc_arrays(): void
    {
        $thread = Thread::factory()->create([
            'to' => [],
            'cc' => [],
            'bcc' => [],
        ]);

        $this->assertIsArray($thread->to);
        $this->assertIsArray($thread->cc);
        $this->assertIsArray($thread->bcc);
        $this->assertEmpty($thread->to);
        $this->assertEmpty($thread->cc);
        $this->assertEmpty($thread->bcc);
    }
}
