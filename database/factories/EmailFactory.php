<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Email;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Email>
 */
class EmailFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'email' => fake()->unique()->safeEmail(),
            'type' => 1, // Primary
        ];
    }

    public function secondary(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 2, // Secondary
        ]);
    }
}
