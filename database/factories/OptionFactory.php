<?php

namespace Database\Factories;

use App\Models\Option;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Option>
 */
class OptionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\App\Models\Option>
     */
    protected $model = Option::class;

    /**
     * Define the model's default state.
     *
     * @return array<'name'|'value', mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word,
            'value' => $this->faker->sentence,
        ];
    }
}
