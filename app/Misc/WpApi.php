<?php

declare(strict_types=1);

namespace App\Misc;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

/**
 * WpApi - Module marketplace API integration
 * Handles license activation/deactivation, version checking, and module downloads
 */
class WpApi
{
    public const ENDPOINT_MODULES = 'freescout/v1/modules';

    public const METHOD_GET = 'GET';
    public const METHOD_POST = 'POST';

    public const ACTION_CHECK_LICENSE = 'check_license';
    public const ACTION_CHECK_LICENSES = 'check_licenses';
    public const ACTION_ACTIVATE_LICENSE = 'activate_license';
    public const ACTION_DEACTIVATE_LICENSE = 'deactivate_license';
    public const ACTION_GET_VERSION = 'get_version';

    /**
     * @var array<string, mixed>|null
     */
    public static ?array $lastError = null;

    /**
     * Get API URL.
     */
    public static function url(string $path, bool $alternative = false): string
    {
        if ($alternative) {
            $api = config('app.freescout_alt_api', 'https://api.freescout.net/');
            if (!is_string($api)) {
                $api = 'https://api.freescout.net/';
            }
            return $api.$path;
        }

        $api = config('app.freescout_api', 'https://freescout.net/wp-json/');
        if (!is_string($api)) {
            $api = 'https://freescout.net/wp-json/';
        }
        return $api.$path;
    }

    /**
     * Make HTTP request.
     *
     * @param  array<string, mixed>  $params
     * @return \Illuminate\Http\Client\Response
     */
    public static function httpRequest(string $method, string $url, array $params)
    {
        $options = Helper::setGuzzleDefaultOptions([
            'connect_timeout' => 10,
        ]);

        $http = \Illuminate\Support\Facades\Http::withOptions($options);
        
        $version = config('app.version', '1.0.0');
        if (!is_string($version)) {
            $version = '1.0.0';
        }

        if ($method === self::METHOD_POST) {
            if (str_contains($url, '?')) {
                $url .= '&';
            } else {
                $url .= '?';
            }
            $url .= 'v='.$version;
            
            return $http->asForm()->post($url, $params);
        }

        $params['v'] = $version;
        
        return $http->get($url, $params);
    }

    /**
     * API request.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public static function request(string $method, string $endpoint, array $params = [], bool $alternativeApi = false): array
    {
        self::$lastError = null;

        try {
            $response = self::httpRequest($method, self::url($endpoint, $alternativeApi), $params);
        } catch (\Exception $e) {
            if (! $alternativeApi) {
                return self::request($method, $endpoint, $params, true);
            }

            Log::error('WpApi Error: '.$e->getMessage());
            self::$lastError = [
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ];

            return [];
        }

        if ($response->status() < 500) {
            $body = $response->body();
            $json = json_decode($body, true);

            if (! is_array($json)) {
                return [];
            }

            if (! empty($json['code']) && ! empty($json['message']) &&
                ! empty($json['data']) && ! empty($json['data']['status']) && (int) $json['data']['status'] !== 200
            ) {
                self::$lastError = $json;

                return [];
            }

            return $json;
        }

        return [];
    }

    /**
     * Get modules from marketplace.
     *
     * @return array<string, mixed>
     */
    public static function getModules(): array
    {
        return self::request(self::METHOD_GET, self::ENDPOINT_MODULES);
    }

    /**
     * Check module license.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public static function checkLicense(array $params): array
    {
        $params['action'] = self::ACTION_CHECK_LICENSE;

        $endpoint = self::ENDPOINT_MODULES;

        if (! empty($params['module_alias']) && is_string($params['module_alias'])) {
            $endpoint .= '/'.$params['module_alias'];
        }

        return self::request(self::METHOD_POST, $endpoint, $params);
    }

    /**
     * Check multiple module licenses.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public static function checkLicenses(array $params): array
    {
        $params['action'] = self::ACTION_CHECK_LICENSES;

        return self::request(self::METHOD_POST, self::ENDPOINT_MODULES, $params);
    }

    /**
     * Activate module license.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public static function activateLicense(array $params): array
    {
        $params['action'] = self::ACTION_ACTIVATE_LICENSE;

        $endpoint = self::ENDPOINT_MODULES;

        if (! empty($params['module_alias']) && is_string($params['module_alias'])) {
            $endpoint .= '/'.$params['module_alias'];
        }

        return self::request(self::METHOD_POST, $endpoint, $params);
    }

    /**
     * Deactivate module license.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public static function deactivateLicense(array $params): array
    {
        $params['action'] = self::ACTION_DEACTIVATE_LICENSE;

        $endpoint = self::ENDPOINT_MODULES;

        if (! empty($params['module_alias']) && is_string($params['module_alias'])) {
            $endpoint .= '/'.$params['module_alias'];
        }

        return self::request(self::METHOD_POST, $endpoint, $params);
    }

    /**
     * Get module version information.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public static function getVersion(array $params): array
    {
        $params['action'] = self::ACTION_GET_VERSION;

        $endpoint = self::ENDPOINT_MODULES;

        if (! empty($params['module_alias']) && is_string($params['module_alias'])) {
            $endpoint .= '/'.$params['module_alias'];
        }

        return self::request(self::METHOD_POST, $endpoint, $params);
    }

    /**
     * Get the last error.
     *
     * @return array<string, mixed>|null
     */
    public static function getLastError(): ?array
    {
        return self::$lastError;
    }
}
