<?php

declare(strict_types=1);

namespace App\Misc;

/**
 * WordPress API integration (stub)
 * TODO: Port full functionality from archive
 */
class WpApi
{
    public static ?string $lastError = null;

    /**
     * Get modules directory
     */
    public static function getModules(): array
    {
        return [];
    }
}
