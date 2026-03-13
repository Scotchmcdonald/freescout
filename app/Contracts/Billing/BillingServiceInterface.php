<?php

declare(strict_types=1);

namespace App\Contracts\Billing;

use Illuminate\Support\Collection;

interface BillingServiceInterface
{
    /**
     * Get invoices for a specific client.
     *
     * @return Collection<int, mixed>
     */
    public function getInvoicesForClient(int $clientId): Collection;
}
