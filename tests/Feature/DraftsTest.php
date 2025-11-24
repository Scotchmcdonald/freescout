<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DraftsTest extends TestCase
{
    use RefreshDatabase;

    public function test_save_draft()
    {
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);
        $user = User::factory()->create();
        $user->mailboxes()->attach($mailbox->id);

        $response = $this->actingAs($user)->postJson(route('drafts.save'), [
            'conversation_id' => $conversation->id,
            'body' => 'This is a draft',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('threads', [
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'type' => Thread::TYPE_DRAFT,
            'state' => Thread::STATE_DRAFT,
            'body' => 'This is a draft',
        ]);
    }

    public function test_discard_draft()
    {
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);
        $user = User::factory()->create();
        $user->mailboxes()->attach($mailbox->id);

        // Create a draft
        $thread = Thread::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'created_by_user_id' => $user->id,
            'type' => Thread::TYPE_DRAFT,
            'state' => Thread::STATE_DRAFT,
            'source_via' => 1,
            'source_type' => 2,
            'body' => 'Draft to discard',
        ]);

        $response = $this->actingAs($user)->postJson(route('drafts.discard'), [
            'thread_id' => $thread->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);

        $this->assertDatabaseMissing('threads', [
            'id' => $thread->id,
        ]);
    }

    public function test_show_conversation_injects_draft()
    {
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);
        $user = User::factory()->create();
        $user->mailboxes()->attach($mailbox->id);

        // Create a draft
        $draft = Thread::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'type' => Thread::TYPE_DRAFT,
            'state' => Thread::STATE_DRAFT,
            'source_via' => 1,
            'source_type' => 2,
            'body' => 'Injected draft',
        ]);

        $response = $this->actingAs($user)->get(route('conversations.show', $conversation));

        $response->assertStatus(200);
        $response->assertViewHas('draft');
        $this->assertEquals($draft->id, $response->viewData('draft')->id);
    }
}
