<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Conversation type enum.
 *
 * Represents the different types of conversations.
 */
enum ConversationType: int
{
    case Email = 1;
    case Phone = 2;
    case Chat = 3;

    /**
     * Get the human-readable label for the type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Email => __('Email'),
            self::Phone => __('Phone'),
            self::Chat => __('Chat'),
        };
    }

    /**
     * Get the icon class for the type.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Email => 'envelope',
            self::Phone => 'phone',
            self::Chat => 'chat-bubble-left-right',
        };
    }

    /**
     * Get all types as an array for select dropdowns.
     *
     * @return array<int, string>
     */
    public static function options(): array
    {
        return [
            self::Email->value => self::Email->label(),
            self::Phone->value => self::Phone->label(),
            self::Chat->value => self::Chat->label(),
        ];
    }
}
