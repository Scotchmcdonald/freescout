<?php

declare(strict_types=1);

namespace App;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Module helper class.
 * 
 * TODO: Port full implementation from archive/app/Module.php
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

        $result = [
            'status' => 'error',
            'msg' => '',
            'msg_success' => '',
            'download_error' => false,
            'download_msg' => '',
            'output' => '',
            'module_name' => $alias,
        ];

        $moduleSource = app(\App\Services\ModuleSource::class);
        $moduleInfo = $moduleSource->getModule($alias);

        if (!$moduleInfo) {
            $result['msg'] = 'Module not found in source';
            return $result;
        }

        $result['module_name'] = $moduleInfo['name'] ?? $alias;
        $downloadUrl = $moduleInfo['download_url'] ?? null;

        if (!$downloadUrl) {
            $result['msg'] = 'Download URL not found for module';
            return $result;
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'mod_');

        try {
            // Download the file
            $response = Http::timeout(120)->sink($tempFile)->get($downloadUrl);

            if (!$response->successful()) {
                throw new \Exception('Failed to download module');
            }

            // Unzip
            $zip = new \ZipArchive;
            if ($zip->open($tempFile) === TRUE) {
                $extractPath = base_path('Modules');
                
                if (!File::isDirectory($extractPath)) {
                    File::makeDirectory($extractPath, 0755, true);
                }

                $zip->extractTo($extractPath);
                $zip->close();
                
                // Clean up temp file
                @unlink($tempFile);
                
                // Run install command
                $outputLog = new BufferedOutput();
                Artisan::call('freescout:module-install', ['module_alias' => $alias], $outputLog);
                $result['output'] = $outputLog->fetch();
                
                // Clear cache
                Artisan::call('cache:clear');
                Artisan::call('config:clear');

                $result['status'] = 'success';
                $result['msg_success'] = 'Module updated successfully';

            } else {
                throw new \Exception('Failed to open zip file');
            }

        } catch (\Exception $e) {
            if (file_exists($tempFile)) @unlink($tempFile);
            $result['msg'] = $e->getMessage();
            $result['download_error'] = true;
        }

        return $result;
    }
}
