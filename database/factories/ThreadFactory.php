<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Thread>
 */
class ThreadFactory extends Factory
{
    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'user_id' => User::factory(),
            'customer_id' => null,
            'type' => 1, // Message
            'status' => 2, // Active
            'state' => 2, // Published
            'action_type' => 1, // Reply
            'source_via' => 1, // Email
            'source_type' => 1, // Email
            'body' => fake()->paragraphs(3, true),
            'to' => [fake()->email()],
            'cc' => null,
            'bcc' => null,
            'from' => fake()->email(),
            'headers' => null,
            'message_id' => fake()->uuid() . '@example.com',
            'opened_at' => null,
            'meta' => null,
        ];
    }

    public function fromCustomer(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
            'customer_id' => Customer::factory(),
            'type' => 4, // Customer message
        ]);
    }

    public function note(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 2, // Note
            'to' => null,
            'from' => null,
        ]);
    }
}
