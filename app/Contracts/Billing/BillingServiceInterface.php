<?php

declare(strict_types=1);

namespace App\Contracts\Billing;

use Illuminate\Support\Collection;

interface BillingServiceInterface
{
    /**
     * Get invoices for a specific client.
     * 
     * @param int $clientId
     * @return Collection
     */
    public function getInvoicesForClient(int $clientId): Collection;
}
