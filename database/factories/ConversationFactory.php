<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Conversation>
 */
class ConversationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'number' => fake()->unique()->numberBetween(1000, 999999),
            'threads_count' => 0,
            'type' => 1, // Email
            'folder_id' => Folder::factory(),
            'mailbox_id' => Mailbox::factory(),
            'user_id' => User::factory(),
            'customer_id' => Customer::factory(),
            'status' => 1, // Active
            'state' => 2, // Published
            'subject' => fake()->sentence(),
            'customer_email' => fake()->email(),
            'cc' => null,
            'bcc' => null,
            'preview' => fake()->text(200),
            'imported' => false,
            'has_attachments' => false,
            'created_by_user_id' => null,
            'created_by_customer_id' => null,
            'source_via' => 1, // Email
            'source_type' => 1, // Email
            'channel' => 1, // Email
            'closed_by_user_id' => null,
            'closed_at' => null,
            'user_updated_at' => now(),
            'last_reply_at' => now(),
            'last_reply_from' => 1, // Customer
            'read_by_user' => false,
            'meta' => null,
        ];
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 3, // Closed
            'closed_by_user_id' => User::factory(),
            'closed_at' => now(),
        ]);
    }

    public function withAttachments(): static
    {
        return $this->state(fn (array $attributes) => [
            'has_attachments' => true,
        ]);
    }
}
