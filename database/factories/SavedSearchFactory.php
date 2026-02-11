<?php

namespace Database\Factories;

use App\Models\SavedSearch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SavedSearch>
 */
class SavedSearchFactory extends Factory
{
    protected $model = SavedSearch::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'query' => $this->faker->word,
            'filters' => ['status' => 1],
            'is_default' => false,
        ];
    }
}
