<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * BillingTemplateInterface
 * 
 * Contract for billing templates that can be resolved by EntitlementEngine.
 * This allows core services to work with billing templates without depending
 * on specific module implementations (Core Blindness principle).
 */
interface BillingTemplateInterface
{
    /**
     * Get the product type for this billing template
     * 
     * @return string Product type identifier (e.g., 'silver_plan', 'rent_to_own')
     */
    public function getProductType(): string;
}
