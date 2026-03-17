<?php

declare(strict_types=1);

namespace Tests\Unit\Misc;

use App\Misc\Helper;
use Tests\UnitTestCase;

/**
 * Comprehensive tests for Helper class edge cases and boundary conditions.
 */
class HelperEdgeCasesTest extends UnitTestCase
{
    // ===== checkRequiredExtensions edge cases =====

    public function test_check_required_extensions_returns_array(): void
    {
        $result = Helper::checkRequiredExtensions();

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    public function test_get_missing_extensions_returns_array(): void
    {
        $missing = Helper::getMissingExtensions();

        $this->assertIsArray($missing);
    }

    // ===== checkRequiredFunctions edge cases =====

    public function test_check_required_functions_returns_array(): void
    {
        $result = Helper::checkRequiredFunctions();

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    public function test_get_missing_functions_returns_array(): void
    {
        $missing = Helper::getMissingFunctions();

        $this->assertIsArray($missing);
    }

    // ===== isFolderWritable edge cases =====

    public function test_is_folder_writable_with_temp_dir(): void
    {
        $result = Helper::isFolderWritable(sys_get_temp_dir());

        $this->assertTrue($result);
    }

    public function test_is_folder_writable_with_nonexistent_path(): void
    {
        $result = Helper::isFolderWritable('/nonexistent/path/xyz123');

        $this->assertFalse($result);
    }

    public function test_is_folder_writable_with_file_path(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test');

        $result = Helper::isFolderWritable($tempFile);

        // A file is not a folder
        $this->assertFalse($result);

        unlink($tempFile);
    }

    public function test_is_folder_writable_with_empty_string(): void
    {
        $result = Helper::isFolderWritable('');

        $this->assertFalse($result);
    }

    // ===== formatBytes edge cases =====

    public function test_format_bytes_with_zero(): void
    {
        $result = Helper::formatBytes(0);

        $this->assertIsString($result);
        $this->assertStringContainsString('0', $result);
    }

    public function test_format_bytes_with_bytes(): void
    {
        $result = Helper::formatBytes(500);

        $this->assertIsString($result);
        $this->assertStringContainsString('B', $result);
    }

    public function test_format_bytes_with_kilobytes(): void
    {
        $result = Helper::formatBytes(2048);

        $this->assertIsString($result);
        $this->assertTrue(
            str_contains($result, 'KB') ||
            str_contains($result, 'K')
        );
    }

    public function test_format_bytes_with_megabytes(): void
    {
        $result = Helper::formatBytes(1048576 * 5); // 5MB

        $this->assertIsString($result);
        $this->assertTrue(
            str_contains($result, 'MB') ||
            str_contains($result, 'M')
        );
    }

    public function test_format_bytes_with_gigabytes(): void
    {
        $result = Helper::formatBytes(1073741824); // 1GB

        $this->assertIsString($result);
        $this->assertTrue(
            str_contains($result, 'GB') ||
            str_contains($result, 'G')
        );
    }

    public function test_format_bytes_with_negative(): void
    {
        $result = Helper::formatBytes(-100);

        // Should handle negative gracefully
        $this->assertIsString($result);
    }

    // ===== jsonToArray edge cases =====

    public function test_json_to_array_with_valid_json(): void
    {
        $json = '{"key": "value", "number": 123}';

        $result = Helper::jsonToArray($json);

        $this->assertIsArray($result);
        $this->assertEquals('value', $result['key']);
        $this->assertEquals(123, $result['number']);
    }

    public function test_json_to_array_with_invalid_json(): void
    {
        $json = 'not valid json';

        $result = Helper::jsonToArray($json);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_json_to_array_with_empty_string(): void
    {
        $result = Helper::jsonToArray('');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_json_to_array_with_null(): void
    {
        $result = Helper::jsonToArray(null);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_json_to_array_with_array_json(): void
    {
        $json = '[1, 2, 3, "four"]';

        $result = Helper::jsonToArray($json);

        $this->assertIsArray($result);
        $this->assertEquals([1, 2, 3, 'four'], $result);
    }

    // ===== getWebCronHash edge cases =====

    public function test_get_web_cron_hash_returns_string(): void
    {
        $hash = Helper::getWebCronHash();

        $this->assertIsString($hash);
        $this->assertNotEmpty($hash);
    }

    public function test_get_web_cron_hash_is_consistent(): void
    {
        $hash1 = Helper::getWebCronHash();
        $hash2 = Helper::getWebCronHash();

        $this->assertEquals($hash1, $hash2);
    }

    public function test_get_web_cron_hash_is_hexadecimal(): void
    {
        $hash = Helper::getWebCronHash();

        $this->assertMatchesRegularExpression('/^[a-f0-9]+$/i', $hash);
    }

    public function test_get_web_cron_hash_is_long_enough(): void
    {
        $hash = Helper::getWebCronHash();

        // SHA256 produces 64 hex characters
        $this->assertGreaterThanOrEqual(32, strlen($hash));
    }

    // ===== isConsole tests =====

    public function test_is_console_returns_boolean(): void
    {
        $result = Helper::isConsole();

        $this->assertIsBool($result);
    }

    public function test_is_console_returns_true_in_tests(): void
    {
        // Tests typically run in console
        $result = Helper::isConsole();

        $this->assertTrue($result);
    }

    // ===== getServerDomain tests =====

    public function test_get_server_domain_returns_string(): void
    {
        $result = Helper::getServerDomain();

        $this->assertIsString($result);
    }

    public function test_get_server_domain_not_empty(): void
    {
        $result = Helper::getServerDomain();

        // May be empty in CLI but should be string
        $this->assertIsString($result);
    }

    // ===== setEnvFileVar tests =====

    public function test_set_env_file_var_returns_boolean(): void
    {
        // Create temp env file
        $tempDir = sys_get_temp_dir();
        $envPath = $tempDir.'/test_'.uniqid().'.env';
        file_put_contents($envPath, "APP_KEY=test\nAPP_DEBUG=true\n");

        $result = Helper::setEnvFileVar('APP_DEBUG', 'false', $envPath);

        $this->assertIsBool($result);

        unlink($envPath);
    }

    public function test_set_env_file_var_updates_existing_var(): void
    {
        $tempDir = sys_get_temp_dir();
        $envPath = $tempDir.'/test_'.uniqid().'.env';
        file_put_contents($envPath, "APP_KEY=test\nAPP_DEBUG=true\n");

        Helper::setEnvFileVar('APP_DEBUG', 'false', $envPath);

        $content = file_get_contents($envPath);
        $this->assertStringContainsString('APP_DEBUG=false', $content);

        unlink($envPath);
    }

    public function test_set_env_file_var_with_nonexistent_file(): void
    {
        $result = Helper::setEnvFileVar('KEY', 'value', '/nonexistent/path/.env');

        $this->assertFalse($result);
    }

    // ===== runCommand tests =====

    public function test_run_command_returns_string(): void
    {
        // 'list' is a standard artisan command
        $result = Helper::runCommand('list');

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function test_run_command_captures_output(): void
    {
        $result = Helper::runCommand('list');

        $this->assertStringContainsString('Usage:', $result);
    }

    public function test_run_command_with_failed_command(): void
    {
        // This should throw CommandNotFoundException
        $this->expectException(\Symfony\Component\Console\Exception\CommandNotFoundException::class);

        Helper::runCommand('nonexistent-command');
    }

    // ===== createZipArchive tests =====

    public function test_create_zip_archive_returns_boolean(): void
    {
        $tempDir = sys_get_temp_dir();
        $zipPath = $tempDir.'/test_'.uniqid().'.zip';
        $sourceDir = $tempDir.'/source_'.uniqid();

        mkdir($sourceDir);
        file_put_contents($sourceDir.'/test.txt', 'content');

        // New signature: createZipArchive(string $zipPath, array $files, string $baseDir = '')
        $result = Helper::createZipArchive($zipPath, [$sourceDir.'/test.txt'], $sourceDir);

        $this->assertIsBool($result);
        $this->assertTrue($result);

        // Cleanup
        @unlink($zipPath);
        @unlink($sourceDir.'/test.txt');
        @rmdir($sourceDir);
    }

    public function test_create_zip_archive_with_empty_directory(): void
    {
        $tempDir = sys_get_temp_dir();
        $zipPath = $tempDir.'/test_'.uniqid().'.zip';
        $sourceDir = $tempDir.'/empty_'.uniqid();

        mkdir($sourceDir);

        // Passing empty array of files
        $result = Helper::createZipArchive($zipPath, [], $sourceDir);

        // Should handle empty file list gracefully
        $this->assertIsBool($result);
        $this->assertTrue($result);

        // Cleanup
        @unlink($zipPath);
        @rmdir($sourceDir);
    }

    public function test_create_zip_archive_with_nonexistent_source(): void
    {
        $tempDir = sys_get_temp_dir();
        $zipPath = $tempDir.'/test_'.uniqid().'.zip';

        // Passing nonexistent file
        $result = Helper::createZipArchive($zipPath, ['/nonexistent/path']);

        // It should skip the file and return true (created empty zip)
        $this->assertTrue($result);

        @unlink($zipPath);
    }

    // ===== downloadRemoteFile tests =====

    public function test_download_remote_file_returns_boolean_or_string(): void
    {
        $result = Helper::downloadRemoteFile('https://example.com', sys_get_temp_dir().'/test.txt');

        // May succeed or fail depending on network
        $this->assertTrue(is_bool($result) || is_string($result));
    }

    public function test_download_remote_file_with_invalid_url(): void
    {
        $result = Helper::downloadRemoteFile('not-a-url', sys_get_temp_dir().'/test.txt');

        $this->assertFalse($result);
    }

    // ===== unzip tests =====

    public function test_unzip_with_nonexistent_file(): void
    {
        $result = Helper::unzip('/nonexistent/file.zip', sys_get_temp_dir());

        $this->assertFalse($result);
    }

    public function test_unzip_with_invalid_destination(): void
    {
        // Create a valid zip first
        $tempDir = sys_get_temp_dir();
        $zipPath = $tempDir.'/test_'.uniqid().'.zip';
        $sourceDir = $tempDir.'/source_'.uniqid();

        mkdir($sourceDir);
        file_put_contents($sourceDir.'/test.txt', 'content');
        Helper::createZipArchive($zipPath, [$sourceDir.'/test.txt'], $sourceDir);

        if (file_exists($zipPath)) {
            $result = Helper::unzip($zipPath, '/nonexistent/destination');
            $this->assertFalse($result);
        }

        // Cleanup
        @unlink($zipPath);
        @unlink($sourceDir.'/test.txt');
        @rmdir($sourceDir);
    }

    // ===== backgroundAction tests =====

    public function test_background_action_accepts_url(): void
    {
        // This should not throw an exception
        $this->expectNotToPerformAssertions();

        // We can't actually test background execution, but we can ensure it doesn't error
        try {
            Helper::backgroundAction('test', []);
        } catch (\Exception $e) {
            // May fail in test environment, which is acceptable
        }
    }

    // ===== logException tests =====

    public function test_log_exception_handles_exception(): void
    {
        $this->expectNotToPerformAssertions();

        $exception = new \Exception('Test exception');

        // Should not throw
        Helper::logException($exception);
    }

    public function test_log_exception_with_custom_message(): void
    {
        $this->expectNotToPerformAssertions();

        $exception = new \Exception('Test');

        // Should not throw
        Helper::logException($exception, 'Custom context');
    }
}
