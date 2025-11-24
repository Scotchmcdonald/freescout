<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CollisionDetectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_viewing_updates_cache_and_returns_viewers()
    {
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Attach users to mailbox
        $user1->mailboxes()->attach($mailbox->id);
        $user2->mailboxes()->attach($mailbox->id);

        // User 1 views the conversation
        $response1 = $this->actingAs($user1)->postJson(route('conversations.viewing', $conversation->id));
        $response1->assertStatus(200);
        $response1->assertJson([]); // No other viewers yet

        // User 2 views the conversation
        $response2 = $this->actingAs($user2)->postJson(route('conversations.viewing', $conversation->id));
        $response2->assertStatus(200);
        
        // User 2 should see User 1
        $response2->assertJsonFragment(['id' => $user1->id]);

        // User 1 views again
        $response3 = $this->actingAs($user1)->postJson(route('conversations.viewing', $conversation->id));
        $response3->assertStatus(200);
        
        // User 1 should see User 2
        $response3->assertJsonFragment(['id' => $user2->id]);
    }

    public function test_viewing_respects_permissions()
    {
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);
        $user = User::factory()->create(); // Not attached to mailbox

        $response = $this->actingAs($user)->postJson(route('conversations.viewing', $conversation->id));
        $response->assertStatus(403);
    }

    public function test_viewing_returns_404_for_invalid_conversation()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->postJson(route('conversations.viewing', 99999));
        $response->assertStatus(404);
    }
}
