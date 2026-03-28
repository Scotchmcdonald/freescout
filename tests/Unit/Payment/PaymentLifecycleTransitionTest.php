<?php

declare(strict_types=1);

namespace Tests\Unit\Payment;

use Illuminate\Support\Carbon;
use Modules\Payment\Models\Payment;
use Modules\Payment\Models\PaymentMethod;
use Tests\PureUnitTestCase;

final class TestPaymentTransitionModel extends Payment
{
    /** @var array<string, mixed> */
    public array $lastUpdatePayload = [];

    public function update(array $attributes = [], array $options = []): bool
    {
        $this->lastUpdatePayload = $attributes;
        $this->setRawAttributes(array_merge($this->attributes, $attributes), true);

        return true;
    }
}

final class TestPaymentMethodTransitionModel extends PaymentMethod
{
    /** @var array<string, mixed> */
    public array $lastUpdatePayload = [];

    public function update(array $attributes = [], array $options = []): bool
    {
        $this->lastUpdatePayload = $attributes;
        $this->setRawAttributes(array_merge($this->attributes, $attributes), true);

        return true;
    }
}

class PaymentLifecycleTransitionTest extends PureUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-03-24 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function payment(array $attrs = []): TestPaymentTransitionModel
    {
        $model = new TestPaymentTransitionModel;
        $model->setRawAttributes(array_merge([
            'status' => 'pending',
            'response_payload' => '{"existing":true}',
            'helcim_transaction_id' => 'tx_old',
        ], $attrs), true);

        return $model;
    }

    private function paymentMethod(array $attrs = []): TestPaymentMethodTransitionModel
    {
        $model = new TestPaymentMethodTransitionModel;
        $model->setRawAttributes($attrs, true);

        return $model;
    }

    public function test_mark_as_successful_updates_expected_fields_and_defaults(): void
    {
        $payment = $this->payment();

        $payment->markAsSuccessful([
            'helcim_transaction_id' => 'tx_new',
            'approval_code' => 'APPROVED',
            'avs_response' => 'Y',
            'cvv_response' => 'M',
            'response_payload' => ['ok' => true],
        ]);

        $this->assertSame('successful', $payment->lastUpdatePayload['status']);
        $this->assertSame('tx_new', $payment->lastUpdatePayload['helcim_transaction_id']);
        $this->assertSame('APPROVED', $payment->lastUpdatePayload['approval_code']);
        $this->assertSame(['ok' => true], $payment->lastUpdatePayload['response_payload']);
        $this->assertInstanceOf(Carbon::class, $payment->lastUpdatePayload['processed_at']);

        $fallback = $this->payment(['helcim_transaction_id' => 'tx_fallback', 'response_payload' => '{"existing":1}']);
        $fallback->markAsSuccessful();
        $this->assertSame('tx_fallback', $fallback->lastUpdatePayload['helcim_transaction_id']);
        $this->assertSame(['existing' => 1], $fallback->lastUpdatePayload['response_payload']);
        $this->assertNull($fallback->lastUpdatePayload['approval_code']);
    }

    public function test_mark_as_failed_and_declined_set_status_reason_and_payload(): void
    {
        $failed = $this->payment(['response_payload' => '{"existing":1}']);
        $failed->markAsFailed('network timeout', ['response_payload' => ['error' => 'timeout']]);

        $this->assertSame('failed', $failed->lastUpdatePayload['status']);
        $this->assertSame('network timeout', $failed->lastUpdatePayload['failure_reason']);
        $this->assertSame(['error' => 'timeout'], $failed->lastUpdatePayload['response_payload']);
        $this->assertInstanceOf(Carbon::class, $failed->lastUpdatePayload['failed_at']);

        $declined = $this->payment(['response_payload' => '{"existing":2}']);
        $declined->markAsDeclined('issuer decline');

        $this->assertSame('declined', $declined->lastUpdatePayload['status']);
        $this->assertSame('issuer decline', $declined->lastUpdatePayload['failure_reason']);
        $this->assertSame(['existing' => 2], $declined->lastUpdatePayload['response_payload']);
    }

    public function test_mark_as_reconciled_sets_reconcile_flags_and_actor(): void
    {
        $payment = $this->payment();
        $payment->markAsReconciled('system-bot');

        $this->assertTrue($payment->lastUpdatePayload['reconciled']);
        $this->assertSame('system-bot', $payment->lastUpdatePayload['reconciled_by']);
        $this->assertInstanceOf(Carbon::class, $payment->lastUpdatePayload['reconciled_at']);
    }

    public function test_payment_method_mark_as_used_sets_last_used_timestamp(): void
    {
        $method = $this->paymentMethod();
        $method->markAsUsed();

        $this->assertArrayHasKey('last_used_at', $method->lastUpdatePayload);
        $this->assertInstanceOf(Carbon::class, $method->lastUpdatePayload['last_used_at']);
    }

    public function test_authorization_boundary_failed_payment_is_ineligible_for_refund(): void
    {
        // Authorization boundary: only successful payments may be refunded;
        // failed payments must be refused at the refund authorization gate.
        $payment = $this->payment([
            'status' => 'failed',
            'total_amount' => '100.00',
            'refunded_amount' => '0.00',
            'dispute_status' => null,
        ]);

        $this->assertFalse(
            $payment->canBeRefunded(),
            'Authorization boundary: failed payments must not be eligible for refunds'
        );
    }
}
