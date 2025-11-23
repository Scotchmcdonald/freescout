<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\Follower;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Follower>
 */
class FollowerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\App\Models\Follower>
     */
    protected $model = Follower::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    // @phpstan-ignore-next-line
    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'user_id' => User::factory(),
        ];
    }
}
