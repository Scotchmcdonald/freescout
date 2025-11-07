<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ActivityLog;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    protected $model = ActivityLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'log_name' => 'default',
            'description' => $this->faker->sentence,
            'subject_type' => Conversation::class,
            'subject_id' => Conversation::factory(),
            'causer_type' => User::class,
            'causer_id' => User::factory(),
            'properties' => null,
            'batch_uuid' => null,
        ];
    }
}
