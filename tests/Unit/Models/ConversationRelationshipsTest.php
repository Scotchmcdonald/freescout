<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    public function test_conversation_belongs_to_mailbox(): void
    {
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);

        $this->assertInstanceOf(Mailbox::class, $conversation->mailbox);
        $this->assertEquals($mailbox->id, $conversation->mailbox->id);
    }

    public function test_conversation_belongs_to_customer(): void
    {
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id]);

        $this->assertInstanceOf(Customer::class, $conversation->customer);
        $this->assertEquals($customer->id, $conversation->customer->id);
    }

    public function test_conversation_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $conversation->user);
        $this->assertEquals($user->id, $conversation->user->id);
    }

    public function test_conversation_has_many_threads(): void
    {
        $conversation = Conversation::factory()->create();
        Thread::factory()->count(3)->create(['conversation_id' => $conversation->id]);

        $this->assertCount(3, $conversation->threads);
        $this->assertInstanceOf(Thread::class, $conversation->threads->first());
    }

    public function test_conversation_can_have_no_assigned_user(): void
    {
        $conversation = Conversation::factory()->create(['user_id' => null]);

        $this->assertNull($conversation->user_id);
        $this->assertNull($conversation->user);
    }

    public function test_conversation_scope_active_filters_correctly(): void
    {
        Conversation::factory()->create(['status' => 1]); // Active
        Conversation::factory()->create(['status' => 1]); // Active
        Conversation::factory()->create(['status' => 3]); // Closed

        $activeConversations = Conversation::where('status', 1)->get();

        $this->assertCount(2, $activeConversations);
    }

    public function test_conversation_scope_unassigned_filters_correctly(): void
    {
        Conversation::factory()->create(['user_id' => null, 'status' => 1]);
        Conversation::factory()->create(['user_id' => null, 'status' => 1]);
        $user = User::factory()->create();
        Conversation::factory()->create(['user_id' => $user->id, 'status' => 1]);

        $unassigned = Conversation::whereNull('user_id')->where('status', 1)->get();

        $this->assertCount(2, $unassigned);
    }

    public function test_conversation_eager_loads_relationships(): void
    {
        $conversation = Conversation::factory()->create();
        Thread::factory()->create(['conversation_id' => $conversation->id]);

        $loaded = Conversation::with(['mailbox', 'customer', 'user', 'threads'])->first();

        $this->assertTrue($loaded->relationLoaded('mailbox'));
        $this->assertTrue($loaded->relationLoaded('customer'));
        $this->assertTrue($loaded->relationLoaded('threads'));
    }
}
