<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DataTransferObjects\EntitlementResult;

/**
 * EntitlementResolver interface
 * 
 * Defines the contract for calculating entitlements for different product types.
 * Each product type (Silver Plan, Rent-To-Own, Ad-Hoc) implements this interface.
 */
interface EntitlementResolver
{
    /**
     * Calculate entitlement for a given billing template
     * 
     * @param BillingTemplateInterface $template The billing template to calculate for
     * @return EntitlementResult The calculated entitlement result
     */
    public function calculate(BillingTemplateInterface $template): EntitlementResult;
}
