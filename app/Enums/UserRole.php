<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * User role enum.
 *
 * Represents the different roles a user can have.
 */
enum UserRole: int
{
    case User = 1;
    case Admin = 2;
    case Reporter = 3;

    /**
     * Get the human-readable label for the role.
     */
    public function label(): string
    {
        return match ($this) {
            self::User => __('User'),
            self::Admin => __('Admin'),
            self::Reporter => __('Reporter'),
        };
    }

    /**
     * Get the description for the role.
     */
    public function description(): string
    {
        return match ($this) {
            self::User => __('Standard user with access to assigned mailboxes'),
            self::Admin => __('Full access to all mailboxes and settings'),
            self::Reporter => __('Read-only access to assigned mailboxes'),
        };
    }

    /**
     * Get all roles as an array for select dropdowns.
     *
     * @return array<int, string>
     */
    public static function options(): array
    {
        return [
            self::User->value => self::User->label(),
            self::Admin->value => self::Admin->label(),
            self::Reporter->value => self::Reporter->label(),
        ];
    }

    /**
     * Check if this role has admin privileges.
     */
    public function isAdmin(): bool
    {
        return $this === self::Admin;
    }
}
