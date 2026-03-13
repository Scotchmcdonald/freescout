<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

/**
 * EntitlementResult DTO
 *
 * Represents the result of an entitlement calculation
 * Used for invoice generation and line item breakdown
 */
final readonly class EntitlementResult
{
    /**
     * @param  float  $amount  Total amount to charge
     * @param  int  $quantity  Primary quantity (e.g., number of users)
     * @param  array<int, array{description: string, quantity: int|float, rate: float, amount: float, cost: ?float}>  $breakdown  Detailed breakdown for invoice line items
     * @param  bool  $hasReachedGoal  For Rent-To-Own: whether goal has been reached
     */
    public function __construct(
        public float $amount,
        public int $quantity,
        public array $breakdown,
        public bool $hasReachedGoal = false,
    ) {}
}
