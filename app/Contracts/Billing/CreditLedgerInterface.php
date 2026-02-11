<?php

declare(strict_types=1);

namespace App\Contracts\Billing;

/**
 * Interface CreditLedgerInterface
 * 
 * Legacy interface for credit ledger operations.
 * Extends both CreditWriter and CreditReader for backward compatibility.
 * 
 * @deprecated Use CreditWriter and/or CreditReader instead
 * 
 * MIGRATION PATH:
 * - If you only read balances: depend on CreditReader
 * - If you only modify credits: depend on CreditWriter
 * - If you need both: depend on both interfaces (preferred) or this legacy interface
 * 
 * This interface will be kept for backward compatibility but new code should use
 * the segregated interfaces to follow the Interface Segregation Principle.
 */
interface CreditLedgerInterface extends CreditWriter, CreditReader
{
    // All methods inherited from CreditWriter and CreditReader
    // No additional methods should be added here
}
