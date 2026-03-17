<?php

declare(strict_types=1);

namespace Tests\Integration\Observers;

use App\Models\Conversation;
use App\Models\Thread;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThreadObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_increments_conversation_thread_count_when_thread_created()
    {
        $conversation = Conversation::factory()->create(['threads_count' => 0]);

        Thread::factory()->create(['conversation_id' => $conversation->id]);

        $this->assertEquals(1, $conversation->fresh()->threads_count);
    }

    public function test_it_increments_thread_count_multiple_times()
    {
        $conversation = Conversation::factory()->create(['threads_count' => 0]);

        Thread::factory()->create(['conversation_id' => $conversation->id]);
        Thread::factory()->create(['conversation_id' => $conversation->id]);
        Thread::factory()->create(['conversation_id' => $conversation->id]);

        $this->assertEquals(3, $conversation->fresh()->threads_count);
    }

    public function test_it_decrements_conversation_thread_count_when_thread_deleted()
    {
        $conversation = Conversation::factory()->create(['threads_count' => 5]);
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        // Should have incremented to 6
        $conversation->refresh();
        $this->assertEquals(6, $conversation->threads_count);

        $thread->delete();

        $this->assertEquals(5, $conversation->fresh()->threads_count);
    }

    public function test_it_handles_thread_count_with_existing_threads()
    {
        $conversation = Conversation::factory()->create(['threads_count' => 2]);

        // Create a new thread
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $this->assertEquals(3, $conversation->fresh()->threads_count);

        // Delete the thread
        $thread->delete();

        $this->assertEquals(2, $conversation->fresh()->threads_count);
    }

    public function test_it_only_affects_own_conversation_thread_count()
    {
        $conversation1 = Conversation::factory()->create(['threads_count' => 0]);
        $conversation2 = Conversation::factory()->create(['threads_count' => 0]);

        Thread::factory()->create(['conversation_id' => $conversation1->id]);
        Thread::factory()->create(['conversation_id' => $conversation2->id]);

        $conversation1->refresh();
        $conversation2->refresh();

        $this->assertEquals(1, $conversation1->threads_count);
        $this->assertEquals(1, $conversation2->threads_count);
    }
}
