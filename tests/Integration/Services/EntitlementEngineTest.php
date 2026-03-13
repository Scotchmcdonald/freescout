<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use App\Contracts\BillingTemplateInterface;
use App\Contracts\EntitlementResolver;
use App\DataTransferObjects\EntitlementResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\PIB\Services\EntitlementEngineService as EntitlementEngine;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * EntitlementEngine Integration Tests
 *
 * Tests the central billing calculation routing system.
 * The EntitlementEngine routes billing templates to appropriate
 * resolvers based on product type (silver_plan, rent_to_own, ad_hoc).
 *
 * Critical for:
 * - Accurate billing calculations
 * - Extensible product support
 * - Invoice generation
 */
#[Group('integration')]
#[Group('services')]
#[Group('entitlement')]
class EntitlementEngineTest extends TestCase
{
    use RefreshDatabase;

    private EntitlementEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new EntitlementEngine;
    }

    /**
     * Test resolver can be registered.
     */
    public function test_resolver_can_be_registered(): void
    {
        $resolver = $this->createMockResolver(100.00);

        $this->engine->registerResolver('test_product', $resolver);

        $this->assertTrue($this->engine->hasResolver('test_product'));
    }

    /**
     * Test resolve routes to correct resolver.
     */
    public function test_resolve_routes_to_correct_resolver(): void
    {
        $silverResolver = $this->createMockResolver(99.00);
        $adHocResolver = $this->createMockResolver(150.00);

        $this->engine->registerResolver('silver_plan', $silverResolver);
        $this->engine->registerResolver('ad_hoc', $adHocResolver);

        $silverTemplate = $this->createMockTemplate('silver_plan');
        $adHocTemplate = $this->createMockTemplate('ad_hoc');

        $silverResult = $this->engine->resolve($silverTemplate);
        $adHocResult = $this->engine->resolve($adHocTemplate);

        $this->assertEquals(99.00, $silverResult->amount);
        $this->assertEquals(150.00, $adHocResult->amount);
    }

    /**
     * Test throws exception for unregistered product type.
     */
    public function test_throws_for_unregistered_product_type(): void
    {
        $template = $this->createMockTemplate('unknown_product');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No resolver registered for product type: unknown_product');

        $this->engine->resolve($template);
    }

    /**
     * Test hasResolver returns false for unregistered type.
     */
    public function test_has_resolver_returns_false_for_unregistered(): void
    {
        $this->assertFalse($this->engine->hasResolver('nonexistent'));
    }

    /**
     * Test getRegisteredProductTypes returns all types.
     */
    public function test_get_registered_product_types(): void
    {
        $this->engine->registerResolver('silver_plan', $this->createMockResolver(99.00));
        $this->engine->registerResolver('rent_to_own', $this->createMockResolver(199.00));
        $this->engine->registerResolver('ad_hoc', $this->createMockResolver(50.00));

        $types = $this->engine->getRegisteredProductTypes();

        $this->assertCount(3, $types);
        $this->assertContains('silver_plan', $types);
        $this->assertContains('rent_to_own', $types);
        $this->assertContains('ad_hoc', $types);
    }

    /**
     * Test resolver can be replaced.
     */
    public function test_resolver_can_be_replaced(): void
    {
        $originalResolver = $this->createMockResolver(100.00);
        $newResolver = $this->createMockResolver(200.00);

        $this->engine->registerResolver('silver_plan', $originalResolver);
        $this->engine->registerResolver('silver_plan', $newResolver);

        $template = $this->createMockTemplate('silver_plan');
        $result = $this->engine->resolve($template);

        $this->assertEquals(200.00, $result->amount);
    }

    /**
     * Test resolver receives template correctly.
     */
    public function test_resolver_receives_template(): void
    {
        $receivedTemplate = null;

        $resolver = new class($receivedTemplate) implements EntitlementResolver
        {
            public function __construct(private &$receivedTemplate) {}

            public function calculate(BillingTemplateInterface $template): EntitlementResult
            {
                $this->receivedTemplate = $template;

                return new EntitlementResult(
                    amount: 100.00,
                    quantity: 1,
                    breakdown: []
                );
            }
        };

        $this->engine->registerResolver('test', $resolver);

        $template = $this->createMockTemplate('test');
        $this->engine->resolve($template);

        $this->assertSame($template, $receivedTemplate);
    }

    /**
     * Test EntitlementResult contains expected structure.
     */
    public function test_entitlement_result_structure(): void
    {
        $resolver = new class implements EntitlementResolver
        {
            public function calculate(BillingTemplateInterface $template): EntitlementResult
            {
                return new EntitlementResult(
                    amount: 299.99,
                    quantity: 5,
                    breakdown: [
                        ['description' => 'Base plan', 'quantity' => 1, 'rate' => 199.99, 'amount' => 199.99],
                        ['description' => 'Add-on service', 'quantity' => 2, 'rate' => 50.00, 'amount' => 100.00],
                    ]
                );
            }
        };

        $this->engine->registerResolver('complex_product', $resolver);

        $template = $this->createMockTemplate('complex_product');
        $result = $this->engine->resolve($template);

        $this->assertEquals(299.99, $result->amount);
        $this->assertEquals(5, $result->quantity);
        $this->assertCount(2, $result->breakdown);
        $this->assertEquals('Base plan', $result->breakdown[0]['description']);
    }

    /**
     * Test multiple resolvers can process in sequence.
     */
    public function test_multiple_templates_processed_correctly(): void
    {
        $callCount = 0;

        $resolver = new class($callCount) implements EntitlementResolver
        {
            public function __construct(private &$callCount) {}

            public function calculate(BillingTemplateInterface $template): EntitlementResult
            {
                $this->callCount++;

                return new EntitlementResult(
                    amount: 100.00 * $this->callCount,
                    quantity: $this->callCount,
                    breakdown: []
                );
            }
        };

        $this->engine->registerResolver('sequential', $resolver);

        $results = [];
        for ($i = 0; $i < 3; $i++) {
            $template = $this->createMockTemplate('sequential');
            $results[] = $this->engine->resolve($template);
        }

        $this->assertEquals(100.00, $results[0]->amount);
        $this->assertEquals(200.00, $results[1]->amount);
        $this->assertEquals(300.00, $results[2]->amount);
    }

    /**
     * Create a mock resolver that returns a fixed amount.
     */
    private function createMockResolver(float $amount): EntitlementResolver
    {
        return new class($amount) implements EntitlementResolver
        {
            public function __construct(private float $amount) {}

            public function calculate(BillingTemplateInterface $template): EntitlementResult
            {
                return new EntitlementResult(
                    amount: $this->amount,
                    quantity: 1,
                    breakdown: [['description' => 'Test item', 'quantity' => 1, 'rate' => $this->amount, 'amount' => $this->amount]]
                );
            }
        };
    }

    /**
     * Create a mock billing template.
     */
    private function createMockTemplate(string $productType): BillingTemplateInterface
    {
        return new class($productType) implements BillingTemplateInterface
        {
            public function __construct(private string $productType) {}

            public function getProductType(): string
            {
                return $this->productType;
            }
        };
    }
}
