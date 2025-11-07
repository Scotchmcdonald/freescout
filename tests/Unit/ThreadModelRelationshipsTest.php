<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Attachment;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThreadModelRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function thread_belongs_to_conversation(): void
    {
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
        ]);

        $this->assertInstanceOf(Conversation::class, $thread->conversation);
        $this->assertEquals($conversation->id, $thread->conversation->id);
    }

    /** @test */
    public function thread_belongs_to_created_by_user(): void
    {
        $user = User::factory()->create();
        $thread = Thread::factory()->create([
            'created_by_user_id' => $user->id,
        ]);

        $this->assertInstanceOf(User::class, $thread->createdByUser);
        $this->assertEquals($user->id, $thread->createdByUser->id);
    }

    /** @test */
    public function thread_belongs_to_customer(): void
    {
        $customer = Customer::factory()->create();
        $thread = Thread::factory()->create([
            'created_by_customer_id' => $customer->id,
            'customer_id' => $customer->id,
        ]);

        $this->assertInstanceOf(Customer::class, $thread->customer);
        $this->assertEquals($customer->id, $thread->customer->id);
    }

    /** @test */
    public function thread_belongs_to_edited_by_user(): void
    {
        $user = User::factory()->create();
        $thread = Thread::factory()->create([
            'edited_by_user_id' => $user->id,
            'edited_at' => now(),
        ]);

        $this->assertInstanceOf(User::class, $thread->editedByUser);
        $this->assertEquals($user->id, $thread->editedByUser->id);
    }

    /** @test */
    public function thread_has_many_attachments(): void
    {
        $thread = Thread::factory()->create();
        
        $attachment1 = Attachment::factory()->create([
            'thread_id' => $thread->id,
        ]);
        
        $attachment2 = Attachment::factory()->create([
            'thread_id' => $thread->id,
        ]);

        $attachments = $thread->attachments;
        
        $this->assertCount(2, $attachments);
        $this->assertTrue($attachments->contains($attachment1));
        $this->assertTrue($attachments->contains($attachment2));
    }

    /** @test */
    public function thread_is_customer_message_returns_true_for_customer_type(): void
    {
        $thread = Thread::factory()->create([
            'type' => 4, // Customer message type
        ]);

        $this->assertTrue($thread->isCustomerMessage());
    }

    /** @test */
    public function thread_is_customer_message_returns_false_for_user_type(): void
    {
        $thread = Thread::factory()->create([
            'type' => 1, // User message type
        ]);

        $this->assertFalse($thread->isCustomerMessage());
    }

    /** @test */
    public function thread_is_user_message_returns_true_for_user_type(): void
    {
        $thread = Thread::factory()->create([
            'type' => 1, // User message type
        ]);

        $this->assertTrue($thread->isUserMessage());
    }

    /** @test */
    public function thread_is_user_message_returns_false_for_customer_type(): void
    {
        $thread = Thread::factory()->create([
            'type' => 4, // Customer message type
        ]);

        $this->assertFalse($thread->isUserMessage());
    }

    /** @test */
    public function thread_is_note_returns_true_for_note_type(): void
    {
        $thread = Thread::factory()->note()->create();

        $this->assertTrue($thread->isNote());
    }

    /** @test */
    public function thread_is_note_returns_false_for_message_type(): void
    {
        $thread = Thread::factory()->create([
            'type' => 1, // Message type
        ]);

        $this->assertFalse($thread->isNote());
    }
}
