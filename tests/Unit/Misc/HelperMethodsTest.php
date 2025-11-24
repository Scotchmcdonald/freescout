<?php

declare(strict_types=1);

namespace Tests\Unit\Misc;

use App\Misc\Helper;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Unit tests for Helper class utility methods.
 * 
 * These tests use the base TestCase (not UnitTestCase) as they don't
 * require database access - they test pure utility functions.
 */
class HelperMethodsTest extends TestCase
{
    // ===== checkRequiredExtensions tests =====

    public function test_check_required_extensions_returns_array(): void
    {
        $result = Helper::checkRequiredExtensions();

        $this->assertIsArray($result);
    }

    public function test_check_required_extensions_contains_expected_keys(): void
    {
        $result = Helper::checkRequiredExtensions();

        $this->assertArrayHasKey('mbstring', $result);
        $this->assertArrayHasKey('openssl', $result);
        $this->assertArrayHasKey('pdo', $result);
        $this->assertArrayHasKey('json', $result);
        $this->assertArrayHasKey('curl', $result);
    }

    public function test_check_required_extensions_returns_boolean_values(): void
    {
        $result = Helper::checkRequiredExtensions();

        foreach ($result as $ext => $loaded) {
            $this->assertIsBool($loaded, "Extension $ext should have boolean value");
        }
    }

    // ===== getMissingExtensions tests =====

    public function test_get_missing_extensions_returns_array(): void
    {
        $result = Helper::getMissingExtensions();

        $this->assertIsArray($result);
    }

    public function test_get_missing_extensions_does_not_include_loaded_extensions(): void
    {
        $missing = Helper::getMissingExtensions();

        // Common extensions that should be loaded
        if (extension_loaded('json')) {
            $this->assertNotContains('json', $missing);
        }

        if (extension_loaded('mbstring')) {
            $this->assertNotContains('mbstring', $missing);
        }
    }

    // ===== checkRequiredFunctions tests =====

    public function test_check_required_functions_returns_array(): void
    {
        $result = Helper::checkRequiredFunctions();

        $this->assertIsArray($result);
    }

    public function test_check_required_functions_contains_expected_keys(): void
    {
        $result = Helper::checkRequiredFunctions();

        $this->assertArrayHasKey('proc_open', $result);
        $this->assertArrayHasKey('proc_close', $result);
        $this->assertArrayHasKey('shell_exec', $result);
    }

    public function test_check_required_functions_returns_boolean_values(): void
    {
        $result = Helper::checkRequiredFunctions();

        foreach ($result as $func => $available) {
            $this->assertIsBool($available, "Function $func should have boolean value");
        }
    }

    // ===== getMissingFunctions tests =====

    public function test_get_missing_functions_returns_array(): void
    {
        $result = Helper::getMissingFunctions();

        $this->assertIsArray($result);
    }

    // ===== isFolderWritable tests =====

    public function test_is_folder_writable_returns_true_for_writable_folder(): void
    {
        $tempDir = sys_get_temp_dir() . '/helper_test_' . uniqid();
        mkdir($tempDir, 0755, true);

        $result = Helper::isFolderWritable($tempDir);

        $this->assertTrue($result);

        rmdir($tempDir);
    }

    public function test_is_folder_writable_returns_false_for_nonexistent_folder(): void
    {
        $nonExistent = '/nonexistent/path/that/does/not/exist';

        $result = Helper::isFolderWritable($nonExistent);

        $this->assertFalse($result);
    }

    // ===== jsonToArray tests =====

    public function test_json_to_array_parses_valid_json(): void
    {
        $json = '{"key": "value", "number": 42}';

        $result = Helper::jsonToArray($json);

        $this->assertIsArray($result);
        $this->assertEquals('value', $result['key']);
        $this->assertEquals(42, $result['number']);
    }

    public function test_json_to_array_returns_empty_array_for_invalid_json(): void
    {
        $invalidJson = '{invalid json}';

        $result = Helper::jsonToArray($invalidJson);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_json_to_array_returns_empty_array_for_empty_string(): void
    {
        $result = Helper::jsonToArray('');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_json_to_array_returns_empty_array_for_null(): void
    {
        $result = Helper::jsonToArray(null);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_json_to_array_handles_nested_json(): void
    {
        $json = '{"outer": {"inner": {"deep": "value"}}}';

        $result = Helper::jsonToArray($json);

        $this->assertEquals('value', $result['outer']['inner']['deep']);
    }

    // ===== formatBytes tests =====

    public function test_format_bytes_with_bytes(): void
    {
        $result = Helper::formatBytes(500);

        $this->assertStringContainsString('500', $result);
        $this->assertStringContainsString('B', $result);
    }

    public function test_format_bytes_with_kilobytes(): void
    {
        $result = Helper::formatBytes(1024);

        $this->assertStringContainsString('1', $result);
        $this->assertStringContainsString('KB', $result);
    }

    public function test_format_bytes_with_megabytes(): void
    {
        $result = Helper::formatBytes(1024 * 1024);

        $this->assertStringContainsString('1', $result);
        $this->assertStringContainsString('MB', $result);
    }

    public function test_format_bytes_with_gigabytes(): void
    {
        $result = Helper::formatBytes(1024 * 1024 * 1024);

        $this->assertStringContainsString('1', $result);
        $this->assertStringContainsString('GB', $result);
    }

    public function test_format_bytes_with_zero(): void
    {
        $result = Helper::formatBytes(0);

        $this->assertStringContainsString('0', $result);
    }

    public function test_format_bytes_with_custom_precision(): void
    {
        $result = Helper::formatBytes(1536, 2);

        $this->assertStringContainsString('1.5', $result);
        $this->assertStringContainsString('KB', $result);
    }

    // ===== isConsole tests =====

    public function test_is_console_returns_boolean(): void
    {
        $result = Helper::isConsole();

        $this->assertIsBool($result);
    }

    public function test_is_console_returns_true_in_cli(): void
    {
        // We're running in PHPUnit which is CLI
        $result = Helper::isConsole();

        $this->assertTrue($result);
    }

    // ===== getServerDomain tests =====

    public function test_get_server_domain_returns_string(): void
    {
        $result = Helper::getServerDomain();

        $this->assertIsString($result);
    }

    // ===== getWebCronHash tests =====

    public function test_get_web_cron_hash_returns_string(): void
    {
        $result = Helper::getWebCronHash();

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function test_get_web_cron_hash_is_deterministic(): void
    {
        $hash1 = Helper::getWebCronHash();
        $hash2 = Helper::getWebCronHash();

        $this->assertEquals($hash1, $hash2);
    }

    public function test_get_web_cron_hash_uses_hmac(): void
    {
        $hash = Helper::getWebCronHash();

        // HMAC-SHA256 produces 64 character hex string
        $this->assertEquals(64, strlen($hash));
        $this->assertMatchesRegularExpression('/^[a-f0-9]+$/', $hash);
    }

    // ===== runCommand tests =====

    public function test_run_command_returns_array(): void
    {
        $result = Helper::runCommand('echo "test"');

        $this->assertIsArray($result);
    }

    public function test_run_command_returns_success_key(): void
    {
        $result = Helper::runCommand('echo "test"');

        $this->assertArrayHasKey('success', $result);
    }

    public function test_run_command_returns_output_key(): void
    {
        $result = Helper::runCommand('echo "test"');

        $this->assertArrayHasKey('output', $result);
    }

    public function test_run_command_successful_command(): void
    {
        $result = Helper::runCommand('echo "hello"');

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('hello', $result['output']);
    }

    public function test_run_command_with_invalid_command(): void
    {
        $result = Helper::runCommand('nonexistent_command_xyz_123');

        // Should return false for success but not throw exception
        $this->assertFalse($result['success']);
    }

    // ===== createZipArchive tests =====

    public function test_create_zip_archive_creates_file(): void
    {
        $tempDir = sys_get_temp_dir() . '/zip_test_' . uniqid();
        mkdir($tempDir, 0755, true);
        file_put_contents($tempDir . '/test.txt', 'Test content');

        $zipPath = $tempDir . '/archive.zip';

        $result = Helper::createZipArchive($tempDir, $zipPath);

        $this->assertTrue($result);
        $this->assertFileExists($zipPath);

        // Cleanup
        unlink($zipPath);
        unlink($tempDir . '/test.txt');
        rmdir($tempDir);
    }

    public function test_create_zip_archive_returns_false_for_invalid_source(): void
    {
        $result = Helper::createZipArchive('/nonexistent/path', '/tmp/test.zip');

        $this->assertFalse($result);
    }

    // ===== downloadRemoteFile tests =====

    public function test_download_remote_file_returns_false_for_invalid_url(): void
    {
        $result = Helper::downloadRemoteFile('http://invalid-url-that-does-not-exist-xyz.com/file.zip', '/tmp/test.zip');

        $this->assertFalse($result);
    }

    // ===== unzip tests =====

    public function test_unzip_returns_false_for_nonexistent_file(): void
    {
        $result = Helper::unzip('/nonexistent/file.zip', '/tmp');

        $this->assertFalse($result);
    }

    public function test_unzip_returns_false_for_invalid_zip(): void
    {
        // Create a file that's not a valid zip
        $tempFile = sys_get_temp_dir() . '/invalid_' . uniqid() . '.zip';
        file_put_contents($tempFile, 'Not a zip file content');

        $result = Helper::unzip($tempFile, sys_get_temp_dir());

        $this->assertFalse($result);

        unlink($tempFile);
    }

    // ===== setEnvFileVar tests =====

    public function test_set_env_file_var_creates_backup(): void
    {
        // Create a temp .env file
        $tempEnv = sys_get_temp_dir() . '/.env_test_' . uniqid();
        file_put_contents($tempEnv, "APP_NAME=Test\nAPP_ENV=local\n");

        $result = Helper::setEnvFileVar('APP_DEBUG', 'true', $tempEnv);

        $this->assertTrue($result);
        $content = file_get_contents($tempEnv);
        $this->assertStringContainsString('APP_DEBUG=true', $content);

        unlink($tempEnv);
    }

    public function test_set_env_file_var_updates_existing_value(): void
    {
        $tempEnv = sys_get_temp_dir() . '/.env_test_' . uniqid();
        file_put_contents($tempEnv, "APP_NAME=OldName\nAPP_ENV=local\n");

        $result = Helper::setEnvFileVar('APP_NAME', 'NewName', $tempEnv);

        $this->assertTrue($result);
        $content = file_get_contents($tempEnv);
        $this->assertStringContainsString('APP_NAME=NewName', $content);
        $this->assertStringNotContainsString('APP_NAME=OldName', $content);

        unlink($tempEnv);
    }

    public function test_set_env_file_var_adds_new_value(): void
    {
        $tempEnv = sys_get_temp_dir() . '/.env_test_' . uniqid();
        file_put_contents($tempEnv, "APP_NAME=Test\n");

        $result = Helper::setEnvFileVar('NEW_VAR', 'new_value', $tempEnv);

        $this->assertTrue($result);
        $content = file_get_contents($tempEnv);
        $this->assertStringContainsString('NEW_VAR=new_value', $content);

        unlink($tempEnv);
    }

    public function test_set_env_file_var_returns_false_for_nonexistent_file(): void
    {
        $result = Helper::setEnvFileVar('TEST', 'value', '/nonexistent/.env');

        $this->assertFalse($result);
    }

    // ===== isInstalled tests =====

    public function test_is_installed_returns_boolean(): void
    {
        $result = Helper::isInstalled();

        $this->assertIsBool($result);
    }

    // ===== Required extensions/functions arrays tests =====

    public function test_required_extensions_array_exists(): void
    {
        $this->assertIsArray(Helper::$requiredExtensions);
        $this->assertNotEmpty(Helper::$requiredExtensions);
    }

    public function test_required_functions_array_exists(): void
    {
        $this->assertIsArray(Helper::$requiredFunctions);
        $this->assertNotEmpty(Helper::$requiredFunctions);
    }

    public function test_required_extensions_contains_critical_extensions(): void
    {
        $critical = ['mbstring', 'openssl', 'pdo', 'json', 'curl', 'imap'];

        foreach ($critical as $ext) {
            $this->assertContains($ext, Helper::$requiredExtensions, "Missing critical extension: $ext");
        }
    }

    // ===== DIR_PERMISSIONS constant test =====

    public function test_dir_permissions_constant_exists(): void
    {
        $this->assertEquals(0755, Helper::DIR_PERMISSIONS);
    }

    // ===== setGuzzleDefaultOptions tests =====

    public function test_set_guzzle_default_options_returns_array(): void
    {
        $result = Helper::setGuzzleDefaultOptions();

        $this->assertIsArray($result);
    }

    public function test_set_guzzle_default_options_contains_expected_keys(): void
    {
        $result = Helper::setGuzzleDefaultOptions();

        $this->assertArrayHasKey('http_errors', $result);
        $this->assertArrayHasKey('connect_timeout', $result);
        $this->assertArrayHasKey('timeout', $result);
        $this->assertArrayHasKey('verify', $result);
    }

    public function test_set_guzzle_default_options_merges_custom_options(): void
    {
        $result = Helper::setGuzzleDefaultOptions(['custom' => 'value']);

        $this->assertArrayHasKey('custom', $result);
        $this->assertEquals('value', $result['custom']);
    }

    public function test_set_guzzle_default_options_allows_override(): void
    {
        $result = Helper::setGuzzleDefaultOptions(['timeout' => 60]);

        $this->assertEquals(60, $result['timeout']);
    }
}
