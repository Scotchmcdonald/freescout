<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Folder>
 */
class FolderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'mailbox_id' => Mailbox::factory(),
            'user_id' => null,
            'type' => 1, // Inbox
            'name' => 'Inbox',
            'total_count' => 0,
            'active_count' => 0,
            'meta' => null,
        ];
    }

    public function personal(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => User::factory(),
            'type' => 10, // Custom folder
            'name' => fake()->word(),
        ]);
    }
}
