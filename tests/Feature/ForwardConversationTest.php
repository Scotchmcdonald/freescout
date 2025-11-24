<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForwardConversationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_forward_conversation()
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $user->mailboxes()->attach($mailbox);

        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'subject' => 'Original Subject',
        ]);

        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'body' => 'Original Body',
            'from' => 'customer@example.com',
        ]);

        $response = $this->actingAs($user)
            ->post(route('conversations.forward', [$conversation, $thread]));

        $response->assertRedirect();
        
        $this->assertDatabaseHas('conversations', [
            'subject' => 'Fwd: Original Subject',
            'source_via' => 1, // User
            'state' => 1, // Draft
        ]);

        $newConversation = Conversation::where('subject', 'Fwd: Original Subject')->first();
        
        $this->assertDatabaseHas('threads', [
            'conversation_id' => $newConversation->id,
            'type' => 5, // Draft
        ]);
        
        $newThread = Thread::where('conversation_id', $newConversation->id)->first();
        $this->assertStringContainsString('Original Body', $newThread->body);
        $this->assertStringContainsString('Forwarded message', $newThread->body);
    }

    public function test_user_cannot_forward_conversation_without_access()
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        // User not attached to mailbox

        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
        ]);

        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
        ]);

        $response = $this->actingAs($user)
            ->post(route('conversations.forward', [$conversation, $thread]));

        $response->assertStatus(403);
    }
}
