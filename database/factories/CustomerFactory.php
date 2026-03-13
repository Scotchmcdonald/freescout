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
            'phones' => null,
            'websites' => null,
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
            // Create default email only if no emails exist
            // Note: Tests using withoutEmail() will have emails added separately
            if ($customer->emails()->count() === 0) {
                $customer->emails()->create([
                    'email' => fake()->unique()->safeEmail(),
                    'type' => 1, // TYPE_WORK
                ]);
            }
        });
    }

    /**
     * Skip automatic email creation - for tests that will create emails manually.
     * This returns a new factory that doesn't auto-create emails.
     */
    public function withoutEmail(): static
    {
        return $this->state([])->afterCreating(function (Customer $customer) {
            // Delete any auto-generated emails (from parent configure)
            $customer->emails()->delete();
        });
    }

    /**
     * Override create to handle email attribute properly.
     * This is called directly, so we can intercept the email attribute here.
     */
    public function create($attributes = [], ?\Illuminate\Database\Eloquent\Model $parent = null): \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection
    {
        // Extract email if present
        $email = null;
        if (is_array($attributes) && isset($attributes['email'])) {
            $email = $attributes['email'];
            unset($attributes['email']);
        }

        // Create the customer (this triggers afterCreating hook)
        $customer = parent::create($attributes, $parent);

        // If email was explicitly provided, replace any auto-generated email
        if ($email && $customer instanceof Customer) {
            $customer->emails()->delete();
            $customer->emails()->create([
                'email' => $email,
                'type' => 1, // TYPE_WORK (primary)
            ]);
        }

        return $customer;
    }

    /**
     * Create a customer with a specific email address (chainable method).
     */
    public function withEmail(string $email): static
    {
        return $this->afterCreating(function (Customer $customer) use ($email) {
            // Replace auto-generated email with specified one
            $customer->emails()->delete();
            $customer->emails()->create([
                'email' => $email,
                'type' => 1, // TYPE_WORK (primary)
            ]);
        });
    }

    public function withCompany(): static
    {
        return $this->state(fn (array $attributes) => [
            'company' => fake()->company(),
            'job_title' => fake()->jobTitle(),
        ]);
    }

    public function withMultipleEmails(int $count = 3): static
    {
        return $this->afterCreating(function (Customer $customer) use ($count) {
            // Create additional emails beyond the primary one
            for ($i = 1; $i < $count; $i++) {
                $customer->emails()->create([
                    'email' => fake()->unique()->safeEmail(),
                    'type' => fake()->randomElement([1, 2, 3]), // TYPE_WORK, TYPE_HOME, TYPE_OTHER
                ]);
            }
        });
    }

    public function withUnicodeName(): static
    {
        return $this->state(fn (array $attributes) => [
            'first_name' => '山田',
            'last_name' => '太郎',
        ]);
    }

    public function withEmoji(): static
    {
        return $this->state(fn (array $attributes) => [
            'first_name' => '😀 Happy',
            'last_name' => 'Customer',
        ]);
    }

    public function withChannels(int $count = 2): static
    {
        return $this->afterCreating(function (Customer $customer) {
            // This would attach to channels if Channel model exists
            // Placeholder for future implementation
        });
    }
}
