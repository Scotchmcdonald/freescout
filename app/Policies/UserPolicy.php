<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(?User $user): bool
    {
        return $user?->isAdmin() ?? false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user, User $model): bool
    {
        if ($user === null) {
            return false;
        }

        return $user->isAdmin() || $user->id === $model->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(?User $user): bool
    {
        return $user?->isAdmin() ?? false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(?User $user, User $model): bool
    {
        if ($user === null) {
            return false;
        }

        return $user->isAdmin() || $user->id === $model->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(?User $user, User $model): bool
    {
        if ($user === null) {
            return false;
        }

        return $user->isAdmin() && $user->id !== $model->id;
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
        if (!$user->isAdmin()) {
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
