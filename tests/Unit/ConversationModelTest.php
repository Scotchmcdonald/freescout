<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Conversation;
use App\Models\Folder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_conversation_status_constants(): void
    {
        $this->assertEquals(1, Conversation::STATUS_ACTIVE);
        $this->assertEquals(2, Conversation::STATUS_PENDING);
        $this->assertEquals(3, Conversation::STATUS_CLOSED);
        $this->assertEquals(4, Conversation::STATUS_SPAM);
    }

    public function test_conversation_state_constants(): void
    {
        $this->assertEquals(1, Conversation::STATE_DRAFT);
        $this->assertEquals(2, Conversation::STATE_PUBLISHED);
    }

    public function test_conversation_has_number(): void
    {
        $conversation = new Conversation(['number' => 12345]);
        $this->assertEquals(12345, $conversation->number);
    }

    public function test_conversation_has_subject(): void
    {
        $conversation = new Conversation(['subject' => 'Test Subject']);
        $this->assertEquals('Test Subject', $conversation->subject);
    }

    public function test_conversation_has_status(): void
    {
        $conversation = new Conversation(['status' => Conversation::STATUS_ACTIVE]);
        $this->assertEquals(Conversation::STATUS_ACTIVE, $conversation->status);
    }

    public function test_conversation_has_mailbox_id(): void
    {
        $conversation = new Conversation(['mailbox_id' => 123]);
        $this->assertEquals(123, $conversation->mailbox_id);
    }

    public function test_conversation_has_customer_id(): void
    {
        $conversation = new Conversation(['customer_id' => 456]);
        $this->assertEquals(456, $conversation->customer_id);
    }

    public function test_conversation_has_user_id(): void
    {
        $conversation = new Conversation(['user_id' => 789]);
        $this->assertEquals(789, $conversation->user_id);
    }

    public function test_conversation_has_threads_count(): void
    {
        $conversation = new Conversation(['threads_count' => 5]);
        $this->assertEquals(5, $conversation->threads_count);
    }

    public function test_conversation_has_preview(): void
    {
        $conversation = new Conversation(['preview' => 'Message preview']);
        $this->assertEquals('Message preview', $conversation->preview);
    }

    public function test_conversation_has_last_reply_at(): void
    {
        $date = now();
        $conversation = new Conversation(['last_reply_at' => $date]);
        $this->assertEquals($date->toDateTimeString(), $conversation->last_reply_at->toDateTimeString());
    }

    public function test_conversation_has_closed_at(): void
    {
        $date = now();
        $conversation = new Conversation(['closed_at' => $date]);
        $this->assertEquals($date->toDateTimeString(), $conversation->closed_at->toDateTimeString());
    }

    public function test_is_active_returns_true_for_active_status(): void
    {
        $conversation = Conversation::factory()->make(['status' => Conversation::STATUS_ACTIVE]);

        $this->assertTrue($conversation->isActive());
    }

    public function test_is_active_returns_false_for_non_active_status(): void
    {
        $conversation = Conversation::factory()->make(['status' => Conversation::STATUS_CLOSED]);
        $this->assertFalse($conversation->isActive());

        $conversation = Conversation::factory()->make(['status' => Conversation::STATUS_PENDING]);
        $this->assertFalse($conversation->isActive());
    }

    public function test_is_closed_returns_true_for_closed_status(): void
    {
        $conversation = Conversation::factory()->make(['status' => Conversation::STATUS_CLOSED]);

        $this->assertTrue($conversation->isClosed());
    }

    public function test_is_closed_returns_false_for_non_closed_status(): void
    {
        $conversation = Conversation::factory()->make(['status' => Conversation::STATUS_ACTIVE]);
        $this->assertFalse($conversation->isClosed());

        $conversation = Conversation::factory()->make(['status' => Conversation::STATUS_PENDING]);
        $this->assertFalse($conversation->isClosed());
    }

    public function test_cc_and_bcc_cast_to_json(): void
    {
        $cc = ['cc1@example.com', 'cc2@example.com'];
        $bcc = ['bcc1@example.com'];
        
        $conversation = Conversation::factory()->create([
            'cc' => $cc,
            'bcc' => $bcc,
        ]);

        $this->assertIsArray($conversation->cc);
        $this->assertIsArray($conversation->bcc);
        $this->assertEquals($cc, $conversation->cc);
        $this->assertEquals($bcc, $conversation->bcc);
    }

    public function test_imported_and_has_attachments_cast_to_boolean(): void
    {
        $conversation = Conversation::factory()->create([
            'imported' => true,
            'has_attachments' => false,
        ]);

        $this->assertIsBool($conversation->imported);
        $this->assertIsBool($conversation->has_attachments);
        $this->assertTrue($conversation->imported);
        $this->assertFalse($conversation->has_attachments);
    }
}
