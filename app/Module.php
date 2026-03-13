<?php

declare(strict_types=1);

namespace App;

/**
 * Module helper class.
 */
class Module
{
    public static ?bool $isOfficialResult = null;
    public static ?\Closure $updateCallback = null;

    /**
     * Check if a module is official based on author URL.
     */
    public static function isOfficial(?string $authorUrl): bool
    {
        if (self::$isOfficialResult !== null) {
            return self::$isOfficialResult;
        }

        // For now, return false so custom modules aren't skipped
        return false;
    }

    /**
     * @return array{status: string, msg: string, msg_success: string, download_error: bool, download_msg: string, output: string, module_name: string}
     */
    public static function updateModule(string $alias): array
    {
        if (self::$updateCallback) {
            return (self::$updateCallback)($alias);
        }

        // Return error since automatic updates are not supported without the archive implementations
        return [
            'status' => 'error',
            'msg' => 'Automatic module updates are disabled in this environment. Please update via composer or git.',
            'msg_success' => '',
            'download_error' => false,
            'download_msg' => '',
            'output' => '',
            'module_name' => $alias,
        ];
    }
}
