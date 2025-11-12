<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'company' => fake()->optional()->company(),
            'job_title' => fake()->optional()->jobTitle(),
            'photo_url' => null,
            'photo_type' => 1,
            'channel' => 1, // Email channel
            'channel_id' => null,
            'phones' => fake()->optional()->passthrough([
                ['type' => 'work', 'value' => fake()->phoneNumber()],
            ]),
            'websites' => fake()->optional()->passthrough([
                ['value' => fake()->url()],
            ]),
            'social_profiles' => null,
            'address' => fake()->optional()->streetAddress(),
            'city' => fake()->optional()->city(),
            'state' => fake()->optional()->lexify('??'),
            'zip' => fake()->optional()->postcode(),
            'country' => fake()->optional()->countryCode(),
            'notes' => fake()->optional()->paragraph(),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Customer $customer) {
            // Create a primary email for the customer (if not already created)
            if ($customer->emails()->count() === 0) {
                $customer->emails()->create([
                    'email' => fake()->unique()->safeEmail(),
                    'type' => 'work',
                ]);
            }
        });
    }

    public function create($attributes = [], ?\Illuminate\Database\Eloquent\Model $parent = null): \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection
    {
        // If email attribute is provided, extract it and create the email separately
        $emailAttribute = null;
        if (is_array($attributes) && isset($attributes['email'])) {
            $emailAttribute = $attributes['email'];
            unset($attributes['email']);
        }

        $customer = parent::create($attributes, $parent);

        // If email was provided, create it as a separate email record
        if ($emailAttribute && $customer instanceof Customer) {
            $customer->emails()->create([
                'email' => $emailAttribute,
                'type' => 'work',
            ]);
        }

        return $customer;
    }

    public function withCompany(): static
    {
        return $this->state(fn (array $attributes) => [
            'company' => fake()->company(),
            'job_title' => fake()->jobTitle(),
        ]);
    }
}
