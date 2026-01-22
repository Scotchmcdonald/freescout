<?php

namespace App\Policies;

use App\Models\User;
use Modules\Crm\Models\Client;
use Modules\Crm\Models\ClientUser;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Client Policy
 * 
 * Enforces data isolation for client access:
 * - Client users can only view their own client record
 * - Admin users can view/manage all clients
 */
class ClientPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any clients
     */
    public function viewAny(User|ClientUser $user): bool
    {
        // Admin users can view all clients
        if ($user instanceof User) {
            return true;
        }

        // Client users can only view their own client
        return false;
    }

    /**
     * Determine whether the user can view the client
     */
    public function view(User|ClientUser $user, Client $client): bool
    {
        // Internal users (Staff)
        if ($user instanceof User) {
            // Admins can view everything
            if ($user->role === User::ROLE_ADMIN) {
                return true;
            }
            
            // Technicians can only view clients belonging to companies they have access to
            // If client has no company, it's considered restricted (or open? restricted is safer)
            if (!$client->company_id) {
                return false;
            }
            
            return $user->hasCompanyAccess($client->company_id);
        }

        // Client users can only view their own client
        return $user->is_active && $user->client_id === $client->id;
    }

    /**
     * Determine whether the user can create clients
     */
    public function create(User|ClientUser $user): bool
    {
        // Only admin users can create clients
        return $user instanceof User && $user->isAdmin();
    }

    /**
     * Determine whether the user can update the client
     */
    public function update(User|ClientUser $user, Client $client): bool
    {
        // Only admin users can update clients
        return $user instanceof User && $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the client
     */
    public function delete(User|ClientUser $user, Client $client): bool
    {
        // Only admin users can delete clients
        return $user instanceof User && $user->isAdmin();
    }

    /**
     * Determine whether the user can view client portal data
     */
    public function viewPortal(User|ClientUser $user, Client $client): bool
    {
        // Admin users can view any client portal
        if ($user instanceof User) {
            return true;
        }

        // Client users can only view their own client's portal
        return $user->is_active 
            && $user->client_id === $client->id
            && $client->isActive();
    }

    /**
     * Determine whether the user can view client's invoices
     */
    public function viewInvoices(User|ClientUser $user, Client $client): bool
    {
        return $this->viewPortal($user, $client);
    }

    /**
     * Determine whether the user can view client's assets
     */
    public function viewAssets(User|ClientUser $user, Client $client): bool
    {
        return $this->viewPortal($user, $client);
    }

    /**
     * Determine whether the user can view client's subscriptions
     */
    public function viewSubscriptions(User|ClientUser $user, Client $client): bool
    {
        return $this->viewPortal($user, $client);
    }

    /**
     * Determine whether the user can manage payment methods
     */
    public function managePayments(User|ClientUser $user, Client $client): bool
    {
        // Admin users can manage any client's payments
        if ($user instanceof User) {
            return true;
        }

        // Client users can manage their own client's payment methods
        return $user->is_active 
            && $user->client_id === $client->id
            && $client->isActive();
    }
}
