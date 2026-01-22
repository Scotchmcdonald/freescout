<?php

namespace App\Policies;

use App\Models\User;
use Modules\Crm\Models\ClientUser;
use Modules\PIB\Models\Invoice;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Invoice Policy
 * 
 * Enforces data isolation for invoice access:
 * - Client users can only view their own client's invoices
 * - Admin users can view/manage all invoices
 */
class InvoicePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any invoices
     */
    public function viewAny(User|ClientUser $user): bool
    {
        // Admin users can view all invoices
        if ($user instanceof User) {
            return true;
        }

        // Client users can view their client's invoices
        return $user->is_active && $user->client && $user->client->isActive();
    }

    /**
     * Determine whether the user can view the invoice
     */
    public function view(User|ClientUser $user, Invoice $invoice): bool
    {
        // Internal users
        if ($user instanceof User) {
            if ($user->isAdmin()) {
                return true;
            }
             // Technicians: Only if they have access to client's company
             if (!$invoice->client || !$invoice->client->company_id) {
                 return false; 
             }
             return $user->hasCompanyAccess($invoice->client->company_id);
        }

        // Client users can only view their own client's invoices
        return $user->is_active 
            && $user->client_id === $invoice->client_id
            && $user->client 
            && $user->client->isActive();
    }

    /**
     * Determine whether the user can create invoices
     */
    public function create(User|ClientUser $user): bool
    {
        // Only admin users can create invoices
        return $user instanceof User && $user->isAdmin();
    }

    /**
     * Determine whether the user can update the invoice
     */
    public function update(User|ClientUser $user, Invoice $invoice): bool
    {
        // Only admin users can update invoices
        return $user instanceof User && $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the invoice
     */
    public function delete(User|ClientUser $user, Invoice $invoice): bool
    {
        // Only admin users can delete invoices
        return $user instanceof User && $user->isAdmin();
    }

    /**
     * Determine whether the user can pay the invoice
     */
    public function pay(User|ClientUser $user, Invoice $invoice): bool
    {
        // Admin users can process payments
        if ($user instanceof User) {
            return true;
        }

        // Client users can only pay their own invoices
        return $user->is_active 
            && $user->client_id === $invoice->client_id
            && $user->client 
            && $user->client->isActive()
            && in_array($invoice->status, ['pending', 'sent', 'overdue']);
    }

    /**
     * Determine whether the user can dispute the invoice
     */
    public function dispute(User|ClientUser $user, Invoice $invoice): bool
    {
        // Client users can dispute their own invoices
        if ($user instanceof ClientUser) {
            return $user->is_active 
                && $user->client_id === $invoice->client_id
                && $user->client 
                && $user->client->isActive()
                && in_array($invoice->status, ['pending', 'sent']);
        }

        return false;
    }

    /**
     * Determine whether the user can download the invoice
     */
    public function download(User|ClientUser $user, Invoice $invoice): bool
    {
        return $this->view($user, $invoice);
    }
}
