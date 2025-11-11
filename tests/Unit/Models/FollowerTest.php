<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Conversation;
use App\Models\Follower;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FollowerTest extends TestCase
{
    use RefreshDatabase;

    public function test_follower_belongs_to_conversation(): void
    {
        $conversation = Conversation::factory()->create();
        $user = User::factory()->create();
        $follower = Follower::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
        ]);

        $this->assertInstanceOf(Conversation::class, $follower->conversation);
        $this->assertEquals($conversation->id, $follower->conversation->id);
    }

    public function test_follower_belongs_to_user(): void
    {
        $conversation = Conversation::factory()->create();
        $user = User::factory()->create();
        $follower = Follower::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
        ]);

        $this->assertInstanceOf(User::class, $follower->user);
        $this->assertEquals($user->id, $follower->user->id);
    }

    public function test_follower_has_timestamps(): void
    {
        $conversation = Conversation::factory()->create();
        $user = User::factory()->create();
        $follower = Follower::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
        ]);

        $this->assertNotNull($follower->created_at);
        $this->assertNotNull($follower->updated_at);
    }
}
