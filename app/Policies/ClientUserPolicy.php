<?php

namespace App\Policies;

use App\Models\User;
use Modules\Crm\Models\ClientUser;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Client User Policy
 * 
 * Enforces access control for client user management:
 * - Admin users can manage all client users
 * - Client users can only view/update their own profile
 */
class ClientUserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any client users
     */
    public function viewAny(User|ClientUser $user): bool
    {
        // Admin users can view all client users
        return $user instanceof User;
    }

    /**
     * Determine whether the user can view the client user
     */
    public function view(User|ClientUser $user, ClientUser $clientUser): bool
    {
        // Admin users can view any client user
        if ($user instanceof User) {
            return true;
        }

        // Client users can only view themselves
        return $user->id === $clientUser->id;
    }

    /**
     * Determine whether the user can create client users
     */
    public function create(User|ClientUser $user): bool
    {
        // Only admin users can create client users
        return $user instanceof User;
    }

    /**
     * Determine whether the user can update the client user
     */
    public function update(User|ClientUser $user, ClientUser $clientUser): bool
    {
        // Admin users can update any client user
        if ($user instanceof User) {
            return true;
        }

        // Client users can only update their own profile
        return $user->id === $clientUser->id && $user->is_active;
    }

    /**
     * Determine whether the user can delete the client user
     */
    public function delete(User|ClientUser $user, ClientUser $clientUser): bool
    {
        // Only admin users can delete client users
        return $user instanceof User;
    }

    /**
     * Determine whether the user can toggle the client user's active status
     */
    public function toggleActive(User|ClientUser $user, ClientUser $clientUser): bool
    {
        // Only admin users can toggle active status
        return $user instanceof User;
    }

    /**
     * Determine whether the user can impersonate the client user
     */
    public function impersonate(User $user, ClientUser $clientUser): bool
    {
        // Only admin users can impersonate client users
        return $user->isAdmin() && $clientUser->is_active;
    }
}
