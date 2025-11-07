<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Customer;
use App\Models\SendLog;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SendLog>
 */
class SendLogFactory extends Factory
{
    protected $model = SendLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'thread_id' => Thread::factory(),
            'customer_id' => Customer::factory(),
            'user_id' => null,
            'message_id' => $this->faker->uuid . '@example.com',
            'email' => $this->faker->safeEmail,
            'subject' => $this->faker->sentence,
            'mail_type' => 1,
            'status' => 1,
            'status_message' => null,
            'smtp_queue_id' => null,
        ];
    }

    /**
     * Indicate that the email was sent successfully.
     */
    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 1,
            'status_message' => 'Sent successfully',
        ]);
    }

    /**
     * Indicate that the email failed to send.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 2,
            'status_message' => 'Send failed',
        ]);
    }


}
