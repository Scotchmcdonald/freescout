<?php

declare(strict_types=1);

namespace App\ValueObjects;

use InvalidArgumentException;
use JsonSerializable;

class Money implements JsonSerializable
{
    public function __construct(
        public readonly int $amount, // Amount in cents
        public readonly string $currency = 'USD'
    ) {}

    public static function fromFloat(float $amount, string $currency = 'USD'): self
    {
        return new self((int) round($amount * 100), $currency);
    }

    public static function fromCents(int $amount, string $currency = 'USD'): self
    {
        return new self($amount, $currency);
    }

    public function add(Money $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amount + $other->amount, $this->currency);
    }

    public function subtract(Money $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amount - $other->amount, $this->currency);
    }

    public function multiply(float $multiplier): self
    {
        return new self((int) round($this->amount * $multiplier), $this->currency);
    }

    public function percentage(float $percent): self
    {
        return $this->multiply($percent / 100);
    }

    /**
     * @return array<int, self>
     */
    public function allocate(int $ratios): array
    {
        if ($ratios <= 0) {
            throw new InvalidArgumentException('Cannot allocate to 0 or fewer targets');
        }

        $remainder = $this->amount % $ratios;
        $base = ($this->amount - $remainder) / $ratios;
        $results = [];

        for ($i = 0; $i < $ratios; $i++) {
            $results[] = new self($base + ($i < $remainder ? 1 : 0), $this->currency);
        }

        return $results;
    }

    public function equals(Money $other): bool
    {
        return $this->amount === $other->amount && $this->currency === $other->currency;
    }

    public function toFloat(): float
    {
        return $this->amount / 100;
    }

    public function format(): string
    {
        return number_format($this->toFloat(), 2);
    }

    /**
     * @return array{amount: int, currency: string, formatted: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'amount' => $this->amount,
            'currency' => $this->currency,
            'formatted' => $this->format(),
        ];
    }

    private function assertSameCurrency(Money $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException('Currencies must match: '.$this->currency.' vs '.$other->currency);
        }
    }
}
