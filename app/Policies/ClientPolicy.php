<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Modules\Crm\Models\Client;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Client Policy
 *
 * Enforces data isolation for client/company access:
 * - External users can only view their own company's client record
 * - Staff with view_crm/manage_crm can view/manage clients
 * - Admin bypass handled by Gate::before
 *
 * @note During Phase 3 migration, Client will be fully replaced by Company.
 *       For now, external users check via $user->company_id matching $client->id.
 */
class ClientPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any clients.
     */
    public function viewAny(User $user): bool
    {
        if ($user->isClient()) {
            return false;
        }

        return $user->hasPermission('view_crm');
    }

    /**
     * Determine whether the user can view the client.
     */
    public function view(User $user, Client $client): bool
    {
        // Internal users (Staff)
        if (! $user->isClient()) {
            if ($user->hasPermission('manage_crm')) {
                return true;
            }

            if ($user->hasPermission('view_crm')) {
                if (! $client->company_id) {
                    return false;
                }

                return $user->hasCompanyAccess($client->company_id);
            }

            if ($client->company_id) {
                return $user->hasCompanyAccess($client->company_id);
            }

            return false;
        }

        // External users can only view their own company's client record
        return $user->isActive() && $user->company_id === $client->id;
    }

    /**
     * Determine whether the user can create clients.
     */
    public function create(User $user): bool
    {
        return ! $user->isClient() && $user->hasPermission('manage_crm');
    }

    /**
     * Determine whether the user can update the client.
     */
    public function update(User $user, Client $client): bool
    {
        return ! $user->isClient() && $user->hasPermission('manage_crm');
    }

    /**
     * Determine whether the user can delete the client.
     */
    public function delete(User $user, Client $client): bool
    {
        return ! $user->isClient() && $user->hasPermission('manage_crm');
    }

    /**
     * Determine whether the user can view client portal data.
     */
    public function viewPortal(User $user, Client $client): bool
    {
        if (! $user->isClient()) {
            return $user->hasPermission('view_crm');
        }

        return $user->isActive()
            && $user->company_id === $client->id
            && $client->isActive();
    }

    /**
     * Determine whether the user can view client's invoices.
     */
    public function viewInvoices(User $user, Client $client): bool
    {
        if (! $user->isClient()) {
            return $user->hasPermission('view_billing');
        }

        return $this->viewPortal($user, $client);
    }

    /**
     * Determine whether the user can view client's assets.
     */
    public function viewAssets(User $user, Client $client): bool
    {
        if (! $user->isClient()) {
            return $user->hasPermission('view_assets');
        }

        return $this->viewPortal($user, $client);
    }

    /**
     * Determine whether the user can view client's subscriptions.
     */
    public function viewSubscriptions(User $user, Client $client): bool
    {
        if (! $user->isClient()) {
            return $user->hasPermission('view_software_subscriptions');
        }

        return $this->viewPortal($user, $client);
    }

    /**
     * Determine whether the user can manage payment methods.
     */
    public function managePayments(User $user, Client $client): bool
    {
        if (! $user->isClient()) {
            return $user->hasPermission('manage_billing');
        }

        return $user->isActive()
            && $user->company_id === $client->id
            && $client->isActive();
    }
}
