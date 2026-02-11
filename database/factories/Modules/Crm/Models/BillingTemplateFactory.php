<?php

namespace Database\Factories\Modules\Crm\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\ContractManager\Models\BillingTemplate;

/**
 * Legacy stub — delegates to the canonical factory in ContractManager module.
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\ContractManager\Models\BillingTemplate>
 * @see \Modules\ContractManager\Database\Factories\BillingTemplateFactory
 */
class BillingTemplateFactory extends Factory
{
    protected $model = BillingTemplate::class;

    /**
     * Define the model's default state.
     */
    public function definition()
    {
        return [
            'client_id' => 1,
            'company_id' => 1,  // Required field
            'name' => $this->faker->words(3, true) . ' Plan',
            'product_type' => $this->faker->randomElement(['service_plan', 'hardware', 'software']),
            'product_config' => [],
            'billing_cycle' => $this->faker->randomElement(['monthly', 'quarterly', 'annual']),
            'next_invoice_date' => now()->addMonth(),
            'proration_enabled' => false,
            'status' => 'active',
            'activated_at' => now(),
        ];
    }
}
