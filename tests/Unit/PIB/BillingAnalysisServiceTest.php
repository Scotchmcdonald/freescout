<?php

declare(strict_types=1);

namespace Tests\Unit\PIB;

use App\DataTransferObjects\EntitlementResult;
use Modules\ContractManager\Models\BillingTemplate;
use Modules\PIB\Models\Invoice;
use Modules\PIB\Services\BillingAnalysisService;
use Modules\PIB\Services\EntitlementEngineService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\UnitTestCase;

class BillingAnalysisServiceTest extends UnitTestCase
{
    /** @var EntitlementEngineService&\PHPUnit\Framework\MockObject\MockObject */
    private $engine;

    private BillingAnalysisService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->engine = $this->createMock(EntitlementEngineService::class);
        $this->service = new BillingAnalysisService($this->engine);
    }

    #[DataProvider('percentChangeProvider')]
    public function test_calculate_percent_change_handles_core_arithmetic_branches(
        float $previousAmount,
        float $currentAmount,
        float $expected
    ): void {
        $actual = $this->invokeProtected($this->service, 'calculatePercentChange', [
            $previousAmount,
            $currentAmount,
        ]);

        $this->assertSame($expected, $actual);
    }

    /**
     * @return array<string, array{0:float, 1:float, 2:float}>
     */
    public static function percentChangeProvider(): array
    {
        return [
            'normal increase' => [100.0, 125.0, 25.0],
            'normal decrease' => [100.0, 75.0, -25.0],
            'flat with previous amount' => [100.0, 100.0, 0.0],
            'no previous invoice but current billing exists' => [0.0, 50.0, 100.0],
            'both previous and current are zero' => [0.0, 0.0, 0.0],
        ];
    }

    public function test_is_unusual_variance_uses_20_percent_threshold_symmetrically(): void
    {
        $this->assertFalse($this->invokeProtected($this->service, 'isUnusualVariance', [19.9]));
        $this->assertTrue($this->invokeProtected($this->service, 'isUnusualVariance', [20.0]));
        $this->assertTrue($this->invokeProtected($this->service, 'isUnusualVariance', [-20.0]));
    }

    public function test_sort_variances_orders_by_absolute_percent_change_descending(): void
    {
        $input = [
            ['percent_change' => 5.0, 'name' => 'small increase'],
            ['percent_change' => -45.0, 'name' => 'large decrease'],
            ['percent_change' => 20.0, 'name' => 'medium increase'],
        ];

        $sorted = $this->invokeProtected($this->service, 'sortVariancesByMagnitude', [$input]);

        $this->assertSame('large decrease', $sorted[0]['name']);
        $this->assertSame('medium increase', $sorted[1]['name']);
        $this->assertSame('small increase', $sorted[2]['name']);
    }

    public function test_billing_variance_report_skips_failed_entitlement_and_sorts_by_magnitude(): void
    {
        $templateWithHistory = BillingTemplate::factory()->create(['status' => 'active']);
        $templateWithoutHistory = BillingTemplate::factory()->create(['status' => 'active']);
        $templateWithEngineFailure = BillingTemplate::factory()->create(['status' => 'active']);
        BillingTemplate::factory()->create(['status' => 'paused']);

        Invoice::factory()->create([
            'billing_template_id' => $templateWithHistory->id,
            'total_amount' => 80.0,
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);

        Invoice::factory()->create([
            'billing_template_id' => $templateWithHistory->id,
            'total_amount' => 100.0,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        $this->engine
            ->expects($this->exactly(3))
            ->method('resolve')
            ->willReturnOnConsecutiveCalls(
                new EntitlementResult(120.0, 1, [['description' => 'A', 'quantity' => 1, 'rate' => 120.0, 'amount' => 120.0, 'cost' => null]]),
                new EntitlementResult(35.0, 1, [['description' => 'B', 'quantity' => 1, 'rate' => 35.0, 'amount' => 35.0, 'cost' => null]]),
                $this->throwException(new \RuntimeException('calculation failed')),
            );

        $report = $this->service->getBillingVarianceReport();

        $this->assertCount(2, $report);

        // Template without history should be first (100% change) after absolute sort.
        $this->assertSame(100.0, $report[0]['percent_change']);
        $this->assertTrue($report[0]['is_unusual']);
        $this->assertSame(0.0, (float) $report[0]['previous_amount']);
        $this->assertSame(35.0, (float) $report[0]['current_amount']);

        // Template with latest invoice at 100 and current 120 should produce +20.0.
        $this->assertSame(20.0, $report[1]['percent_change']);
        $this->assertTrue($report[1]['is_unusual']);
        $this->assertSame(100.0, (float) $report[1]['previous_amount']);
        $this->assertSame(120.0, (float) $report[1]['current_amount']);
        $this->assertNotEmpty($report[1]['breakdown']);
    }

    /**
     * @param  list<mixed>  $args
     */
    private function invokeProtected(object $target, string $method, array $args = []): mixed
    {
        $reflection = new \ReflectionMethod($target, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($target, $args);
    }
}
