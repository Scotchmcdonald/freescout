<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Conversation status enum.
 *
 * Represents the possible states of a conversation.
 */
enum ConversationStatus: int
{
    case Active = 1;
    case Pending = 2;
    case Closed = 3;
    case Spam = 4;

    /**
     * Get the human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Active => __('Active'),
            self::Pending => __('Pending'),
            self::Closed => __('Closed'),
            self::Spam => __('Spam'),
        };
    }

    /**
     * Get the color for the status badge.
     */
    public function color(): string
    {
        return match ($this) {
            self::Active => '#3f8abf',    // Blue
            self::Pending => '#e6b216',   // Yellow/Orange
            self::Closed => '#5cb85c',    // Green
            self::Spam => '#d9534f',      // Red
        };
    }

    /**
     * Get the CSS class for the status badge.
     */
    public function cssClass(): string
    {
        return match ($this) {
            self::Active => 'bg-blue-100 text-blue-800',
            self::Pending => 'bg-yellow-100 text-yellow-800',
            self::Closed => 'bg-green-100 text-green-800',
            self::Spam => 'bg-red-100 text-red-800',
        };
    }

    /**
     * Get all statuses as an array for select dropdowns.
     *
     * @return array<int, string>
     */
    public static function options(): array
    {
        return [
            self::Active->value => self::Active->label(),
            self::Pending->value => self::Pending->label(),
            self::Closed->value => self::Closed->label(),
            self::Spam->value => self::Spam->label(),
        ];
    }
}
