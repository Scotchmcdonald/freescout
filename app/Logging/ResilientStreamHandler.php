<?php

declare(strict_types=1);

namespace App\Logging;

use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Throwable;

class ResilientStreamHandler extends StreamHandler
{
    private ?string $fallbackFilePath = null;

    public function __construct(
        string $stream,
        int|string|Level $level = Level::Debug,
        bool $bubble = true,
        ?int $filePermission = 0666,
        bool $useLocking = false,
        private readonly ?string $fallbackDirectory = null,
    ) {
        parent::__construct($stream, $level, $bubble, $filePermission, $useLocking);
    }

    protected function write(LogRecord $record): void
    {
        try {
            parent::write($record);
        } catch (Throwable $exception) {
            if (! $this->isPermissionError($exception)) {
                throw $exception;
            }

            $this->writeToFallbackFile($record, $exception);
        }
    }

    private function isPermissionError(Throwable $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'permission denied')
            || str_contains($message, 'operation not permitted')
            || str_contains($message, 'could not be opened in append mode')
            || str_contains($message, 'chmod()');
    }

    private function writeToFallbackFile(LogRecord $record, Throwable $originalException): void
    {
        $formattedRecord = $this->normalizeFormattedRecord($record->formatted);
        $fallbackPath = $this->getFallbackFilePath();

        $written = @file_put_contents($fallbackPath, $formattedRecord, FILE_APPEND | LOCK_EX);

        if ($written === false) {
            // Final fallback to stderr so application flow does not break.
            error_log(sprintf(
                '[logging-failover] Failed to write to %s after %s',
                $fallbackPath,
                $originalException->getMessage(),
            ));

            return;
        }

        @chmod($fallbackPath, 0666);
    }

    private function normalizeFormattedRecord(mixed $formatted): string
    {
        if (is_string($formatted)) {
            return $formatted;
        }

        if (is_scalar($formatted) || $formatted === null) {
            return (string) $formatted;
        }

        $json = json_encode($formatted, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $json === false ? '[unserializable-log-record]'.PHP_EOL : $json.PHP_EOL;
    }

    private function getFallbackFilePath(): string
    {
        if ($this->fallbackFilePath !== null) {
            return $this->fallbackFilePath;
        }

        $streamPath = is_string($this->url) ? $this->url : storage_path('logs/laravel.log');
        $pathInfo = pathinfo($streamPath);

        $baseName = isset($pathInfo['filename']) && $pathInfo['filename'] !== ''
            ? $pathInfo['filename']
            : 'laravel';

        $extension = isset($pathInfo['extension']) && $pathInfo['extension'] !== ''
            ? '.'.$pathInfo['extension']
            : '.log';

        $directory = $this->fallbackDirectory ?: ($pathInfo['dirname'] ?? storage_path('logs'));

        if (! is_dir($directory)) {
            @mkdir($directory, 0775, true);
        }

        $this->fallbackFilePath = rtrim($directory, '/').'/'.$baseName.'-fallback-'.date('Y-m-d-His').$extension;

        return $this->fallbackFilePath;
    }
}
