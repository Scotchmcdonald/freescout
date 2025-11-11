<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Customer;
use App\Models\SendLog;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SendLog>
 */
class SendLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'thread_id' => Thread::factory(),
            'customer_id' => null,
            'user_id' => null,
            'message_id' => fake()->uuid().'@example.com',
            'email' => fake()->safeEmail(),
            'status' => 1, // Sent
            'status_message' => null,
            'opens' => 0,
            'clicks' => 0,
            'opened_at' => null,
            'clicked_at' => null,
            'meta' => null,
        ];
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 2, // Failed
            'status_message' => fake()->sentence(),
        ]);
    }

    public function opened(): static
    {
        return $this->state(fn (array $attributes) => [
            'opens' => fake()->numberBetween(1, 10),
            'opened_at' => now()->subDays(fake()->numberBetween(1, 30)),
        ]);
    }

    public function clicked(): static
    {
        return $this->state(fn (array $attributes) => [
            'clicks' => fake()->numberBetween(1, 5),
            'clicked_at' => now()->subDays(fake()->numberBetween(1, 30)),
        ]);
    }

    public function forCustomer(): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_id' => Customer::factory(),
        ]);
    }

    public function forUser(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => User::factory(),
        ]);
    }
}
