<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\ValueObjects\Money;
use InvalidArgumentException;
use Tests\PureUnitTestCase;

/**
 * Pure-unit tests for the Money value object.
 *
 * All maths operations are deterministic — no DB or framework needed.
 */
final class MoneyTest extends PureUnitTestCase
{
    // ── Constructors / factories ──────────────────────────────────────────

    public function test_constructor_stores_cents_and_currency(): void
    {
        $m = new Money(1050, 'USD');
        $this->assertSame(1050, $m->amount);
        $this->assertSame('USD', $m->currency);
    }

    public function test_from_float_converts_to_cents(): void
    {
        $m = Money::fromFloat(10.50);
        $this->assertSame(1050, $m->amount);
        $this->assertSame('USD', $m->currency);
    }

    public function test_from_float_rounds_correctly(): void
    {
        // 0.1 + 0.2 floating point quirk should round to nearest cent
        $m = Money::fromFloat(10.005);
        $this->assertSame(1001, $m->amount); // rounds to $10.01 (1001 cents)
    }

    public function test_from_float_custom_currency(): void
    {
        $m = Money::fromFloat(5.00, 'EUR');
        $this->assertSame(500, $m->amount);
        $this->assertSame('EUR', $m->currency);
    }

    public function test_from_cents_factory(): void
    {
        $m = Money::fromCents(250);
        $this->assertSame(250, $m->amount);
        $this->assertSame('USD', $m->currency);
    }

    // ── add ───────────────────────────────────────────────────────────────

    public function test_add_same_currency(): void
    {
        $result = Money::fromCents(100)->add(Money::fromCents(50));
        $this->assertSame(150, $result->amount);
    }

    public function test_add_preserves_currency(): void
    {
        $result = Money::fromCents(100, 'EUR')->add(Money::fromCents(50, 'EUR'));
        $this->assertSame('EUR', $result->currency);
    }

    public function test_add_throws_on_currency_mismatch(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Currencies must match');
        Money::fromCents(100, 'USD')->add(Money::fromCents(50, 'EUR'));
    }

    // ── subtract ──────────────────────────────────────────────────────────

    public function test_subtract_same_currency(): void
    {
        $result = Money::fromCents(200)->subtract(Money::fromCents(75));
        $this->assertSame(125, $result->amount);
    }

    public function test_subtract_can_produce_negative(): void
    {
        $result = Money::fromCents(50)->subtract(Money::fromCents(100));
        $this->assertSame(-50, $result->amount);
    }

    public function test_subtract_throws_on_currency_mismatch(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Money::fromCents(100, 'USD')->subtract(Money::fromCents(50, 'GBP'));
    }

    // ── multiply ──────────────────────────────────────────────────────────

    public function test_multiply_by_integer(): void
    {
        $result = Money::fromCents(100)->multiply(3);
        $this->assertSame(300, $result->amount);
    }

    public function test_multiply_by_fraction(): void
    {
        $result = Money::fromCents(100)->multiply(1.5);
        $this->assertSame(150, $result->amount);
    }

    public function test_multiply_rounds_to_nearest_cent(): void
    {
        // 100 cents * 0.333 = 33.3 → rounds to 33 cents
        $result = Money::fromCents(100)->multiply(0.333);
        $this->assertSame(33, $result->amount);
    }

    // ── percentage ────────────────────────────────────────────────────────

    public function test_percentage_ten_percent(): void
    {
        $result = Money::fromCents(1000)->percentage(10);
        $this->assertSame(100, $result->amount); // 10% of $10.00 = $1.00
    }

    public function test_percentage_one_hundred_percent(): void
    {
        $result = Money::fromCents(500)->percentage(100);
        $this->assertSame(500, $result->amount);
    }

    public function test_percentage_zero(): void
    {
        $result = Money::fromCents(1000)->percentage(0);
        $this->assertSame(0, $result->amount);
    }

    // ── allocate ──────────────────────────────────────────────────────────

    public function test_allocate_evenly_divisible(): void
    {
        $parts = Money::fromCents(300)->allocate(3);
        $this->assertCount(3, $parts);
        $this->assertSame(100, $parts[0]->amount);
        $this->assertSame(100, $parts[1]->amount);
        $this->assertSame(100, $parts[2]->amount);
    }

    public function test_allocate_with_remainder_distributes_to_first_parts(): void
    {
        // 10 cents into 3 parts: 4, 3, 3 (remainder of 1 goes to first bucket)
        $parts = Money::fromCents(10)->allocate(3);
        $this->assertCount(3, $parts);
        $this->assertSame(4, $parts[0]->amount);
        $this->assertSame(3, $parts[1]->amount);
        $this->assertSame(3, $parts[2]->amount);
    }

    public function test_allocate_sum_matches_original(): void
    {
        $original = Money::fromCents(101);
        $parts = $original->allocate(3);
        $total = array_sum(array_map(fn ($p) => $p->amount, $parts));
        $this->assertSame(101, $total);
    }

    public function test_allocate_throws_on_zero_targets(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot allocate to 0 or fewer targets');
        Money::fromCents(100)->allocate(0);
    }

    public function test_allocate_throws_on_negative_targets(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Money::fromCents(100)->allocate(-1);
    }

    // ── equals ────────────────────────────────────────────────────────────

    public function test_equals_same_amount_and_currency(): void
    {
        $this->assertTrue(Money::fromCents(100)->equals(Money::fromCents(100)));
    }

    public function test_equals_different_amount(): void
    {
        $this->assertFalse(Money::fromCents(100)->equals(Money::fromCents(200)));
    }

    public function test_equals_different_currency(): void
    {
        $this->assertFalse(
            Money::fromCents(100, 'USD')->equals(Money::fromCents(100, 'EUR'))
        );
    }

    // ── toFloat / format ──────────────────────────────────────────────────

    public function test_to_float(): void
    {
        $this->assertSame(10.5, Money::fromCents(1050)->toFloat());
    }

    public function test_format_two_decimal_places(): void
    {
        $this->assertSame('10.50', Money::fromCents(1050)->format());
    }

    public function test_format_whole_dollars(): void
    {
        $this->assertSame('100.00', Money::fromCents(10000)->format());
    }

    // ── jsonSerialize ─────────────────────────────────────────────────────

    public function test_json_serialize_structure(): void
    {
        $data = Money::fromCents(1250, 'EUR')->jsonSerialize();

        $this->assertSame(1250, $data['amount']);
        $this->assertSame('EUR', $data['currency']);
        $this->assertSame('12.50', $data['formatted']);
    }
}
