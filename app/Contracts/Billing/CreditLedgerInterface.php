<?php

declare(strict_types=1);

namespace App\Contracts\Billing;

/**
 * Interface CreditLedgerInterface
 * 
 * Standard contract for credit ledger operations.
 * Implemented by feature modules (e.g. PIB) to provide credit services to Core or other modules.
 */
interface CreditLedgerInterface
{
    /**
     * Add credit to client account
     *
     * @param int $clientId
     * @param float $amount Amount in dollars (positive)
     * @param string $description
     * @param string|null $referenceType
     * @param int|null $referenceId
     * @return void
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
     * @param int $clientId
     * @param float $amount Amount to deduct in dollars (positive)
     * @param string $description
     * @param string|null $referenceType
     * @param int|null $referenceId
     * @return void
     * @throws \Exception If insufficient balance
     */
    public function deductCredit(
        int $clientId,
        float $amount,
        string $description,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): void;

    /**
     * Get current credit balance
     *
     * @param int $clientId
     * @return float Balance in dollars
     */
    public function getBalance(int $clientId): float;

    /**
     * Check if client has sufficient credit
     *
     * @param int $clientId
     * @param float $amount
     * @return bool
     */
    public function hasSufficientCredit(int $clientId, float $amount): bool;
}
