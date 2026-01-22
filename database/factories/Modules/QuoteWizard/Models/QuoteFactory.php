<?php

namespace Database\Factories\Modules\QuoteWizard\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\QuoteWizard\Models\Quote;
use Modules\Crm\Models\Client;

/**
 * Quote Factory
 * 
 * Generates test data for Quote models.
 */
class QuoteFactory extends Factory
{
    protected $model = Quote::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $subtotal = $this->faker->randomFloat(2, 100, 10000);
        $taxRate = 0.10; // 10% tax
        $taxAmount = round($subtotal * $taxRate, 2);
        $total = $subtotal + $taxAmount;

        return [
            'client_id' => Client::factory(),
            'quote_number' => 'Q-' . $this->faker->unique()->numberBetween(1000, 99999),
            'title' => $this->faker->sentence(3),
            'status' => $this->faker->randomElement(['draft', 'sent', 'approved', 'rejected', 'expired']),
            'date_issued' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'date_valid_until' => $this->faker->dateTimeBetween('now', '+90 days'),
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $total,
            'notes' => $this->faker->optional()->paragraph(),
            'terms' => $this->faker->optional()->paragraph(),
            'created_by' => null, // Can be overridden with User factory
        ];
    }

    /**
     * Indicate that the quote is a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }

    /**
     * Indicate that the quote has been sent to the client.
     */
    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'sent',
            'date_issued' => now(),
        ]);
    }

    /**
     * Indicate that the quote has been approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
        ]);
    }

    /**
     * Indicate that the quote has been rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
        ]);
    }

    /**
     * Indicate that the quote has expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'expired',
            'date_valid_until' => $this->faker->dateTimeBetween('-90 days', '-1 day'),
        ]);
    }
}
