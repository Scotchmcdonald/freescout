<?php

declare(strict_types=1);

namespace App\Contracts\Billing;

/**
 * CreditReader Interface
 * 
 * Focused interface for credit query operations (read-only).
 * Segregated from write operations per Interface Segregation Principle.
 * 
 * Use this interface when you only need to query credit balances without
 * modifying them. This prevents accidental mutations and makes testing easier.
 * 
 * Examples:
 * - Analytics services that need balance information
 * - Reporting dashboards
 * - Client portal views
 */
interface CreditReader
{
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

    /**
     * Get credit ledger history for a client
     *
     * @param int $clientId
     * @param int $limit Maximum number of entries to return
     * @return array<int, array<string, mixed>> Array of ledger entries
     */
    public function getLedger(int $clientId, int $limit = 50): array;
}
