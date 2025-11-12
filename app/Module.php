<?php

declare(strict_types=1);

namespace App;

/**
 * Module helper class.
 * 
 * TODO: Port full implementation from archive/app/Module.php
 */
class Module
{
    /**
     * Check if a module is official based on author URL.
     */
    public static function isOfficial(?string $authorUrl): bool
    {
        // TODO: Implement full logic from archive
        // For now, return false so custom modules aren't skipped
        return false;
    }
}
