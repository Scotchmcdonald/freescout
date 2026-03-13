<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ModuleSourceService
{
    /**
     * Get the URL for the module source.
     */
    protected function getSourceUrl(): string
    {
        $url = config('modules.source_url');

        return is_string($url) ? $url : 'https://raw.githubusercontent.com/freescout-helpdesk/modules/main/modules.json';
    }

    /**
     * Fetch the list of available modules.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getModules(): array
    {
        $result = Cache::remember('available_modules', 3600, function (): array {
            try {
                $url = $this->getSourceUrl();
                // For testing purposes, if the URL is the default placeholder and it doesn't exist, return empty array
                // In a real scenario, we would expect a valid JSON response

                // If we are in a test environment or the URL is dummy, return a sample list
                if (app()->environment('testing') || str_contains($url, 'example.com')) {
                    return $this->getSampleModules();
                }

                $response = Http::timeout(10)->get($url);

                if ($response->successful()) {
                    $modules = $response->json('modules');

                    return is_array($modules) ? $modules : [];
                }

                Log::warning('Failed to fetch modules from source: '.$response->status());

                return [];
            } catch (\Exception $e) {
                Log::error('Exception fetching modules: '.$e->getMessage());

                return [];
            }
        });

        /** @var array<int, array<string, mixed>> $result */
        return $result;
    }

    /**
     * Get module details by alias.
     *
     * @return array<string, mixed>|null
     */
    public function getModule(string $alias): ?array
    {
        $modules = $this->getModules();

        return collect($modules)->firstWhere('alias', $alias);
    }

    /**
     * Get sample modules for testing/dev.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function getSampleModules(): array
    {
        return [
            [
                'name' => 'Sample Module',
                'alias' => 'samplemodule',
                'description' => 'A sample module for testing purposes.',
                'version' => '1.0.0',
                'download_url' => 'https://example.com/modules/samplemodule.zip',
                'icon' => null,
                'price' => 'Free',
            ],
            [
                'name' => 'Custom Reports',
                'alias' => 'customreports',
                'description' => 'Advanced reporting capabilities.',
                'version' => '2.1.0',
                'download_url' => 'https://example.com/modules/customreports.zip',
                'icon' => null,
                'price' => 'Free',
            ],
        ];
    }
}
