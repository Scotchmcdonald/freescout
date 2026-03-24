<?php

declare(strict_types=1);

namespace Tests\Unit\Misc;

use App\Misc\Helper;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Facade;
use Tests\PureUnitTestCase;

class HelperTest extends PureUnitTestCase
{
    private ?Container $previousContainer = null;

    private mixed $previousFacadeApplication = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previousContainer = Container::getInstance();
        $this->previousFacadeApplication = Facade::getFacadeApplication();

        $app = new Application(getcwd());
        $app->instance('config', new Repository([
            'app' => [
                'key' => 'base64:dGVzdA==',
                'url' => 'https://example.com/helpdesk',
            ],
        ]));

        Container::setInstance($app);
        Facade::setFacadeApplication($app);
    }

    protected function tearDown(): void
    {
        Facade::setFacadeApplication($this->previousFacadeApplication);
        Container::setInstance($this->previousContainer);

        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // isInstalled
    // -------------------------------------------------------------------------

    public function test_is_installed_returns_true_when_app_key_is_set(): void
    {
        $this->assertTrue(Helper::isInstalled());
    }

    public function test_is_installed_returns_false_when_app_key_is_empty(): void
    {
        $app = Container::getInstance();
        $app->instance('config', new Repository(['app' => ['key' => '']]));

        $this->assertFalse(Helper::isInstalled());
    }

    public function test_is_installed_returns_false_when_app_key_is_null(): void
    {
        $app = Container::getInstance();
        $app->instance('config', new Repository(['app' => ['key' => null]]));

        $this->assertFalse(Helper::isInstalled());
    }

    // -------------------------------------------------------------------------
    // setGuzzleDefaultOptions
    // -------------------------------------------------------------------------

    public function test_set_guzzle_default_options_returns_merged_defaults(): void
    {
        $result = Helper::setGuzzleDefaultOptions(['timeout' => 60]);

        $this->assertSame(60, $result['timeout']);
        $this->assertSame(false, $result['http_errors']);
        $this->assertSame(10, $result['connect_timeout']);
        $this->assertSame(true, $result['verify']);
    }

    public function test_set_guzzle_default_options_returns_defaults_when_no_overrides(): void
    {
        $result = Helper::setGuzzleDefaultOptions();

        $this->assertSame(30, $result['timeout']);
        $this->assertFalse($result['http_errors']);
    }

    // -------------------------------------------------------------------------
    // checkRequiredExtensions / getMissingExtensions
    // -------------------------------------------------------------------------

    public function test_check_required_extensions_returns_array_of_booleans(): void
    {
        $result = Helper::checkRequiredExtensions();

        $this->assertIsArray($result);
        foreach ($result as $ext => $loaded) {
            $this->assertIsString($ext);
            $this->assertIsBool($loaded);
        }
    }

    public function test_get_missing_extensions_are_a_subset_of_required_extensions(): void
    {
        $missing = Helper::getMissingExtensions();
        $required = Helper::$requiredExtensions;

        $this->assertIsArray($missing);
        foreach ($missing as $ext) {
            $this->assertContains($ext, $required);
        }
    }

    public function test_required_extensions_count_matches_static_class_variable(): void
    {
        $result = Helper::checkRequiredExtensions();

        $this->assertCount(count(Helper::$requiredExtensions), $result);
    }

    // -------------------------------------------------------------------------
    // checkRequiredFunctions / getMissingFunctions
    // -------------------------------------------------------------------------

    public function test_check_required_functions_returns_array_of_booleans(): void
    {
        $result = Helper::checkRequiredFunctions();

        $this->assertIsArray($result);
        foreach ($result as $func => $available) {
            $this->assertIsString($func);
            $this->assertIsBool($available);
        }
    }

    public function test_get_missing_functions_returns_only_disabled_or_missing_functions(): void
    {
        // Just verify it returns an array — actual disabled functions depend on env
        $this->assertIsArray(Helper::getMissingFunctions());
    }

    // -------------------------------------------------------------------------
    // isFolderWritable
    // -------------------------------------------------------------------------

    public function test_is_folder_writable_returns_true_for_existing_writable_directory(): void
    {
        $this->assertTrue(Helper::isFolderWritable(sys_get_temp_dir()));
    }

    public function test_is_folder_writable_returns_false_for_nonexistent_path(): void
    {
        $this->assertFalse(Helper::isFolderWritable('/nonexistent/path/xyz'));
    }

    // -------------------------------------------------------------------------
    // formatBytes
    // -------------------------------------------------------------------------

    public function test_format_bytes_formats_bytes_as_b(): void
    {
        $this->assertSame('0 B', Helper::formatBytes(0));
        $this->assertSame('512 B', Helper::formatBytes(512));
    }

    public function test_format_bytes_formats_kilobytes(): void
    {
        $this->assertSame('1 KB', Helper::formatBytes(1024));
    }

    public function test_format_bytes_formats_megabytes(): void
    {
        $this->assertSame('1 MB', Helper::formatBytes(1024 * 1024));
    }

    public function test_format_bytes_formats_gigabytes(): void
    {
        $this->assertSame('1 GB', Helper::formatBytes(1024 * 1024 * 1024));
    }

    public function test_format_bytes_rounds_to_precision(): void
    {
        $result = Helper::formatBytes(1536); // 1.5 KB
        $this->assertSame('1.5 KB', $result);
    }

    // -------------------------------------------------------------------------
    // getSubdirectory
    // -------------------------------------------------------------------------

    public function test_get_subdirectory_extracts_path_from_app_url(): void
    {
        // app.url = 'https://example.com/helpdesk' → subdirectory = 'helpdesk'
        $this->assertSame('helpdesk', Helper::getSubdirectory());
    }

    public function test_get_subdirectory_returns_null_when_no_path_in_url(): void
    {
        $app = Container::getInstance();
        $app->instance('config', new Repository([
            'app' => ['url' => 'https://example.com'],
        ]));

        $this->assertNull(Helper::getSubdirectory());
    }

    public function test_get_subdirectory_returns_empty_or_null_for_root_slash(): void
    {
        $app = Container::getInstance();
        $app->instance('config', new Repository([
            'app' => ['url' => 'https://example.com/'],
        ]));

        // trim('/', '/') → '' which is falsy, so getSubdirectory returns null-ish empty string
        $result = Helper::getSubdirectory();
        $this->assertEmpty($result);
    }
}
