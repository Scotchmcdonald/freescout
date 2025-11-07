<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationModelRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function conversation_belongs_to_customer(): void
    {
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()
            ->for($customer)
            ->create();

        $this->assertInstanceOf(Customer::class, $conversation->customer);
        $this->assertEquals($customer->id, $conversation->customer->id);
    }

    /** @test */
    public function conversation_belongs_to_mailbox(): void
    {
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()
            ->for($mailbox)
            ->create();

        $this->assertInstanceOf(Mailbox::class, $conversation->mailbox);
        $this->assertEquals($mailbox->id, $conversation->mailbox->id);
    }

    /** @test */
    public function conversation_has_many_threads(): void
    {
        $conversation = Conversation::factory()->create();
        
        $thread1 = Thread::factory()->create([
            'conversation_id' => $conversation->id,
        ]);
        
        $thread2 = Thread::factory()->create([
            'conversation_id' => $conversation->id,
        ]);

        $threads = $conversation->threads;
        
        $this->assertCount(2, $threads);
        $this->assertTrue($threads->contains($thread1));
        $this->assertTrue($threads->contains($thread2));
    }

    /** @test */
    public function conversation_belongs_to_assigned_user(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()
            ->for($user, 'user')
            ->create();

        $this->assertInstanceOf(User::class, $conversation->user);
        $this->assertEquals($user->id, $conversation->user->id);
    }

    /** @test */
    public function conversation_can_have_null_assigned_user(): void
    {
        $conversation = Conversation::factory()->create([
            'user_id' => null,
        ]);

        $this->assertNull($conversation->user_id);
        $this->assertNull($conversation->user);
    }

    /** @test */
    public function conversation_belongs_to_folder(): void
    {
        $folder = Folder::factory()->create();
        $conversation = Conversation::factory()
            ->for($folder)
            ->create();

        $this->assertInstanceOf(Folder::class, $conversation->folder);
        $this->assertEquals($folder->id, $conversation->folder->id);
    }

    /** @test */
    public function conversation_belongs_to_created_by_user(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create([
            'created_by_user_id' => $user->id,
        ]);

        $this->assertInstanceOf(User::class, $conversation->createdByUser);
        $this->assertEquals($user->id, $conversation->createdByUser->id);
    }

    /** @test */
    public function conversation_belongs_to_closed_by_user(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create([
            'closed_by_user_id' => $user->id,
            'closed_at' => now(),
            'status' => Conversation::STATUS_CLOSED,
        ]);

        $this->assertInstanceOf(User::class, $conversation->closedByUser);
        $this->assertEquals($user->id, $conversation->closedByUser->id);
    }
}
