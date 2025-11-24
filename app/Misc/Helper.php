<?php

declare(strict_types=1);

namespace App\Misc;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Helper class - utility functions for FreeScout
 */
class Helper
{
    /**
     * Directory permissions for created directories
     */
    public const DIR_PERMISSIONS = 0755;

    /**
     * Required PHP extensions
     *
     * @var array<string>
     */
    public static array $requiredExtensions = [
        'mbstring',
        'openssl',
        'pdo',
        'tokenizer',
        'xml',
        'ctype',
        'json',
        'curl',
        'gd',
        'imap',
        'zip',
        'fileinfo',
    ];

    /**
     * Required PHP functions
     *
     * @var array<string>
     */
    public static array $requiredFunctions = [
        'proc_open',
        'proc_close',
        'proc_get_status',
        'shell_exec',
        'putenv',
        'symlink',
    ];

    /**
     * Check if application is installed
     */
    public static function isInstalled(): bool
    {
        return config('app.key') !== null && config('app.key') !== '';
    }

    /**
     * Restart queue workers
     */
    public static function queueWorkerRestart(): void
    {
        Artisan::call('queue:restart');
    }

    /**
     * Set default Guzzle options
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
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

    /**
     * Check required PHP extensions.
     *
     * @return array<string, bool>
     */
    public static function checkRequiredExtensions(): array
    {
        $result = [];
        foreach (self::$requiredExtensions as $ext) {
            $result[$ext] = extension_loaded($ext);
        }

        return $result;
    }

    /**
     * Get missing PHP extensions.
     *
     * @return array<string>
     */
    public static function getMissingExtensions(): array
    {
        $missing = [];
        foreach (self::$requiredExtensions as $ext) {
            if (! extension_loaded($ext)) {
                $missing[] = $ext;
            }
        }

        return $missing;
    }

    /**
     * Check required PHP functions.
     *
     * @return array<string, bool>
     */
    public static function checkRequiredFunctions(): array
    {
        $disabledFunctions = array_map('trim', explode(',', ini_get('disable_functions') ?: ''));

        $result = [];
        foreach (self::$requiredFunctions as $func) {
            $result[$func] = function_exists($func) && ! in_array($func, $disabledFunctions);
        }

        return $result;
    }

    /**
     * Get missing PHP functions.
     *
     * @return array<string>
     */
    public static function getMissingFunctions(): array
    {
        $disabledFunctions = array_map('trim', explode(',', ini_get('disable_functions') ?: ''));
        $missing = [];

        foreach (self::$requiredFunctions as $func) {
            if (! function_exists($func) || in_array($func, $disabledFunctions)) {
                $missing[] = $func;
            }
        }

        return $missing;
    }

    /**
     * Check if folder is writable.
     */
    public static function isFolderWritable(string $path): bool
    {
        return is_dir($path) && is_writable($path);
    }

    /**
     * Create ZIP archive.
     *
     * @param  array<string>  $files
     */
    public static function createZipArchive(string $zipPath, array $files, string $baseDir = ''): bool
    {
        if (! class_exists('ZipArchive')) {
            return false;
        }

        $zip = new \ZipArchive;
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return false;
        }

        foreach ($files as $file) {
            if (! file_exists($file)) {
                continue;
            }

            $localName = $baseDir ? str_replace($baseDir, '', $file) : basename($file);
            $zip->addFile($file, ltrim($localName, '/'));
        }

        return $zip->close();
    }

    /**
     * Download remote file.
     */
    public static function downloadRemoteFile(string $url, string $destPath, int $timeout = 120): bool
    {
        try {
            $response = Http::timeout($timeout)->sink($destPath)->get($url);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Download failed: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Unzip a file.
     */
    public static function unzip(string $zipPath, string $destPath): bool
    {
        if (! class_exists('ZipArchive')) {
            return false;
        }

        $zip = new \ZipArchive;
        if ($zip->open($zipPath) !== true) {
            return false;
        }

        if (! File::isDirectory($destPath)) {
            File::makeDirectory($destPath, self::DIR_PERMISSIONS, true);
        }

        $result = $zip->extractTo($destPath);
        $zip->close();

        return $result;
    }

    /**
     * Set environment variable in .env file.
     */
    public static function setEnvFileVar(string $key, string $value): bool
    {
        $envPath = base_path('.env');

        if (! file_exists($envPath)) {
            return false;
        }

        $content = file_get_contents($envPath);
        if ($content === false) {
            return false;
        }

        // Escape value if needed
        if (str_contains($value, ' ') || str_contains($value, '#')) {
            $value = '"'.$value.'"';
        }

        // Check if key exists
        if (preg_match("/^{$key}=/m", $content)) {
            // Replace existing
            $content = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $content);
        } else {
            // Add new line
            $content .= "\n{$key}={$value}";
        }

        return file_put_contents($envPath, $content) !== false;
    }

    /**
     * Get web cron hash.
     */
    public static function getWebCronHash(): string
    {
        return hash_hmac('sha256', 'cron', config('app.key'));
    }

    /**
     * Run artisan command.
     */
    public static function runCommand(string $command, array $params = []): string
    {
        $outputLog = new BufferedOutput;
        Artisan::call($command, $params, $outputLog);

        return $outputLog->fetch();
    }

    /**
     * Schedule a background action (non-blocking).
     *
     * @param  array<string>  $params
     */
    public static function backgroundAction(string $command, array $params = []): void
    {
        $artisan = base_path('artisan');
        $paramsStr = implode(' ', array_map('escapeshellarg', $params));

        // Run in background
        exec("php {$artisan} {$command} {$paramsStr} > /dev/null 2>&1 &");
    }

    /**
     * Convert JSON string to array.
     *
     * @return array<string, mixed>
     */
    public static function jsonToArray(mixed $json): array
    {
        if (is_string($json)) {
            $decoded = json_decode($json, true);

            return is_array($decoded) ? $decoded : [];
        }

        if (is_array($json)) {
            return $json;
        }

        return [];
    }

    /**
     * Log exception.
     */
    public static function logException(\Exception $e, string $context = ''): void
    {
        $message = $context ? "[{$context}] " : '';
        $message .= $e->getMessage();

        Log::error($message, [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);
    }

    /**
     * Get server domain.
     */
    public static function getServerDomain(): string
    {
        return request()->getHost();
    }

    /**
     * Check if running in console mode.
     */
    public static function isConsole(): bool
    {
        return app()->runningInConsole();
    }

    /**
     * Format bytes to human readable string.
     */
    public static function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision).' '.$units[$pow];
    }
}
