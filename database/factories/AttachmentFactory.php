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
        $fileName = fake()->word().'.'.fake()->fileExtension();

        return [
            'thread_id' => Thread::factory(),
            'conversation_id' => null,
            'file_name' => $fileName,
            'file_dir' => 'attachments/'.fake()->uuid(),
            'file_size' => fake()->numberBetween(1024, 10485760), // 1KB to 10MB
            'mime_type' => fake()->mimeType(),
            'embedded' => false,
        ];
    }

    public function image(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_name' => fake()->word().'.jpg',
            'mime_type' => 'image/jpeg',
            'embedded' => fake()->boolean(),
        ]);
    }

    public function pdf(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_name' => fake()->word().'.pdf',
            'mime_type' => 'application/pdf',
        ]);
    }
}
