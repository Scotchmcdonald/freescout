<?php

declare(strict_types=1);

namespace App\Misc;

/**
 * Helper class - minimal implementation for Laravel 11
 * TODO: Port full Helper functionality from archive
 */
class Helper
{
    /**
     * Check if application is installed
     */
    public static function isInstalled(): bool
    {
        // Simple check: if .env has APP_KEY set, consider it installed
        return config('app.key') !== null && config('app.key') !== '';
    }

    /**
     * Restart queue workers
     */
    public static function queueWorkerRestart(): void
    {
        // Signal queue workers to restart
        \Artisan::call('queue:restart');
    }

    /**
     * Directory permissions for created directories
     */
    public const DIR_PERMISSIONS = 0755;

    /**
     * Set default Guzzle options
     */
    public static function setGuzzleDefaultOptions(array $options = []): array
    {
        return array_merge([
            'http_errors' => false,
            'connect_timeout' => 10,
            'timeout' => 30,
            'verify' => true,
        ], $options);
    }
}

