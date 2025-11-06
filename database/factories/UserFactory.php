<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password = null;

    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            // Use lower bcrypt rounds in testing for speed (default is 12)
            'password' => static::$password ??= Hash::make('password', ['rounds' => 4]),
            'remember_token' => Str::random(10),
            'role' => 1, // User role
            'timezone' => fake()->randomElement(['America/New_York', 'America/Chicago', 'America/Denver', 'America/Los_Angeles', 'UTC']),
            'photo_url' => null,
            'type' => 1, // User type
            'status' => 1, // Active
            'invite_state' => 1, // Activated
            'locale' => 'en',
            'job_title' => fake()->jobTitle(),
            'phone' => fake()->phoneNumber(),
            'time_format' => 12,
            'enable_kb_shortcuts' => true,
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 2, // Admin role
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 2, // Inactive
        ]);
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
