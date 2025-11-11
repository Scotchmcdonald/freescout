<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Customer;

class CustomerObserver
{
    /**
     * Handle the Customer "creating" event.
     */
    public function creating(Customer $customer): void
    {
        // Note: Customer email is stored in separate emails table
        // Email normalization is handled in Email model
    }

    /**
     * Handle the Customer "deleting" event.
     */
    public function deleting(Customer $customer): void
    {
        // Delete conversations
        $customer->conversations()->delete();

        // Delete email records
        $customer->emails()->delete();
    }
}
