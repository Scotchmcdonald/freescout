<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Attachment;
use App\Models\Thread;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attachment>
 */
class AttachmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'thread_id' => Thread::factory(),
            'filename' => fake()->uuid() . '.pdf',
            'mime_type' => 'application/pdf',
            'size' => fake()->numberBetween(1024, 5242880), // 1KB to 5MB
            'inline' => false,
            'public' => false,
        ];
    }

    public function image(): static
    {
        return $this->state(fn (array $attributes) => [
            'filename' => fake()->uuid() . '.png',
            'mime_type' => 'image/png',
        ]);
    }
}
