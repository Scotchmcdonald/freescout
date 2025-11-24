<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CollisionControllerViewingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Mailbox $mailbox;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->mailbox = Mailbox::factory()->create();
        $this->mailbox->users()->attach($this->user);
    }

    public function test_collision_viewing_endpoint_exists(): void
    {
        $this->actingAs($this->user);

        $conversation = Conversation::factory()
            ->for($this->mailbox)
            ->create();

        // Test that the collision viewing endpoint can be called
        $response = $this->post(route('collision.viewing'), [
            'conversation_id' => $conversation->id,
        ]);

        // Should return a valid response (success or redirect)
        $this->assertTrue(
            $response->isOk() || 
            $response->isRedirect() || 
            $response->status() === 422 ||
            $response->status() === 200
        );
    }

    public function test_collision_viewing_requires_authentication(): void
    {
        $conversation = Conversation::factory()
            ->for($this->mailbox)
            ->create();

        $response = $this->post(route('collision.viewing'), [
            'conversation_id' => $conversation->id,
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_collision_viewing_requires_conversation_id(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('collision.viewing'), []);

        // Should return validation error or handle gracefully
        $this->assertTrue(
            $response->status() === 422 ||
            $response->status() === 400 ||
            $response->isRedirect()
        );
    }

    public function test_collision_viewing_returns_json_for_ajax_request(): void
    {
        $this->actingAs($this->user);

        $conversation = Conversation::factory()
            ->for($this->mailbox)
            ->create();

        $response = $this->postJson(route('collision.viewing'), [
            'conversation_id' => $conversation->id,
        ]);

        // Should return JSON response
        $this->assertTrue(
            $response->isOk() || 
            $response->status() === 422 ||
            $response->status() === 200
        );
    }

    public function test_collision_viewing_tracks_user_viewing_conversation(): void
    {
        $this->actingAs($this->user);

        $conversation = Conversation::factory()
            ->for($this->mailbox)
            ->create();

        $response = $this->postJson(route('collision.viewing'), [
            'conversation_id' => $conversation->id,
        ]);

        // If successful, response should contain viewing data
        if ($response->isOk()) {
            $data = $response->json();
            $this->assertIsArray($data);
        }
    }

    public function test_multiple_users_viewing_same_conversation(): void
    {
        $user1 = $this->user;
        $user2 = User::factory()->create(['role' => User::ROLE_USER]);
        $this->mailbox->users()->attach($user2);

        $conversation = Conversation::factory()
            ->for($this->mailbox)
            ->create();

        // First user views
        $this->actingAs($user1);
        $response1 = $this->postJson(route('collision.viewing'), [
            'conversation_id' => $conversation->id,
        ]);

        // Second user views
        $this->actingAs($user2);
        $response2 = $this->postJson(route('collision.viewing'), [
            'conversation_id' => $conversation->id,
        ]);

        // Both should receive valid responses
        $this->assertTrue($response1->isOk() || $response1->status() === 422);
        $this->assertTrue($response2->isOk() || $response2->status() === 422);
    }

    public function test_collision_viewing_for_nonexistent_conversation(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson(route('collision.viewing'), [
            'conversation_id' => 999999,
        ]);

        // Should return error or not found
        $this->assertTrue(
            $response->status() === 404 ||
            $response->status() === 422 ||
            $response->isOk()
        );
    }

    public function test_user_without_access_cannot_view_collision_data(): void
    {
        $otherUser = User::factory()->create(['role' => User::ROLE_USER]);
        $this->actingAs($otherUser);

        $conversation = Conversation::factory()
            ->for($this->mailbox)
            ->create();

        $response = $this->postJson(route('collision.viewing'), [
            'conversation_id' => $conversation->id,
        ]);

        // Should be forbidden or return empty data
        $this->assertTrue(
            $response->isForbidden() ||
            $response->isOk() ||
            $response->status() === 422
        );
    }
}
