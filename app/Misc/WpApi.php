<?php

declare(strict_types=1);

namespace App\Misc;

/**
 * WordPress API integration (stub)
 * TODO: Port full functionality from archive
 */
class WpApi
{
    /** @var array<string, mixed>|null */
    public static ?array $lastError = null;
    /** @var array<int, array<string, mixed>>|null */
    public static ?array $modules = null;

    /**
     * Get modules directory
     * 
     * @return array<int, array<string, mixed>>
     */
    public static function getModules(): array
    {
        return self::$modules ?? [];
    }
}
