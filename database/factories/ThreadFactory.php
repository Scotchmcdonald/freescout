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
            'user_id' => null, // Don't auto-create - tests should specify
            'customer_id' => null, // Don't auto-create - tests should specify
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
            'message_id' => fake()->uuid().'@example.com',
            'has_attachments' => false,
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

    public function customerMessage(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 4, // Customer message
            'user_id' => null,
            'customer_id' => Customer::factory(),
        ]);
    }

    public function userReply(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 1, // Message/Reply
            'user_id' => User::factory(),
            'customer_id' => null,
        ]);
    }

    public function withLargeBody(): static
    {
        return $this->state(fn (array $attributes) => [
            'body' => fake()->paragraphs(50, true), // ~5KB of text
        ]);
    }

    public function withHtmlBody(): static
    {
        return $this->state(function (array $attributes) {
            $paragraphs = fake()->paragraphs(5, true);
            $body = is_array($paragraphs) ? implode("\n", $paragraphs) : $paragraphs;
            return [
                'body' => '<html><body><h1>Test Email</h1>' . $body . '</body></html>',
            ];
        });
    }

    public function withAttachments(int $count = 2): static
    {
        return $this->state(fn (array $attributes) => [
            'has_attachments' => true,
        ])->has(
            \App\Models\Attachment::factory()->count($count)
        );
    }
}
