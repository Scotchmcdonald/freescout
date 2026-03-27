<?php

declare(strict_types=1);

namespace App\Traits;

use App\Services\AuditLogService;
use Illuminate\Support\Facades\Auth;

/**
 * Trait for auditing sensitive business operations.
 *
 * Use this trait in services that perform sensitive operations like:
 * - Credit adjustments
 * - Quote approvals
 * - Contract modifications
 * - Payment processing
 * - Data exports
 * - Permission changes
 *
 * @example
 * class CreditService {
 *     use AuditsSensitiveOperations;
 *
 *     public function addCredit(int $clientId, float $amount, string $reason): void {
 *         // Perform operation
 *         $this->auditSensitiveOperation(
 *             'credit_added',
 *             Client::find($clientId),
 *             ['amount' => $amount, 'reason' => $reason]
 *         );
 *     }
 * }
 */
trait AuditsSensitiveOperations
{
    /**
     * Log a sensitive operation to the audit trail.
     *
     * @param  string  $operation  Operation name (e.g., 'credit_added', 'quote_approved')
     * @param  mixed  $subject  The model being operated on
     * @param  array  $properties  Additional context (amounts, reasons, etc.)
     * @param  string|null  $logName  Category for filtering (default: 'sensitive_operations')
     */
    protected function auditSensitiveOperation(
        string $operation,
        mixed $subject = null,
        array $properties = [],
        ?string $logName = 'sensitive_operations'
    ): void {
        app(AuditLogService::class)->logSensitiveOperation(
            operation: $operation,
            subject: $subject,
            properties: $properties,
            logName: $logName,
            causer: Auth::user()
        );
    }

    /**
     * Log a bulk sensitive operation with count.
     *
     * @param  string  $operation  Operation name
     * @param  int  $count  Number of items affected
     * @param  array  $properties  Additional context
     */
    protected function auditBulkOperation(
        string $operation,
        int $count,
        array $properties = []
    ): void {
        $this->auditSensitiveOperation(
            operation: $operation,
            subject: null,
            properties: array_merge($properties, ['count' => $count]),
            logName: 'bulk_operations'
        );
    }

    /**
     * Log a financial operation with amount tracking.
     *
     * @param  string  $operation  Operation name
     * @param  mixed  $subject  Related model
     * @param  int  $amountCents  Amount in cents
     * @param  array  $additionalProperties  Additional context
     */
    protected function auditFinancialOperation(
        string $operation,
        mixed $subject,
        int $amountCents,
        array $additionalProperties = []
    ): void {
        $this->auditSensitiveOperation(
            operation: $operation,
            subject: $subject,
            properties: array_merge($additionalProperties, [
                'amount_cents' => $amountCents,
                'amount_dollars' => (float) ($amountCents / 100),
            ]),
            logName: 'financial_operations'
        );
    }

    /**
     * Log a data access operation (exports, bulk queries).
     *
     * @param  string  $operation  Operation name
     * @param  string  $dataType  Type of data accessed
     * @param  array  $filters  Applied filters
     * @param  int|null  $recordCount  Number of records accessed
     */
    protected function auditDataAccess(
        string $operation,
        string $dataType,
        array $filters = [],
        ?int $recordCount = null
    ): void {
        $properties = [
            'data_type' => $dataType,
            'filters' => $filters,
        ];

        if ($recordCount !== null) {
            $properties['record_count'] = $recordCount;
        }

        $this->auditSensitiveOperation(
            operation: $operation,
            subject: null,
            properties: $properties,
            logName: 'data_access'
        );
    }
}
