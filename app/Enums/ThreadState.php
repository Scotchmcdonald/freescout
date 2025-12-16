<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Thread State Enum
 * 
 * Represents the publication state of a thread.
 */
enum ThreadState: int
{
    case DRAFT = 1;
    case PUBLISHED = 2;
    case HIDDEN = 3;
    case DELETED = 4;

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match($this) {
            self::DRAFT => 'Draft',
            self::PUBLISHED => 'Published',
            self::HIDDEN => 'Hidden',
            self::DELETED => 'Deleted',
        };
    }

    /**
     * Check if thread is visible.
     */
    public function isVisible(): bool
    {
        return $this === self::PUBLISHED;
    }

    /**
     * Check if thread is editable.
     */
    public function isEditable(): bool
    {
        return $this === self::DRAFT || $this === self::PUBLISHED;
    }
}
