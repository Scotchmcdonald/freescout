<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use App\Contracts\BillingTemplateInterface;
use App\Contracts\EntitlementResolver;
use App\DataTransferObjects\EntitlementResult;
use Modules\PIB\Services\EntitlementEngineService;
use Tests\IntegrationTestCase;

class EntitlementEngineServiceTest extends IntegrationTestCase
{
    public function test_register_resolver_stores_resolver(): void
    {
        $engine = new EntitlementEngineService();
        $resolver = $this->createMockResolver();

        $engine->registerResolver('test_product', $resolver);

        $this->assertTrue($engine->hasResolver('test_product'));
    }

    public function test_register_multiple_resolvers(): void
    {
        $engine = new EntitlementEngineService();
        $resolver1 = $this->createMockResolver();
        $resolver2 = $this->createMockResolver();
        $resolver3 = $this->createMockResolver();

        $engine->registerResolver('product_a', $resolver1);
        $engine->registerResolver('product_b', $resolver2);
        $engine->registerResolver('product_c', $resolver3);

        $this->assertTrue($engine->hasResolver('product_a'));
        $this->assertTrue($engine->hasResolver('product_b'));
        $this->assertTrue($engine->hasResolver('product_c'));
    }

    public function test_register_resolver_overwrites_existing(): void
    {
        $engine = new EntitlementEngineService();
        $oldResult = new \App\DataTransferObjects\EntitlementResult(
            amount: 100.0,
            quantity: 1,
            breakdown: [['description' => 'Old', 'quantity' => 1, 'rate' => 100.0, 'amount' => 100.0, 'cost' => null]]
        );
        $newResult = new \App\DataTransferObjects\EntitlementResult(
            amount: 200.0,
            quantity: 1,
            breakdown: [['description' => 'New', 'quantity' => 1, 'rate' => 200.0, 'amount' => 200.0, 'cost' => null]]
        );

        $old = $this->createMockResolver(
            $this->createMockTemplate('product_x'),
            $oldResult
        );
        $new = $this->createMockResolver(
            $this->createMockTemplate('product_x'),
            $newResult
        );

        $engine->registerResolver('product_x', $old);
        $engine->registerResolver('product_x', $new);

        $template = $this->createMockTemplate('product_x');
        $result = $engine->resolve($template);

        $this->assertEquals(200.0, $result->amount);
    }

    public function test_resolve_calls_registered_resolver(): void
    {
        $engine = new EntitlementEngineService();
        $template = $this->createMockTemplate('silver_plan');
        $expectedResult = new \App\DataTransferObjects\EntitlementResult(
            amount: 99.99,
            quantity: 5,
            breakdown: [['description' => 'Silver', 'quantity' => 5, 'rate' => 99.99, 'amount' => 99.99, 'cost' => null]]
        );

        $resolver = $this->createMockResolver($template, $expectedResult);
        $engine->registerResolver('silver_plan', $resolver);

        $result = $engine->resolve($template);

        $this->assertEquals(99.99, $result->amount);
        $this->assertEquals(5, $result->quantity);
    }

    public function test_resolve_throws_on_unregistered_product_type(): void
    {
        $engine = new EntitlementEngineService();
        $template = $this->createMockTemplate('unknown_product');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No resolver registered for product type: unknown_product');

        $engine->resolve($template);
    }

    public function test_resolve_with_different_product_types(): void
    {
        $engine = new EntitlementEngineService();
        $goldResult = new \App\DataTransferObjects\EntitlementResult(
            amount: 299.99,
            quantity: 10,
            breakdown: [['description' => 'Gold', 'quantity' => 10, 'rate' => 299.99, 'amount' => 299.99, 'cost' => null]]
        );
        $silverResult = new \App\DataTransferObjects\EntitlementResult(
            amount: 99.99,
            quantity: 5,
            breakdown: [['description' => 'Silver', 'quantity' => 5, 'rate' => 99.99, 'amount' => 99.99, 'cost' => null]]
        );

        $resolver1 = $this->createMockResolver(
            $this->createMockTemplate('gold'),
            $goldResult
        );
        $resolver2 = $this->createMockResolver(
            $this->createMockTemplate('silver'),
            $silverResult
        );

        $engine->registerResolver('gold', $resolver1);
        $engine->registerResolver('silver', $resolver2);

        $goldActual = $engine->resolve($this->createMockTemplate('gold'));
        $silverActual = $engine->resolve($this->createMockTemplate('silver'));

        $this->assertEquals(299.99, $goldActual->amount);
        $this->assertEquals(99.99, $silverActual->amount);
        $this->assertNotEquals($goldActual->quantity, $silverActual->quantity);
    }

    public function test_has_resolver_returns_false_for_unregistered(): void
    {
        $engine = new EntitlementEngineService();
        $this->assertFalse($engine->hasResolver('nonexistent'));
    }

    public function test_has_resolver_returns_true_for_registered(): void
    {
        $engine = new EntitlementEngineService();
        $resolver = $this->createMockResolver();
        $engine->registerResolver('exists', $resolver);

        $this->assertTrue($engine->hasResolver('exists'));
    }

    public function test_get_registered_product_types_returns_all(): void
    {
        $engine = new EntitlementEngineService();
        $resolver = $this->createMockResolver();
        $engine->registerResolver('product_a', $resolver);
        $engine->registerResolver('product_b', $resolver);
        $engine->registerResolver('product_c', $resolver);

        $types = $engine->getRegisteredProductTypes();

        $this->assertCount(3, $types);
        $this->assertContains('product_a', $types);
        $this->assertContains('product_b', $types);
        $this->assertContains('product_c', $types);
    }

    public function test_get_registered_product_types_empty_on_no_registrations(): void
    {
        // Fresh instance with no registrations
        $freshEngine = new EntitlementEngineService();

        $types = $freshEngine->getRegisteredProductTypes();

        $this->assertEmpty($types);
    }

    public function test_resolve_empty_product_type_throws(): void
    {
        $engine = new EntitlementEngineService();
        $template = $this->createMockTemplate('');

        $this->expectException(\Exception::class);

        $engine->resolve($template);
    }

    public function test_register_resolver_with_empty_product_type(): void
    {
        $engine = new EntitlementEngineService();
        $resolver = $this->createMockResolver();

        // Should not throw, but registration should occur
        $engine->registerResolver('', $resolver);

        // Empty string is a valid key
        $this->assertTrue($engine->hasResolver(''));
    }

    public function test_get_registered_product_types_preserves_order(): void
    {
        $engine = new EntitlementEngineService();
        $resolver = $this->createMockResolver();
        $expected = ['zebra', 'apple', 'banana', 'cherry'];

        foreach ($expected as $type) {
            $engine->registerResolver($type, $resolver);
        }

        $types = $engine->getRegisteredProductTypes();

        $this->assertEquals($expected, $types);
    }

    public function test_resolve_uses_template_product_type(): void
    {
        $engine = new EntitlementEngineService();
        $template = $this->createMockTemplate('custom_type');
        $result = new \App\DataTransferObjects\EntitlementResult(
            amount: 50.0,
            quantity: 1,
            breakdown: [['description' => 'Custom', 'quantity' => 1, 'rate' => 50.0, 'amount' => 50.0, 'cost' => null]]
        );
        $resolver = $this->createMockResolver($template, $result);

        $engine->registerResolver('custom_type', $resolver);

        $resolved = $engine->resolve($template);

        // Verify the resolver was called with the correct template
        $this->assertEquals(50.0, $resolved->amount);
    }

    public function test_resolve_result_properties_preserved(): void
    {
        $engine = new EntitlementEngineService();
        $template = $this->createMockTemplate('test');
        $expectedResult = new \App\DataTransferObjects\EntitlementResult(
            amount: 123.45,
            quantity: 7,
            breakdown: [['description' => 'Test', 'quantity' => 7, 'rate' => 123.45, 'amount' => 123.45, 'cost' => null]]
        );

        $resolver = $this->createMockResolver($template, $expectedResult);
        $engine->registerResolver('test', $resolver);

        $result = $engine->resolve($template);

        $this->assertEquals($expectedResult->amount, $result->amount);
        $this->assertEquals($expectedResult->quantity, $result->quantity);
    }

    public function test_multiple_resolves_same_template(): void
    {
        $engine = new EntitlementEngineService();
        $template = $this->createMockTemplate('repeatable');
        $result = new \App\DataTransferObjects\EntitlementResult(
            amount: 100.0,
            quantity: 1,
            breakdown: [['description' => 'Repeat', 'quantity' => 1, 'rate' => 100.0, 'amount' => 100.0, 'cost' => null]]
        );
        $resolver = $this->createMockResolver($template, $result);

        $engine->registerResolver('repeatable', $resolver);

        $result1 = $engine->resolve($template);
        $result2 = $engine->resolve($template);

        $this->assertEquals($result1->amount, $result2->amount);
    }

    public function test_resolve_with_different_template_instances(): void
    {
        $engine = new EntitlementEngineService();
        $result = new \App\DataTransferObjects\EntitlementResult(
            amount: 50.0,
            quantity: 1,
            breakdown: [['description' => 'Test', 'quantity' => 1, 'rate' => 50.0, 'amount' => 50.0, 'cost' => null]]
        );
        $resolver = $this->createMockResolver(null, $result);

        $engine->registerResolver('product', $resolver);

        $template1 = $this->createMockTemplate('product');
        $template2 = $this->createMockTemplate('product');

        // Both should resolve successfully (different instances, same product type)
        $engine->resolve($template1);
        $engine->resolve($template2);

        $this->assertTrue($engine->hasResolver('product'));
    }

    public function test_error_message_includes_product_type(): void
    {
        $engine = new EntitlementEngineService();
        $template = $this->createMockTemplate('specific_missing_type');

        try {
            $engine->resolve($template);
            $this->fail('Should have thrown exception');
        } catch (\Exception $e) {
            $this->assertStringContainsString('specific_missing_type', $e->getMessage());
        }
    }

    public function test_registered_product_types_after_overwrite(): void
    {
        $engine = new EntitlementEngineService();
        $resolver1 = $this->createMockResolver();
        $resolver2 = $this->createMockResolver();

        $engine->registerResolver('product', $resolver1);
        $engine->registerResolver('product', $resolver2);

        $types = $engine->getRegisteredProductTypes();

        // Should still be just one (overwritten, not duplicated)
        $this->assertCount(1, $types);
        $this->assertEquals(['product'], $types);
    }

    // Helper methods

    private function createMockResolver(
        ?BillingTemplateInterface $expectedTemplate = null,
        ?EntitlementResult $result = null,
    ): EntitlementResolver {
        $result ??= new EntitlementResult(
            amount: 100.0,
            quantity: 1,
            breakdown: [['description' => 'Default', 'quantity' => 1, 'rate' => 100.0, 'amount' => 100.0, 'cost' => null]]
        );

        return new class($expectedTemplate, $result) implements EntitlementResolver {
            public function __construct(
                private ?BillingTemplateInterface $expectedTemplate,
                private EntitlementResult $result,
            ) {
            }

            public function calculate(BillingTemplateInterface $template): EntitlementResult
            {
                if ($this->expectedTemplate && $template->getProductType() !== $this->expectedTemplate->getProductType()) {
                    throw new \Exception('Template mismatch');
                }

                return $this->result;
            }
        };
    }

    private function createMockTemplate(string $productType): BillingTemplateInterface
    {
        return new class($productType) implements BillingTemplateInterface {
            public function __construct(private string $productType)
            {
            }

            public function getProductType(): string
            {
                return $this->productType;
            }

            public function getId(): int
            {
                return 1;
            }

            public function getBillingCycle(): string
            {
                return 'monthly';
            }

            public function getPrice(): float
            {
                return 99.99;
            }
        };
    }
}
