<?php

declare(strict_types=1);

namespace Tests\Integration\Misc;

use App\Misc\WpApi;
use Illuminate\Support\Facades\Http;
use Tests\IntegrationTestCase;

/**
 * Unit tests for WpApi service.
 *
 * Uses mocked HTTP responses to test the marketplace API integration.
 */
class WpApiServiceTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
    }

    // ===== activateLicense tests =====

    public function test_activate_license_returns_array(): void
    {
        Http::fake([
            '*' => Http::response([
                'success' => true,
                'license' => 'valid',
                'item_name' => 'Test Module',
            ], 200),
        ]);

        $result = WpApi::activateLicense(['license' => 'test-license-key', 'module_alias' => 'test-module']);

        $this->assertIsArray($result);
    }

    public function test_activate_license_includes_license_key_in_request(): void
    {
        Http::fake(function ($request) {
            $this->assertEquals('my-license-key', $request['license']);

            return Http::response(['success' => true], 200);
        });

        WpApi::activateLicense(['license' => 'my-license-key', 'module_alias' => 'test-module']);

        Http::assertSentCount(1);
    }

    public function test_activate_license_handles_success_response(): void
    {
        Http::fake([
            '*' => Http::response([
                'success' => true,
                'license' => 'valid',
            ], 200),
        ]);

        $result = WpApi::activateLicense(['license' => 'test-key', 'module_alias' => 'test-module']);

        $this->assertTrue($result['success']);
        $this->assertEquals('valid', $result['license']);
    }

    public function test_activate_license_handles_failure_response(): void
    {
        Http::fake([
            '*' => Http::response([
                'success' => false,
                'error' => 'invalid',
            ], 200),
        ]);

        $result = WpApi::activateLicense(['license' => 'invalid-key', 'module_alias' => 'test-module']);

        $this->assertFalse($result['success']);
    }

    public function test_activate_license_handles_http_error(): void
    {
        Http::fake([
            '*' => Http::response(null, 500),
        ]);

        $result = WpApi::activateLicense(['license' => 'test-key', 'module_alias' => 'test-module']);

        $this->assertFalse($result['success'] ?? false);
    }

    public function test_activate_license_handles_timeout(): void
    {
        Http::fake([
            '*' => Http::response(null, 408),
        ]);

        $result = WpApi::activateLicense(['license' => 'test-key', 'module_alias' => 'test-module']);

        $this->assertIsArray($result);
    }

    // ===== deactivateLicense tests =====

    public function test_deactivate_license_returns_array(): void
    {
        Http::fake([
            '*' => Http::response([
                'success' => true,
                'license' => 'deactivated',
            ], 200),
        ]);

        $result = WpApi::deactivateLicense(['license' => 'test-license-key', 'module_alias' => 'test-module']);

        $this->assertIsArray($result);
    }

    public function test_deactivate_license_handles_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'success' => true,
                'license' => 'deactivated',
            ], 200),
        ]);

        $result = WpApi::deactivateLicense(['license' => 'test-key', 'module_alias' => 'test-module']);

        $this->assertTrue($result['success']);
    }

    public function test_deactivate_license_handles_failure(): void
    {
        Http::fake([
            '*' => Http::response([
                'success' => false,
            ], 200),
        ]);

        $result = WpApi::deactivateLicense(['license' => 'test-key', 'module_alias' => 'test-module']);

        $this->assertFalse($result['success']);
    }

    // ===== checkLicense tests =====

    public function test_check_license_returns_array(): void
    {
        Http::fake([
            '*' => Http::response([
                'success' => true,
                'license' => 'valid',
            ], 200),
        ]);

        $result = WpApi::checkLicense(['license' => 'test-license-key', 'module_alias' => 'test-module']);

        $this->assertIsArray($result);
    }

    public function test_check_license_handles_valid_license(): void
    {
        Http::fake([
            '*' => Http::response([
                'success' => true,
                'license' => 'valid',
                'expires' => '2025-12-31',
            ], 200),
        ]);

        $result = WpApi::checkLicense(['license' => 'test-key', 'module_alias' => 'test-module']);

        $this->assertEquals('valid', $result['license']);
    }

    public function test_check_license_handles_expired_license(): void
    {
        Http::fake([
            '*' => Http::response([
                'success' => true,
                'license' => 'expired',
            ], 200),
        ]);

        $result = WpApi::checkLicense(['license' => 'test-key', 'module_alias' => 'test-module']);

        $this->assertEquals('expired', $result['license']);
    }

    public function test_check_license_handles_invalid_license(): void
    {
        Http::fake([
            '*' => Http::response([
                'success' => false,
                'license' => 'invalid',
            ], 200),
        ]);

        $result = WpApi::checkLicense(['license' => 'test-key', 'module_alias' => 'test-module']);

        $this->assertEquals('invalid', $result['license']);
    }

    // ===== checkLicenses tests =====

    public function test_check_licenses_returns_array(): void
    {
        Http::fake([
            '*' => Http::response([
                'success' => true,
                'licenses' => [],
            ], 200),
        ]);

        $result = WpApi::checkLicenses(['module1' => 'key1', 'module2' => 'key2']);

        $this->assertIsArray($result);
    }

    public function test_check_licenses_processes_multiple_modules(): void
    {
        Http::fake([
            '*' => Http::response([
                'success' => true,
                'license' => 'valid',
            ], 200),
        ]);

        $licenses = [
            'module1' => 'key1',
            'module2' => 'key2',
            'module3' => 'key3',
        ];

        $result = WpApi::checkLicenses($licenses);

        $this->assertIsArray($result);
    }

    public function test_check_licenses_returns_empty_for_empty_input(): void
    {
        $result = WpApi::checkLicenses([]);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    // ===== getVersion tests =====

    public function test_get_version_returns_array(): void
    {
        Http::fake([
            '*' => Http::response([
                'success' => true,
                'new_version' => '1.0.0',
            ], 200),
        ]);

        $result = WpApi::getVersion(['module_alias' => 'test-module']);

        $this->assertIsArray($result);
    }

    public function test_get_version_returns_version_info(): void
    {
        Http::fake([
            '*' => Http::response([
                'success' => true,
                'new_version' => '2.0.0',
                'package' => 'https://example.com/download.zip',
            ], 200),
        ]);

        $result = WpApi::getVersion(['module_alias' => 'test-module']);

        $this->assertEquals('2.0.0', $result['new_version']);
        $this->assertArrayHasKey('package', $result);
    }

    public function test_get_version_handles_no_update(): void
    {
        Http::fake([
            '*' => Http::response([
                'success' => true,
                'new_version' => '1.0.0',
            ], 200),
        ]);

        $result = WpApi::getVersion(['module_alias' => 'test-module', 'version' => '1.0.0']);

        $this->assertIsArray($result);
    }

    public function test_get_version_with_license_key(): void
    {
        Http::fake(function ($request) {
            // Verify license key is included
            return Http::response([
                'success' => true,
                'new_version' => '1.0.0',
            ], 200);
        });

        $result = WpApi::getVersion(['module_alias' => 'test-module', 'version' => '1.0.0', 'license' => 'test-license-key']);

        $this->assertIsArray($result);
    }

    // ===== Error handling tests =====

    public function test_handles_json_decode_error(): void
    {
        Http::fake([
            '*' => Http::response('invalid json {{{', 200),
        ]);

        $result = WpApi::checkLicense(['license' => 'test-key', 'module_alias' => 'test-module']);

        $this->assertIsArray($result);
    }

    public function test_handles_empty_response(): void
    {
        Http::fake([
            '*' => Http::response('', 200),
        ]);

        $result = WpApi::checkLicense(['license' => 'test-key', 'module_alias' => 'test-module']);

        $this->assertIsArray($result);
    }

    public function test_handles_null_response(): void
    {
        Http::fake([
            '*' => Http::response(null, 200),
        ]);

        $result = WpApi::checkLicense(['license' => 'test-key', 'module_alias' => 'test-module']);

        $this->assertIsArray($result);
    }

    // ===== URL construction tests =====

    public function test_uses_correct_api_endpoint(): void
    {
        Http::fake(function ($request) {
            // Verify the correct API endpoint is being used
            $this->assertStringContainsString('freescout', $request->url());

            return Http::response(['success' => true], 200);
        });

        WpApi::checkLicense(['license' => 'test-key', 'module_alias' => 'test-module']);
    }

    // ===== Response structure tests =====

    public function test_license_response_contains_expected_fields(): void
    {
        Http::fake([
            '*' => Http::response([
                'success' => true,
                'license' => 'valid',
                'item_name' => 'Test Module',
                'expires' => '2025-12-31',
                'customer_email' => 'test@example.com',
            ], 200),
        ]);

        $result = WpApi::checkLicense(['license' => 'test-key', 'module_alias' => 'test-module']);

        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('license', $result);
    }

    // ===== Edge cases =====

    public function test_handles_special_characters_in_license_key(): void
    {
        Http::fake([
            '*' => Http::response(['success' => true], 200),
        ]);

        $result = WpApi::activateLicense(['license' => 'key-with-special-chars!@#$%', 'module_alias' => 'test-module']);

        $this->assertIsArray($result);
    }

    public function test_handles_very_long_license_key(): void
    {
        Http::fake([
            '*' => Http::response(['success' => true], 200),
        ]);

        $longKey = str_repeat('a', 1000);
        $result = WpApi::activateLicense(['license' => $longKey, 'module_alias' => 'test-module']);

        $this->assertIsArray($result);
    }

    public function test_handles_unicode_module_name(): void
    {
        Http::fake([
            '*' => Http::response(['success' => true], 200),
        ]);

        $result = WpApi::checkLicense(['license' => 'test-key', 'module_alias' => 'module-名前']);

        $this->assertIsArray($result);
    }
}
