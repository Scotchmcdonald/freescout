<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * UserEntitlementCountProvider
 *
 * Contract for retrieving billable user counts per client/company.
 * Implemented by the CRM module; consumed by PIB's entitlement resolvers.
 *
 * This interface enforces the Core Blindness pattern: PIB reads user counts
 * without depending on any CRM Eloquent model or table structure directly.
 *
 * Registration:
 *   CrmServiceProvider::register() binds CrmUserEntitlementCountProvider to this interface.
 *
 * Usage:
 *   $count = app(UserEntitlementCountProvider::class)->activeUserCountForClient($clientId);
 */
interface UserEntitlementCountProvider
{
    /**
     * Return the count of active, billable users for a given client.
     *
     * Implementations MUST read from a pre-aggregated counter (e.g. client_user_counters)
     * to avoid full table scans during invoice generation runs.
     *
     * @param  int  $clientId  CRM client ID
     * @return int  Active user count (0 if no record exists)
     */
    public function activeUserCountForClient(int $clientId): int;

    /**
     * Return the sum of active, billable users across all clients in a company.
     *
     * @param  int  $companyId  Company ID
     * @return int  Active user count (0 if no clients exist)
     */
    public function activeUserCountForCompany(int $companyId): int;

    /**
     * Recompute and persist the counter for a given client from the live source-of-truth.
     *
     * Called by event listeners when contact state changes to keep the counter fresh.
     *
     * @param  int  $clientId  CRM client ID
     * @return int  The newly computed count
     */
    public function rebuildCounterForClient(int $clientId): int;
}
