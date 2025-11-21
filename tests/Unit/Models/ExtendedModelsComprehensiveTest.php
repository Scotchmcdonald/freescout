<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use Tests\UnitTestCase;
use App\Models\Follower;
use App\Models\User;
use App\Models\Conversation;

/**
 * Test Suite for Extended Models - Follower
 *
 * This test suite covers extended functionality models:
 * - Follower Model (25 tests) - Conversation following and notifications
 * Total: 25 tests
 *
 * These models handle additional features beyond core email processing.
 */
class ExtendedModelsComprehensiveTest extends UnitTestCase
{
    // ========================================
    // Follower Model Tests (25+ tests)
    // ========================================

    public function test_follower_has_conversation_relationship(): void
    {
        $conversation = Conversation::factory()->create();
        $follower = Follower::factory()->create(['conversation_id' => $conversation->id]);
        
        $this->assertEquals($conversation->id, $follower->conversation->id);
    }

    public function test_follower_has_user_relationship(): void
    {
        $user = User::factory()->create();
        $follower = Follower::factory()->create(['user_id' => $user->id]);
        
        $this->assertEquals($user->id, $follower->user->id);
    }

    public function test_follower_has_conversation_id_attribute(): void
    {
        $conversation = Conversation::factory()->create();
        $follower = Follower::factory()->create(['conversation_id' => $conversation->id]);
        
        $this->assertEquals($conversation->id, $follower->conversation_id);
    }

    public function test_follower_has_user_id_attribute(): void
    {
        $user = User::factory()->create();
        $follower = Follower::factory()->create(['user_id' => $user->id]);
        
        $this->assertEquals($user->id, $follower->user_id);
    }

    public function test_follower_unique_constraint_on_conversation_user(): void
    {
        $conversation = Conversation::factory()->create();
        $user = User::factory()->create();
        
        Follower::factory()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id
        ]);
        
        // Attempting to create duplicate should fail
        $this->expectException(\Exception::class);
        Follower::factory()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id
        ]);
    }

    public function test_follower_can_have_multiple_users_for_same_conversation(): void
    {
        $conversation = Conversation::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        Follower::factory()->create(['conversation_id' => $conversation->id, 'user_id' => $user1->id]);
        Follower::factory()->create(['conversation_id' => $conversation->id, 'user_id' => $user2->id]);
        
        $followers = Follower::where('conversation_id', $conversation->id)->get();
        
        $this->assertCount(2, $followers);
    }

    public function test_follower_can_have_user_following_multiple_conversations(): void
    {
        $conversation1 = Conversation::factory()->create();
        $conversation2 = Conversation::factory()->create();
        $user = User::factory()->create();
        
        Follower::factory()->create(['conversation_id' => $conversation1->id, 'user_id' => $user->id]);
        Follower::factory()->create(['conversation_id' => $conversation2->id, 'user_id' => $user->id]);
        
        $followers = Follower::where('user_id', $user->id)->get();
        
        $this->assertCount(2, $followers);
    }

    public function test_follower_has_created_at_timestamp(): void
    {
        $follower = Follower::factory()->create();
        $this->assertNotNull($follower->created_at);
    }

    public function test_follower_has_updated_at_timestamp(): void
    {
        $follower = Follower::factory()->create();
        $this->assertNotNull($follower->updated_at);
    }

    public function test_follower_can_be_deleted(): void
    {
        $follower = Follower::factory()->create();
        $id = $follower->id;
        
        $follower->delete();
        
        $this->assertNull(Follower::find($id));
    }

    public function test_follower_is_deleted_when_conversation_is_deleted(): void
    {
        $conversation = Conversation::factory()->create();
        $follower = Follower::factory()->create(['conversation_id' => $conversation->id]);
        $followerId = $follower->id;
        
        $conversation->delete();
        
        $this->assertNull(Follower::find($followerId));
    }

    public function test_follower_is_deleted_when_user_is_deleted(): void
    {
        $user = User::factory()->create();
        $follower = Follower::factory()->create(['user_id' => $user->id]);
        $followerId = $follower->id;
        
        $user->delete();
        
        $this->assertNull(Follower::find($followerId));
    }

    public function test_follower_conversation_id_cannot_be_null(): void
    {
        $this->expectException(\Exception::class);
        Follower::factory()->create(['conversation_id' => null]);
    }

    public function test_follower_user_id_cannot_be_null(): void
    {
        $this->expectException(\Exception::class);
        Follower::factory()->create(['user_id' => null]);
    }

    public function test_follower_can_check_if_user_is_following_conversation(): void
    {
        $conversation = Conversation::factory()->create();
        $user = User::factory()->create();
        Follower::factory()->create(['conversation_id' => $conversation->id, 'user_id' => $user->id]);
        
        $isFollowing = Follower::where('conversation_id', $conversation->id)
            ->where('user_id', $user->id)
            ->exists();
        
        $this->assertTrue($isFollowing);
    }

    public function test_follower_returns_false_when_user_not_following(): void
    {
        $conversation = Conversation::factory()->create();
        $user = User::factory()->create();
        
        $isFollowing = Follower::where('conversation_id', $conversation->id)
            ->where('user_id', $user->id)
            ->exists();
        
        $this->assertFalse($isFollowing);
    }

    public function test_follower_can_get_all_followers_for_conversation(): void
    {
        $conversation = Conversation::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        Follower::factory()->create(['conversation_id' => $conversation->id, 'user_id' => $user1->id]);
        Follower::factory()->create(['conversation_id' => $conversation->id, 'user_id' => $user2->id]);
        
        $followers = Follower::where('conversation_id', $conversation->id)->get();
        
        $this->assertCount(2, $followers);
    }

    public function test_follower_can_get_all_conversations_user_is_following(): void
    {
        $user = User::factory()->create();
        $conversation1 = Conversation::factory()->create();
        $conversation2 = Conversation::factory()->create();
        
        Follower::factory()->create(['user_id' => $user->id, 'conversation_id' => $conversation1->id]);
        Follower::factory()->create(['user_id' => $user->id, 'conversation_id' => $conversation2->id]);
        
        $following = Follower::where('user_id', $user->id)->get();
        
        $this->assertCount(2, $following);
    }

    public function test_follower_model_has_table_name(): void
    {
        $follower = new Follower();
        $this->assertEquals('followers', $follower->getTable());
    }

    public function test_follower_has_fillable_attributes(): void
    {
        $follower = new Follower();
        $fillable = $follower->getFillable();
        
        $this->assertContains('conversation_id', $fillable);
        $this->assertContains('user_id', $fillable);
    }

    public function test_follower_mass_assignment_protection(): void
    {
        $follower = Follower::factory()->create();
        
        // Attempting to mass assign non-fillable attributes should not work
        $follower->fill(['id' => 999]);
        
        $this->assertNotEquals(999, $follower->id);
    }

    public function test_follower_can_be_created_with_factory(): void
    {
        $follower = Follower::factory()->create();
        
        $this->assertInstanceOf(Follower::class, $follower);
        $this->assertNotNull($follower->id);
    }

    public function test_follower_can_unfollow_conversation(): void
    {
        $conversation = Conversation::factory()->create();
        $user = User::factory()->create();
        $follower = Follower::factory()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id
        ]);
        
        $follower->delete();
        
        $isFollowing = Follower::where('conversation_id', $conversation->id)
            ->where('user_id', $user->id)
            ->exists();
        
        $this->assertFalse($isFollowing);
    }

    public function test_follower_eager_loading_user(): void
    {
        $follower = Follower::factory()->create();
        
        $loaded = Follower::with('user')->find($follower->id);
        
        $this->assertTrue($loaded->relationLoaded('user'));
    }

    public function test_follower_eager_loading_conversation(): void
    {
        $follower = Follower::factory()->create();
        
        $loaded = Follower::with('conversation')->find($follower->id);
        
        $this->assertTrue($loaded->relationLoaded('conversation'));
    }
}