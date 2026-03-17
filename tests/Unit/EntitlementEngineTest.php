<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Contracts\EntitlementResolver;
use App\DataTransferObjects\EntitlementResult;
use Modules\ContractManager\Models\BillingTemplate;
use Modules\PIB\Services\EntitlementEngineService;
use Tests\UnitTestCase;

/**
 * EntitlementEngineTest
 *
 * Tests the core EntitlementEngine resolver registry
 */
class EntitlementEngineTest extends UnitTestCase
{
    private EntitlementEngineService $engine;

    protected function setUp(): void
    {
        parent::setUp();

        $this->engine = new EntitlementEngineService;
    }

    public function test_can_register_resolver(): void
    {
        $resolver = $this->createMockResolver();

        $this->engine->registerResolver('test_product', $resolver);

        $this->assertTrue($this->engine->hasResolver('test_product'));
    }

    public function test_can_resolve_with_registered_resolver(): void
    {
        $resolver = $this->createMockResolver();
        $this->engine->registerResolver('test_product', $resolver);

        $template = $this->createMock(BillingTemplate::class);
        $template->method('getProductType')->willReturn('test_product');

        $result = $this->engine->resolve($template);

        $this->assertInstanceOf(EntitlementResult::class, $result);
    }

    public function test_throws_exception_for_unregistered_product_type(): void
    {
        $template = $this->createMock(BillingTemplate::class);
        $template->method('getProductType')->willReturn('unknown_product');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No resolver registered for product type: unknown_product');

        $this->engine->resolve($template);
    }

    public function test_has_resolver_returns_false_for_unregistered_type(): void
    {
        $this->assertFalse($this->engine->hasResolver('nonexistent'));
    }

    public function test_get_registered_product_types_returns_all_types(): void
    {
        $resolver1 = $this->createMockResolver();
        $resolver2 = $this->createMockResolver();

        $this->engine->registerResolver('product_a', $resolver1);
        $this->engine->registerResolver('product_b', $resolver2);

        $types = $this->engine->getRegisteredProductTypes();

        $this->assertCount(2, $types);
        $this->assertContains('product_a', $types);
        $this->assertContains('product_b', $types);
    }

    public function test_can_replace_resolver_for_same_product_type(): void
    {
        $resolver1 = $this->createMockResolver();
        $resolver2 = $this->createMockResolver();

        $this->engine->registerResolver('test_product', $resolver1);
        $this->engine->registerResolver('test_product', $resolver2);

        // Should still only have one registered type
        $types = $this->engine->getRegisteredProductTypes();
        $this->assertCount(1, $types);
        $this->assertTrue($this->engine->hasResolver('test_product'));
    }

    /**
     * Create a mock resolver for testing
     */
    private function createMockResolver(): EntitlementResolver
    {
        return new class implements EntitlementResolver
        {
            public function calculate(\App\Contracts\BillingTemplateInterface $template): EntitlementResult
            {
                return new EntitlementResult(
                    amount: 100.00,
                    quantity: 1,
                    breakdown: [
                        [
                            'description' => 'Mock Product',
                            'quantity' => 1,
                            'rate' => 100.00,
                            'amount' => 100.00,
                        ],
                    ],
                    hasReachedGoal: false
                );
            }
        };
    }
}
