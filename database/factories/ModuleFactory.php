<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Module;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Module>
 */
class ModuleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'alias' => fake()->unique()->slug(2),
            'name' => fake()->words(2, true),
            'active' => true,
            'version' => fake()->semver(),
            'description' => fake()->sentence(),
            'author' => fake()->name(),
            'settings' => [
                'enabled' => true,
                'config' => [
                    'option1' => fake()->word(),
                    'option2' => fake()->boolean(),
                ],
            ],
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }

    public function withoutSettings(): static
    {
        return $this->state(fn (array $attributes) => [
            'settings' => null,
        ]);
    }
}
