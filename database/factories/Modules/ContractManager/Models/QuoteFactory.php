<?php

namespace Database\Factories\Modules\ContractManager\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\ContractManager\Models\Quote;
use Modules\Crm\Models\Client;

/**
 * Quote Factory
 * 
 * Generates test data for Quote models.
 *
 * @extends Factory<Quote>
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
            // 'date_issued' removed, likely sent_at in model
            'sent_at' => $this->faker->optional(0.7)->dateTimeBetween('-30 days', 'now'),
            'valid_until' => $this->faker->dateTimeBetween('now', '+90 days'),
            'billing_type' => $this->faker->randomElement(['monthly', 'quarterly', 'annual', 'one_time', 'usage_based']),
            'billing_cycle' => $this->faker->randomElement(['monthly', 'quarterly', 'semi_annual', 'annual', 'one_time']),
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
            'sent_at' => null,
        ]);
    }

    /**
     * Indicate that the quote has been sent to the client.
     */
    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    /**
     * Indicate that the quote has been approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'approved_at' => now(),
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
            'valid_until' => $this->faker->dateTimeBetween('-90 days', '-1 day'),
        ]);
    }
}
