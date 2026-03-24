<?php

declare(strict_types=1);

namespace Tests\Unit\Payment;

use Illuminate\Support\Carbon;
use Modules\Payment\Models\Payment;
use Modules\Payment\Models\PaymentMethod;
use Tests\PureUnitTestCase;

final class TestPayment extends Payment
{
    public function getAttribute($key): mixed
    {
        if ($key === 'created_at') {
            $value = $this->attributes[$key] ?? null;
            if ($value instanceof Carbon) {
                return $value;
            }

            return $value ? Carbon::parse((string) $value) : null;
        }

        return parent::getAttribute($key);
    }
}

final class TestPaymentMethod extends PaymentMethod
{
    public function getAttribute($key): mixed
    {
        if ($key === 'expires_at') {
            $value = $this->attributes[$key] ?? null;
            if ($value instanceof Carbon) {
                return $value;
            }

            return $value ? Carbon::parse((string) $value) : null;
        }

        return parent::getAttribute($key);
    }
}

class PaymentModelHelpersTest extends PureUnitTestCase
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

    private function payment(array $attrs = []): TestPayment
    {
        $model = new TestPayment;
        $raw = [
            'status' => 'successful',
            'amount' => 100,
            'total_amount' => 100,
            'refunded_amount' => 0,
            'created_at' => Carbon::now()->subDay()->format('Y-m-d H:i:s'),
            'dispute_status' => null,
        ];

        foreach ($attrs as $key => $value) {
            $raw[$key] = $value instanceof Carbon
                ? $value->format('Y-m-d H:i:s')
                : $value;
        }

        $model->setRawAttributes($raw, true);

        return $model;
    }

    private function paymentMethod(array $attrs = []): TestPaymentMethod
    {
        $model = new TestPaymentMethod;
        $raw = [
            'is_active' => true,
            'status' => 'active',
            'helcim_card_token' => 'tok_123',
            'expires_at' => Carbon::now()->addDays(60)->format('Y-m-d H:i:s'),
            'card_brand' => 'Visa',
            'last_four' => '4242',
        ];

        foreach ($attrs as $key => $value) {
            $raw[$key] = $value instanceof Carbon
                ? $value->format('Y-m-d H:i:s')
                : $value;
        }

        $model->setRawAttributes($raw, true);

        return $model;
    }

    public function test_payment_status_helpers(): void
    {
        $this->assertTrue($this->payment(['status' => 'successful'])->isSuccessful());
        $this->assertFalse($this->payment(['status' => 'pending'])->isSuccessful());

        $this->assertTrue($this->payment(['status' => 'failed'])->isFailed());
        $this->assertTrue($this->payment(['status' => 'declined'])->isFailed());
        $this->assertFalse($this->payment(['status' => 'successful'])->isFailed());

        $this->assertTrue($this->payment(['status' => 'pending'])->isPending());
        $this->assertTrue($this->payment(['status' => 'processing'])->isPending());
        $this->assertFalse($this->payment(['status' => 'successful'])->isPending());

        $this->assertTrue($this->payment(['status' => 'refunded'])->isRefunded());
        $this->assertTrue($this->payment(['status' => 'partially_refunded'])->isRefunded());
        $this->assertFalse($this->payment(['status' => 'successful'])->isRefunded());
    }

    public function test_payment_can_be_refunded_requires_all_guards(): void
    {
        $eligible = $this->payment([
            'status' => 'successful',
            'total_amount' => 100,
            'refunded_amount' => 10,
            'created_at' => Carbon::now()->subDays(30),
            'dispute_status' => null,
        ]);
        $this->assertTrue($eligible->canBeRefunded());

        $this->assertFalse($this->payment(['status' => 'failed'])->canBeRefunded());
        $this->assertFalse($this->payment(['refunded_amount' => 100, 'total_amount' => 100])->canBeRefunded());
        $this->assertFalse($this->payment(['created_at' => Carbon::now()->subDays(181)])->canBeRefunded());
        $this->assertFalse($this->payment(['created_at' => Carbon::now()->subDays(180)])->canBeRefunded());
        $this->assertFalse($this->payment(['dispute_status' => 'chargeback'])->canBeRefunded());
    }

    public function test_payment_remaining_refundable_amount_handles_null_refunded_amount(): void
    {
        $noRefunds = $this->payment(['total_amount' => 50.25, 'refunded_amount' => null]);
        $partial = $this->payment(['total_amount' => 50.25, 'refunded_amount' => 12.33]);

        $this->assertSame(50.25, $noRefunds->getRemainingRefundableAmount());
        $this->assertEqualsWithDelta(37.92, $partial->getRemainingRefundableAmount(), 0.0001);
    }

    public function test_payment_formatted_amount_helpers(): void
    {
        $payment = $this->payment(['amount' => 1234.5, 'total_amount' => 98]);

        $this->assertSame('$1,234.50', $payment->getFormattedAmount());
        $this->assertSame('$98.00', $payment->getFormattedTotalAmount());
    }

    public function test_payment_method_is_expired_and_is_valid_helpers(): void
    {
        $valid = $this->paymentMethod();
        $this->assertFalse($valid->isExpired());
        $this->assertTrue($valid->isValid());

        $expired = $this->paymentMethod(['expires_at' => Carbon::now()->subDay()]);
        $this->assertTrue($expired->isExpired());
        $this->assertFalse($expired->isValid());

        $inactive = $this->paymentMethod(['is_active' => false]);
        $this->assertFalse($inactive->isValid());

        $wrongStatus = $this->paymentMethod(['status' => 'inactive']);
        $this->assertFalse($wrongStatus->isValid());

        $missingToken = $this->paymentMethod(['helcim_card_token' => null]);
        $this->assertFalse($missingToken->isValid());
    }

    public function test_payment_method_near_expiration_checks_window_and_expired_state(): void
    {
        $near = $this->paymentMethod(['expires_at' => Carbon::now()->addDays(10)]);
        $far = $this->paymentMethod(['expires_at' => Carbon::now()->addDays(45)]);
        $expired = $this->paymentMethod(['expires_at' => Carbon::now()->subDay()]);
        $none = $this->paymentMethod(['expires_at' => null]);

        $this->assertTrue($near->isNearExpiration());
        $this->assertFalse($far->isNearExpiration());
        $this->assertFalse($expired->isNearExpiration());
        $this->assertFalse($none->isNearExpiration());
    }

    public function test_payment_method_display_helpers(): void
    {
        $this->assertSame('****4242', $this->paymentMethod(['last_four' => '4242'])->getMaskedCardNumber());
        $this->assertSame('****', $this->paymentMethod(['last_four' => null])->getMaskedCardNumber());

        $this->assertSame('Visa ending in 4242', $this->paymentMethod(['card_brand' => 'Visa', 'last_four' => '4242'])->getDisplayName());
        $this->assertSame('Card ending in 1234', $this->paymentMethod(['card_brand' => null, 'last_four' => '1234'])->getDisplayName());
    }
}
