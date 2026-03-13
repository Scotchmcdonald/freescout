<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/**
 * UserPolicy — governs user management operations.
 *
 * Note: Gate::before grants super-admin roles (is_super_admin=true) a wildcard
 * bypass, so these checks are effectively redundant for admins.
 * We use hasPermission() so that non-admin roles with 'manage_users' can
 * also perform these actions.
 */
class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        return $user->hasPermission('manage_users');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user, User $model): bool
    {
        if ($user === null) {
            return false;
        }

        return $user->id === $model->id || $user->hasPermission('manage_users');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        return $user->hasPermission('manage_users');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(?User $user, User $model): bool
    {
        if ($user === null) {
            return false;
        }

        return $user->id === $model->id || $user->hasPermission('manage_users');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(?User $user, User $model): bool
    {
        if ($user === null) {
            return false;
        }

        // Cannot delete yourself; must have manage_users permission
        return $user->id !== $model->id && $user->hasPermission('manage_users');
    }

    /**
     * Determine if the user can impersonate another user.
     *
     * Security rules:
     * - Only admins can impersonate
     * - Cannot impersonate yourself
     * - Cannot impersonate other admins
     * - Cannot impersonate while already impersonating
     */
    public function impersonate(?User $user, User $target): bool
    {
        if ($user === null) {
            return false;
        }

        // Must be an admin
        if (! $user->isAdmin()) {
            return false;
        }

        // Cannot impersonate yourself
        if ($user->id === $target->id) {
            return false;
        }

        // Cannot impersonate other admins
        if ($target->isAdmin()) {
            return false;
        }

        // Cannot impersonate while already impersonating someone
        if ($user->isImpersonated()) {
            return false;
        }

        return true;
    }
}
