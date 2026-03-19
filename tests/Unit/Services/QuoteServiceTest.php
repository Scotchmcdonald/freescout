<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Modules\ContractManager\Models\Contract;
use Modules\ContractManager\Models\Quote;
use Modules\ContractManager\Services\ContractService;
use Modules\ContractManager\Services\QuoteService;
use Modules\Crm\Models\Client;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\UnitTestCase;

class QuoteServiceTest extends UnitTestCase
{
    private QuoteService $service;

    /** @var ContractService&\PHPUnit\Framework\MockObject\MockObject */
    private $contractService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->contractService = $this->createMock(ContractService::class);
        $this->service = new QuoteService($this->contractService);
    }

    #[DataProvider('lineTotalProvider')]
    public function test_calculate_line_total_handles_boundaries(
        int $quantity,
        float $unitPrice,
        float $discountPercent,
        float $expected
    ): void {
        $actual = $this->invokeProtected($this->service, 'calculateLineTotal', [
            $quantity,
            $unitPrice,
            $discountPercent,
        ]);

        $this->assertSame($expected, $actual);
    }

    /**
     * @return array<string, array{0:int, 1:float, 2:float, 3:float}>
     */
    public static function lineTotalProvider(): array
    {
        return [
            'normal arithmetic with discount' => [3, 19.99, 10.0, 53.97],
            'zero quantity' => [0, 199.0, 15.0, 0.0],
            'full discount' => [7, 12.5, 100.0, 0.0],
            'fractional rounding' => [1, 99.999, 0.0, 100.0],
            'over 100 percent discount currently allowed' => [2, 10.0, 150.0, -10.0],
        ];
    }

    public function test_add_line_item_applies_defaults_for_invalid_or_missing_input(): void
    {
        $quote = $this->createQuote(status: 'draft');

        $lineItem = $this->service->addLineItem($quote, []);

        $quote->refresh();

        $this->assertSame(1.0, (float) $lineItem->quantity);
        $this->assertSame(0.0, (float) $lineItem->unit_price);
        $this->assertSame(0.0, (float) $lineItem->amount);
        $this->assertSame(0.0, (float) $quote->subtotal);
        $this->assertSame(0.0, (float) $quote->total);
    }

    public function test_add_line_item_recalculates_quote_totals(): void
    {
        $quote = $this->createQuote(status: 'draft');

        $first = $this->service->addLineItem($quote, [
            'description' => 'First line',
            'quantity' => 2,
            'unit_price' => 49.99,
            'discount_percent' => 10.0,
        ]);

        $second = $this->service->addLineItem($quote, [
            'description' => 'Second line',
            'quantity' => 1,
            'unit_price' => 10.0,
            'discount_percent' => 0.0,
        ]);

        $quote->refresh();

        $this->assertSame(89.98, (float) $first->amount);
        $this->assertSame(10.0, (float) $second->amount);
        $this->assertSame(99.98, (float) $quote->subtotal);
        $this->assertSame(99.98, (float) $quote->total);
        $this->assertSame(0.0, (float) $quote->tax_amount);
    }

    public function test_update_quote_rejects_non_draft_quotes(): void
    {
        $quote = $this->createQuote(status: 'sent');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Only draft quotes can be modified. Create a revision instead.');

        $this->service->updateQuote($quote, ['title' => 'Updated title']);
    }

    public function test_send_to_client_rejects_invalid_statuses(): void
    {
        $quote = $this->createQuote(status: 'approved');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Only draft or revised quotes can be sent.');

        $this->service->sendToClient($quote);
    }

    public function test_send_to_client_marks_quote_as_sent(): void
    {
        $quote = $this->createQuote(status: 'draft');

        $sent = $this->service->sendToClient($quote);

        $this->assertSame('sent', $sent->status);
        $this->assertNotNull($sent->sent_at);
    }

    public function test_approve_quote_rejects_non_sent_or_viewed_statuses(): void
    {
        $quote = $this->createQuote(status: 'draft');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Only sent or viewed quotes can be approved.');

        $this->service->approveQuote($quote);
    }

    public function test_approve_quote_marks_status_and_calls_contract_service(): void
    {
        $quote = $this->createQuote(status: 'sent');

        $this->contractService
            ->expects($this->once())
            ->method('createFromQuote')
            ->with($quote, [])
            ->willReturn(new Contract);

        $approved = $this->service->approveQuote($quote);

        $this->assertSame('approved', $approved->status);
        $this->assertNotNull($approved->approved_at);
    }

    private function createQuote(string $status): Quote
    {
        $client = Client::factory()->create();

        return Quote::query()->create([
            'client_id' => $client->id,
            'quote_number' => 'QTE-'.now()->format('Y').'-'.strtoupper(substr(uniqid(), -6)),
            'title' => 'Unit Test Quote',
            'status' => $status,
            'subtotal' => 0,
            'tax_amount' => 0,
            'total' => 0,
            'billing_type' => 'monthly',
            'billing_cycle' => 'monthly',
        ]);
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
