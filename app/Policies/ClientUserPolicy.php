<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Company User Policy (formerly ClientUserPolicy)
 *
 * Enforces access control for external (company-linked) user management:
 * - Staff with view_crm/manage_crm can manage company users
 * - External users can only view/update their own profile
 * - Admin bypass handled by Gate::before
 *
 * @deprecated The ClientUser model has been merged into User. This policy
 *             now operates on User instances typed as external (isClient()).
 *             Will be consolidated into UserPolicy in a future phase.
 */
class ClientUserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any company users.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('view_crm');
    }

    /**
     * Determine whether the user can view the target user.
     */
    public function view(User $user, User $targetUser): bool
    {
        if ($user->hasPermission('view_crm')) {
            return true;
        }

        return $user->id === $targetUser->id;
    }

    /**
     * Determine whether the user can create company users.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('manage_crm');
    }

    /**
     * Determine whether the user can update the target user.
     */
    public function update(User $user, User $targetUser): bool
    {
        if ($user->hasPermission('manage_crm')) {
            return true;
        }

        return $user->id === $targetUser->id && $user->isActive();
    }

    /**
     * Determine whether the user can delete the target user.
     */
    public function delete(User $user, User $targetUser): bool
    {
        return $user->hasPermission('manage_crm');
    }

    /**
     * Determine whether the user can toggle the target user's active status.
     */
    public function toggleActive(User $user, User $targetUser): bool
    {
        return $user->hasPermission('approve_users');
    }

    /**
     * Determine whether the user can impersonate the target user.
     */
    public function impersonate(User $user, User $targetUser): bool
    {
        return $user->isAdmin() && $targetUser->isActive();
    }
}
