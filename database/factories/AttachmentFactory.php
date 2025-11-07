<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Attachment;
use App\Models\Thread;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Attachment>
 */
class AttachmentFactory extends Factory
{
    protected $model = Attachment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'thread_id' => Thread::factory(),
            'filename' => $this->faker->word . '.pdf',
            'mime_type' => 'application/pdf',
            'size' => $this->faker->numberBetween(1024, 5242880),
            'width' => null,
            'height' => null,
            'inline' => false,
            'public' => false,
            'data' => null,
            'url' => null,
        ];
    }

    /**
     * Indicate that the attachment is an image.
     */
    public function image(): static
    {
        return $this->state(fn (array $attributes) => [
            'filename' => $this->faker->word . '.jpg',
            'mime_type' => 'image/jpeg',
            'width' => 1920,
            'height' => 1080,
        ]);
    }

    /**
     * Indicate that the attachment is inline/embedded.
     */
    public function inline(): static
    {
        return $this->state(fn (array $attributes) => [
            'inline' => true,
        ]);
    }
}
