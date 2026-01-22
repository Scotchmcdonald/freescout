<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\EntitlementResolver;
use App\Contracts\BillingTemplateInterface;
use App\DataTransferObjects\EntitlementResult;

/**
 * EntitlementEngine
 * 
 * Central registry for entitlement resolvers.
 * Routes billing templates to the appropriate resolver based on product type.
 * 
 * CRITICAL: This service is registered in PIB ServiceProvider.
 * All billing calculations go through this engine.
 * 
 * Usage:
 * $engine = app(EntitlementEngine::class);
 * $result = $engine->resolve($billingTemplate);
 */
class EntitlementEngine
{
    /**
     * @var array<string, EntitlementResolver>
     */
    private array $resolvers = [];

    /**
     * Register a resolver for a specific product type
     * 
     * @param string $productType Product type identifier (e.g., 'silver_plan', 'rent_to_own')
     * @param EntitlementResolver $resolver Resolver instance
     */
    public function registerResolver(string $productType, EntitlementResolver $resolver): void
    {
        $this->resolvers[$productType] = $resolver;
    }

    /**
     * Resolve entitlement for a billing template
     * 
     * @param BillingTemplateInterface $template Billing template to calculate
     * @return EntitlementResult Calculated entitlement result
     * @throws \Exception If no resolver registered for product type
     */
    public function resolve(BillingTemplateInterface $template): EntitlementResult
    {
        $productType = $template->getProductType();
        $resolver = $this->resolvers[$productType] ?? null;
        
        if (!$resolver) {
            throw new \Exception("No resolver registered for product type: {$productType}");
        }
        
        return $resolver->calculate($template);
    }

    /**
     * Check if a resolver is registered for a product type
     * 
     * @param string $productType Product type identifier
     * @return bool Whether resolver exists
     */
    public function hasResolver(string $productType): bool
    {
        return isset($this->resolvers[$productType]);
    }

    /**
     * Get all registered product types
     * 
     * @return array<string> List of registered product types
     */
    public function getRegisteredProductTypes(): array
    {
        return array_keys($this->resolvers);
    }
}
