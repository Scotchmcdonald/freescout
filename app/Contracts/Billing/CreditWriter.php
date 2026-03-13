<?php

declare(strict_types=1);

namespace App\Contracts\Billing;

/**
 * CreditWriter Interface
 *
 * Focused interface for credit mutation operations (write).
 * Segregated from read operations per Interface Segregation Principle.
 *
 * Use this interface when you need to modify credit balances.
 * For read-only operations, use CreditReader instead.
 */
interface CreditWriter
{
    /**
     * Add credit to client account
     *
     * @param  float  $amount  Amount in dollars (positive)
     */
    public function addCredit(
        int $clientId,
        float $amount,
        string $description,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): void;

    /**
     * Deduct credit from client account
     *
     * @param  float  $amount  Amount to deduct in dollars (positive)
     *
     * @throws \Exception If insufficient balance
     */
    public function deductCredit(
        int $clientId,
        float $amount,
        string $description,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): void;
}
