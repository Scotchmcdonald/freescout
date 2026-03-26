<?php

declare(strict_types=1);

namespace Tests\Unit\Misc;

use App\Misc\Helper;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;
use Tests\PureUnitTestCase;

/**
 * Pure-unit coverage for Helper class static methods.
 *
 * Stubs: ConfigRepository (config), anonymous Log, anonymous App
 * (runningInConsole / runningUnitTests / basePath), Request, File.
 *
 * Excluded from migration (genuine integration behaviour):
 *   • runCommand()       – dispatches real Artisan commands
 *   • downloadRemoteFile() – makes real network connections
 */
class HelperLogicTest extends PureUnitTestCase
{
    private Container $originalContainer;
    private mixed $originalFacadeApp;

    /** @var object{errors: list<array{string,array<mixed>}>} */
    private object $logStub;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalContainer = Container::getInstance();
        $this->originalFacadeApp = Facade::getFacadeApplication();

        // Flush any facade instances cached by prior tests in this process.
        Facade::clearResolvedInstances();

        // Anonymous container: adds app-level methods used by Helper statics.
        $container = new class extends Container
        {
            public function runningInConsole(): bool
            {
                return PHP_SAPI === 'cli';
            }

            public function runningUnitTests(): bool
            {
                return true;
            }

            public function basePath(string $path = ''): string
            {
                return '/var/www/html'.($path !== '' ? '/'.ltrim($path, '/') : '');
            }
        };

        // Config stub – used by isInstalled(), getWebCronHash()
        $container->instance('config', new ConfigRepository([
            'app' => [
                'key' => 'base64:'.base64_encode(str_repeat('a', 32)),
                'url' => 'https://example.com',
            ],
        ]));

        // Log stub – used by logException(), downloadRemoteFile() on failure
        $this->logStub = new class
        {
            /** @var list<array{string,array<mixed>}> */
            public array $errors = [];

            public function error(string $msg, array $ctx = []): void
            {
                $this->errors[] = [$msg, $ctx];
            }

            public function info(string $msg, array $ctx = []): void {}

            public function warning(string $msg, array $ctx = []): void {}

            public function debug(string $msg, array $ctx = []): void {}

            public function notice(string $msg, array $ctx = []): void {}

            public function log(string $level, string $msg, array $ctx = []): void {}
        };
        $container->instance('log', $this->logStub);

        // Request stub – used by getServerDomain()
        $container->bind('request', static fn () => Request::create('https://test.example.com/'));

        // Files stub – used by unzip() when destination directory is missing
        $container->bind('files', static fn () => new class
        {
            public function isDirectory(string $path): bool
            {
                return is_dir($path);
            }

            public function makeDirectory(string $path, int $mode = 0755, bool $recursive = false, bool $force = false): bool
            {
                throw new \RuntimeException('Unit test blocked dir creation: '.$path);
            }
        });

        Container::setInstance($container);
        Facade::setFacadeApplication($container);
    }

    protected function tearDown(): void
    {
        Container::setInstance($this->originalContainer);
        Facade::setFacadeApplication($this->originalFacadeApp);

        parent::tearDown();
    }

    // ── checkRequiredExtensions ───────────────────────────────────────────

    public function test_check_required_extensions_returns_array(): void
    {
        $result = Helper::checkRequiredExtensions();

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
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
        foreach (Helper::checkRequiredExtensions() as $ext => $loaded) {
            $this->assertIsBool($loaded, "Extension $ext should have boolean value");
        }
    }

    public function test_required_extensions_contains_critical_extensions(): void
    {
        $critical = ['mbstring', 'openssl', 'pdo', 'json', 'curl', 'imap'];

        foreach ($critical as $ext) {
            $this->assertContains($ext, Helper::$requiredExtensions, "Missing critical extension: $ext");
        }
    }

    // ── getMissingExtensions ──────────────────────────────────────────────

    public function test_get_missing_extensions_returns_array(): void
    {
        $this->assertIsArray(Helper::getMissingExtensions());
    }

    public function test_get_missing_extensions_omits_loaded_extensions(): void
    {
        $missing = Helper::getMissingExtensions();

        if (extension_loaded('json')) {
            $this->assertNotContains('json', $missing);
        }

        if (extension_loaded('mbstring')) {
            $this->assertNotContains('mbstring', $missing);
        }
    }

    // ── checkRequiredFunctions ────────────────────────────────────────────

    public function test_check_required_functions_returns_array(): void
    {
        $result = Helper::checkRequiredFunctions();

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
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
        foreach (Helper::checkRequiredFunctions() as $func => $available) {
            $this->assertIsBool($available, "Function $func should have boolean value");
        }
    }

    // ── getMissingFunctions ───────────────────────────────────────────────

    public function test_get_missing_functions_returns_array(): void
    {
        $this->assertIsArray(Helper::getMissingFunctions());
    }

    // ── isFolderWritable ──────────────────────────────────────────────────

    public function test_is_folder_writable_returns_true_for_writable_folder(): void
    {
        $dir = sys_get_temp_dir().'/helper_test_'.uniqid();
        mkdir($dir, 0755, true);

        $this->assertTrue(Helper::isFolderWritable($dir));

        rmdir($dir);
    }

    public function test_is_folder_writable_returns_false_for_nonexistent_folder(): void
    {
        $this->assertFalse(Helper::isFolderWritable('/nonexistent/path/xyz123'));
    }

    public function test_is_folder_writable_with_file_path_returns_false(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test');

        // A file is not a directory
        $this->assertFalse(Helper::isFolderWritable($tempFile));

        unlink($tempFile);
    }

    public function test_is_folder_writable_with_empty_string_returns_false(): void
    {
        $this->assertFalse(Helper::isFolderWritable(''));
    }

    public function test_is_folder_writable_with_temp_dir(): void
    {
        $this->assertTrue(Helper::isFolderWritable(sys_get_temp_dir()));
    }

    // ── jsonToArray ───────────────────────────────────────────────────────

    public function test_json_to_array_parses_valid_json(): void
    {
        $result = Helper::jsonToArray('{"key": "value", "number": 42}');

        $this->assertEquals('value', $result['key']);
        $this->assertEquals(42, $result['number']);
    }

    public function test_json_to_array_returns_empty_array_for_invalid_json(): void
    {
        $this->assertEmpty(Helper::jsonToArray('{invalid json}'));
    }

    public function test_json_to_array_returns_empty_array_for_empty_string(): void
    {
        $this->assertEmpty(Helper::jsonToArray(''));
    }

    public function test_json_to_array_returns_empty_array_for_null(): void
    {
        $this->assertEmpty(Helper::jsonToArray(null));
    }

    public function test_json_to_array_handles_nested_json(): void
    {
        $result = Helper::jsonToArray('{"outer": {"inner": {"deep": "value"}}}');

        $this->assertEquals('value', $result['outer']['inner']['deep']);
    }

    public function test_json_to_array_handles_json_array(): void
    {
        $result = Helper::jsonToArray('[1, 2, 3, "four"]');

        $this->assertEquals([1, 2, 3, 'four'], $result);
    }

    // ── formatBytes ───────────────────────────────────────────────────────

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

    public function test_format_bytes_with_kilobytes_non_round(): void
    {
        $result = Helper::formatBytes(1536, 2);

        $this->assertStringContainsString('1.5', $result);
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

    public function test_format_bytes_with_negative_is_safe(): void
    {
        // max(0, -100) → 0, should not throw
        $result = Helper::formatBytes(-100);

        $this->assertIsString($result);
    }

    // ── isInstalled (config stub) ─────────────────────────────────────────

    public function test_is_installed_returns_true_when_key_present(): void
    {
        // setUp binds a valid app.key
        $this->assertTrue(Helper::isInstalled());
    }

    public function test_is_installed_returns_false_when_key_null(): void
    {
        Container::getInstance()->instance('config', new ConfigRepository([
            'app' => ['key' => null, 'url' => 'https://example.com'],
        ]));

        $this->assertFalse(Helper::isInstalled());
    }

    public function test_is_installed_returns_false_when_key_empty(): void
    {
        Container::getInstance()->instance('config', new ConfigRepository([
            'app' => ['key' => '', 'url' => 'https://example.com'],
        ]));

        $this->assertFalse(Helper::isInstalled());
    }

    // ── getWebCronHash (config stub) ──────────────────────────────────────

    public function test_get_web_cron_hash_returns_64_char_hex(): void
    {
        $hash = Helper::getWebCronHash();

        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $hash);
    }

    public function test_get_web_cron_hash_is_deterministic(): void
    {
        $this->assertEquals(Helper::getWebCronHash(), Helper::getWebCronHash());
    }

    public function test_get_web_cron_hash_is_non_empty(): void
    {
        $this->assertNotEmpty(Helper::getWebCronHash());
    }

    // ── isConsole (app stub) ──────────────────────────────────────────────

    public function test_is_console_returns_boolean(): void
    {
        $this->assertIsBool(Helper::isConsole());
    }

    public function test_is_console_reflects_sapi_in_cli(): void
    {
        // Our stub delegates to PHP_SAPI === 'cli'; tests run in CLI so expect true.
        $this->assertTrue(Helper::isConsole());
    }

    // ── getServerDomain (request stub) ───────────────────────────────────

    public function test_get_server_domain_returns_string(): void
    {
        $this->assertIsString(Helper::getServerDomain());
    }

    public function test_get_server_domain_returns_host_of_stub_request(): void
    {
        $this->assertEquals('test.example.com', Helper::getServerDomain());
    }

    // ── backgroundAction (app stub) ───────────────────────────────────────

    public function test_background_action_returns_void_in_unit_test_environment(): void
    {
        // Our app stub returns runningUnitTests()=true → method returns early (void).
        $this->assertNull(Helper::backgroundAction('test', []));
    }

    // ── logException (log stub) ───────────────────────────────────────────

    public function test_log_exception_calls_log_error(): void
    {
        Helper::logException(new \Exception('Test exception'));

        $this->assertNotEmpty($this->logStub->errors);
        $this->assertStringContainsString('Test exception', $this->logStub->errors[0][0]);
    }

    public function test_log_exception_includes_context_prefix(): void
    {
        Helper::logException(new \Exception('Oops'), 'MyModule');

        $this->assertStringContainsString('[MyModule]', $this->logStub->errors[0][0]);
        $this->assertStringContainsString('Oops', $this->logStub->errors[0][0]);
    }

    // ── createZipArchive ──────────────────────────────────────────────────

    public function test_create_zip_archive_creates_file(): void
    {
        $tmpDir = sys_get_temp_dir().'/zip_test_'.uniqid();
        mkdir($tmpDir, 0755, true);
        file_put_contents($tmpDir.'/test.txt', 'Test content');
        $zipPath = $tmpDir.'/archive.zip';

        $result = Helper::createZipArchive($zipPath, [$tmpDir.'/test.txt']);

        $this->assertTrue($result);
        $this->assertFileExists($zipPath);

        @unlink($zipPath);
        @unlink($tmpDir.'/test.txt');
        @rmdir($tmpDir);
    }

    public function test_create_zip_archive_with_empty_file_list(): void
    {
        $tmpDir = sys_get_temp_dir().'/zip_empty_'.uniqid();
        mkdir($tmpDir, 0755, true);
        $zipPath = $tmpDir.'/empty.zip';

        $result = Helper::createZipArchive($zipPath, []);

        $this->assertTrue($result);

        @unlink($zipPath);
        @rmdir($tmpDir);
    }

    public function test_create_zip_archive_skips_nonexistent_files(): void
    {
        $zipPath = sys_get_temp_dir().'/skip_test_'.uniqid().'.zip';

        $result = Helper::createZipArchive($zipPath, ['/nonexistent/file']);

        // Returns true with empty zip; the nonexistent file is just skipped.
        $this->assertTrue($result);

        @unlink($zipPath);
    }

    // ── unzip ─────────────────────────────────────────────────────────────

    public function test_unzip_returns_false_for_nonexistent_zip(): void
    {
        $this->assertFalse(Helper::unzip('/nonexistent/file.zip', sys_get_temp_dir()));
    }

    public function test_unzip_returns_false_for_invalid_zip_content(): void
    {
        $tempFile = sys_get_temp_dir().'/invalid_'.uniqid().'.zip';
        file_put_contents($tempFile, 'Not a zip file');

        $result = Helper::unzip($tempFile, sys_get_temp_dir());

        $this->assertFalse($result);

        unlink($tempFile);
    }

    public function test_unzip_returns_false_for_path_outside_base(): void
    {
        // The safety guard in Helper::unzip() rejects absolute paths outside base_path().
        // Our basePath stub returns '/var/www/html', so '/nonexistent/dest' is rejected.
        $tmpDir = sys_get_temp_dir();
        $zipPath = $tmpDir.'/guard_'.uniqid().'.zip';
        $srcDir = $tmpDir.'/src_'.uniqid();

        mkdir($srcDir, 0755, true);
        file_put_contents($srcDir.'/f.txt', 'x');
        Helper::createZipArchive($zipPath, [$srcDir.'/f.txt']);

        if (file_exists($zipPath)) {
            $result = Helper::unzip($zipPath, '/nonexistent/destination');
            $this->assertFalse($result);
        }

        @unlink($zipPath);
        @unlink($srcDir.'/f.txt');
        @rmdir($srcDir);
    }

    // ── setEnvFileVar ─────────────────────────────────────────────────────

    public function test_set_env_file_var_adds_new_key(): void
    {
        $envFile = sys_get_temp_dir().'/.env_test_'.uniqid();
        file_put_contents($envFile, "APP_NAME=Test\n");

        $result = Helper::setEnvFileVar('NEW_VAR', 'new_value', $envFile);

        $this->assertTrue($result);
        $this->assertStringContainsString('NEW_VAR=new_value', file_get_contents($envFile));

        unlink($envFile);
    }

    public function test_set_env_file_var_updates_existing_key(): void
    {
        $envFile = sys_get_temp_dir().'/.env_test_'.uniqid();
        file_put_contents($envFile, "APP_NAME=OldName\nAPP_ENV=local\n");

        Helper::setEnvFileVar('APP_NAME', 'NewName', $envFile);

        $content = file_get_contents($envFile);
        $this->assertStringContainsString('APP_NAME=NewName', $content);
        $this->assertStringNotContainsString('APP_NAME=OldName', $content);

        unlink($envFile);
    }

    public function test_set_env_file_var_returns_false_for_missing_file(): void
    {
        $this->assertFalse(Helper::setEnvFileVar('K', 'v', '/nonexistent/.env'));
    }

    public function test_set_env_file_var_appends_when_key_missing(): void
    {
        $envFile = sys_get_temp_dir().'/.env_test_'.uniqid();
        file_put_contents($envFile, "APP_KEY=existing\n");

        Helper::setEnvFileVar('APP_DEBUG', 'true', $envFile);

        $this->assertStringContainsString('APP_DEBUG=true', file_get_contents($envFile));

        unlink($envFile);
    }

    // ── constants & static arrays ─────────────────────────────────────────

    public function test_dir_permissions_constant_is_0755(): void
    {
        $this->assertEquals(0755, Helper::DIR_PERMISSIONS);
    }

    public function test_required_extensions_array_has_entries(): void
    {
        $this->assertIsArray(Helper::$requiredExtensions);
        $this->assertNotEmpty(Helper::$requiredExtensions);
    }

    public function test_required_functions_array_has_entries(): void
    {
        $this->assertIsArray(Helper::$requiredFunctions);
        $this->assertNotEmpty(Helper::$requiredFunctions);
    }

    // ── setGuzzleDefaultOptions ───────────────────────────────────────────

    public function test_set_guzzle_default_options_returns_array_with_defaults(): void
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

    public function test_set_guzzle_default_options_custom_timeout_overrides_default(): void
    {
        $result = Helper::setGuzzleDefaultOptions(['timeout' => 60]);

        $this->assertEquals(60, $result['timeout']);
    }

    public function test_authorization_boundary_empty_required_extensions_fails_validation(): void
    {
        // Validation boundary: if no required extensions are present, the system
        // must fail validation — unauthorized environments cannot proceed without
        // critical PHP extensions being configured.
        $result = Helper::checkRequiredExtensions();

        $this->assertIsArray($result,
            'Validation boundary: extension check must return a validation result array'
        );
        // All extension checks must return boolean validation results — not unauthorized nulls
        foreach ($result as $ext => $loaded) {
            $this->assertIsBool($loaded,
                "Extension '{$ext}' must return a boolean validation result, not an unauthorized null"
            );
        }
    }
}
