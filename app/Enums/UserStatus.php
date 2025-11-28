<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * User status enum.
 *
 * Represents the different states a user can be in.
 */
enum UserStatus: int
{
    case Active = 1;
    case Inactive = 2;
    case Deleted = 3;

    /**
     * Get the human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Active => __('Active'),
            self::Inactive => __('Inactive'),
            self::Deleted => __('Deleted'),
        };
    }

    /**
     * Get the CSS class for the status badge.
     */
    public function cssClass(): string
    {
        return match ($this) {
            self::Active => 'bg-green-100 text-green-800',
            self::Inactive => 'bg-gray-100 text-gray-800',
            self::Deleted => 'bg-red-100 text-red-800',
        };
    }

    /**
     * Get all statuses as an array for select dropdowns (excluding Deleted).
     *
     * @return array<int, string>
     */
    public static function options(): array
    {
        return [
            self::Active->value => self::Active->label(),
            self::Inactive->value => self::Inactive->label(),
        ];
    }

    /**
     * Check if this status represents an active user.
     */
    public function isActive(): bool
    {
        return $this === self::Active;
    }
}
