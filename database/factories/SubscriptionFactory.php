<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subscription>
 */
class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'medium' => $this->faker->numberBetween(1, 3),
            'event' => $this->faker->numberBetween(1, 10),
        ];
    }

    /**
     * Indicate that this is an email subscription.
     */
    public function email(): static
    {
        return $this->state(fn (array $attributes) => [
            'medium' => 1,
        ]);
    }

    /**
     * Indicate that this is a browser subscription.
     */
    public function browser(): static
    {
        return $this->state(fn (array $attributes) => [
            'medium' => 2,
        ]);
    }

    /**
     * Indicate that this is a mobile subscription.
     */
    public function mobile(): static
    {
        return $this->state(fn (array $attributes) => [
            'medium' => 3,
        ]);
    }
}
