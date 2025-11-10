<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'log_name' => fake()->randomElement(['default', 'user', 'conversation', 'mailbox']),
            'description' => fake()->sentence(),
            'subject_type' => null,
            'subject_id' => null,
            'causer_type' => null,
            'causer_id' => null,
            'properties' => [
                'attributes' => [
                    'key' => fake()->word(),
                    'value' => fake()->word(),
                ],
            ],
            'batch_uuid' => null,
        ];
    }

    public function causedBy(string $causerType, int $causerId): static
    {
        return $this->state(fn (array $attributes) => [
            'causer_type' => $causerType,
            'causer_id' => $causerId,
        ]);
    }

    public function forSubject(string $subjectType, int $subjectId): static
    {
        return $this->state(fn (array $attributes) => [
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
        ]);
    }
}
