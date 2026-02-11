<?php

namespace Tests\Feature\Conversation;

use App\Models\Conversation;
use App\Models\Mailbox;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);
    $this->mailbox = Mailbox::factory()->create();
    $this->mailbox->users()->attach($this->user);
});

test('viewing updates cache and returns viewers', function () {
    $conversation = Conversation::factory()->create(['mailbox_id' => $this->mailbox->id]);
    $user2 = User::factory()->create();
    $user2->mailboxes()->attach($this->mailbox->id);

    // User 1 views the conversation
    $response1 = $this->actingAs($this->user)->postJson(route('collision.viewing', $conversation->id));
    $response1->assertStatus(200);
    $response1->assertJson([]); // No other viewers yet

    // User 2 views the conversation
    $response2 = $this->actingAs($user2)->postJson(route('collision.viewing', $conversation->id));
    $response2->assertStatus(200);
    
    // User 2 should see User 1
    // The response structure typically contains an array of viewers or similar
    // We check for fragment
    $response2->assertJsonFragment(['id' => $this->user->id]);

    // User 1 views again
    $response3 = $this->actingAs($this->user)->postJson(route('collision.viewing', $conversation->id));
    $response3->assertStatus(200);
    
    // User 1 should see User 2
    $response3->assertJsonFragment(['id' => $user2->id]);
});

test('viewing respects permissions', function () {
    $conversation = Conversation::factory()->create(['mailbox_id' => $this->mailbox->id]);
    $user = User::factory()->create(); // Not attached to mailbox

    $response = $this->actingAs($user)->postJson(route('collision.viewing', $conversation->id));
    $response->assertForbidden();
});

test('viewing returns 404 for invalid conversation', function () {
    $response = $this->actingAs($this->user)->postJson(route('collision.viewing', 99999));
    $response->assertNotFound();
});

test('collision viewing requires authentication', function () {
    $conversation = Conversation::factory()
        ->for($this->mailbox)
        ->create();

    $response = $this->post(route('collision.viewing', ['id' => $conversation->id]), [
        'conversation_id' => $conversation->id,
    ]);

    $response->assertRedirect(route('login'));
});
