<?php

declare(strict_types=1);

namespace Tests\Support;

use RuntimeException;

/**
 * Helper class for loading email fixtures in tests
 */
class EmailFixtures
{
    /**
     * Load an email fixture by name
     *
     * @param  string  $name  The fixture name (without .eml extension)
     * @return string The email content
     *
     * @throws RuntimeException if fixture not found
     */
    public static function load(string $name): string
    {
        $path = __DIR__.'/../Fixtures/emails/'.$name.'.eml';

        if (! file_exists($path)) {
            throw new RuntimeException("Email fixture not found: {$name}");
        }

        return file_get_contents($path);
    }

    /**
     * Get the path to a fixture file
     *
     * @param  string  $name  The fixture name (without .eml extension)
     * @return string The absolute path
     *
     * @throws RuntimeException if fixture not found
     */
    public static function path(string $name): string
    {
        $path = __DIR__.'/../Fixtures/emails/'.$name.'.eml';

        if (! file_exists($path)) {
            throw new RuntimeException("Email fixture not found: {$name}");
        }

        return $path;
    }

    /**
     * Check if a fixture exists
     *
     * @param  string  $name  The fixture name (without .eml extension)
     */
    public static function exists(string $name): bool
    {
        $path = __DIR__.'/../Fixtures/emails/'.$name.'.eml';

        return file_exists($path);
    }

    /**
     * List all available fixtures
     *
     * @return array<string> Array of fixture names (without .eml extension)
     */
    public static function all(): array
    {
        $directory = __DIR__.'/../Fixtures/emails/';
        $files = glob($directory.'*.eml');

        return array_map(function ($file) {
            return basename($file, '.eml');
        }, $files);
    }
}
