<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'medium' => fake()->numberBetween(1, 3), // 1=Email, 2=Browser, 3=Mobile
            'event' => fake()->numberBetween(1, 10), // Various event types
        ];
    }

    public function email(): static
    {
        return $this->state(fn (array $attributes) => [
            'medium' => 1,
        ]);
    }

    public function browser(): static
    {
        return $this->state(fn (array $attributes) => [
            'medium' => 2,
        ]);
    }

    public function mobile(): static
    {
        return $this->state(fn (array $attributes) => [
            'medium' => 3,
        ]);
    }
}
