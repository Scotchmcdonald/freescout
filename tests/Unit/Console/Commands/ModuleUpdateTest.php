<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\ModuleUpdate;
use App\Module;
use App\Services\ModuleSourceService;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\Artisan;
use Nwidart\Modules\Facades\Module as ModuleFacade;
use Nwidart\Modules\Laravel\Module as InstalledModule;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\UnitTestCase;

class ModuleUpdateTest extends UnitTestCase
{
    protected function tearDown(): void
    {
        Module::$updateCallback = null;
        Module::$isOfficialResult = null;

        parent::tearDown();
    }

    public function test_command_metadata_is_correct(): void
    {
        $command = new ModuleUpdate($this->mockModuleSource([]));

        $this->assertSame('freescout:module-update', $command->getName());
        $this->assertStringContainsString('update', strtolower($command->getDescription()));
        $this->assertFalse($command->getDefinition()->getArgument('module_alias')->isRequired());
    }

    public function test_handle_reports_all_modules_up_to_date_when_nothing_changes(): void
    {
        ModuleFacade::shouldReceive('all')->once()->andReturn([]);
        Artisan::shouldReceive('call')->once()->with('freescout:clear-cache')->andReturn(0);

        $command = new TestableModuleUpdate($this->mockModuleSource([]));

        $command->handle();

        $this->assertContains('All modules are up-to-date', $command->recordingOutput()->lines);
    }

    public function test_handle_updates_official_module_when_directory_version_is_newer(): void
    {
        $installedModule = $this->mockInstalledModule('test-module', '1.0.0');

        ModuleFacade::shouldReceive('all')->once()->andReturn([$installedModule]);
        Artisan::shouldReceive('call')->once()->with('freescout:clear-cache')->andReturn(0);

        Module::$updateCallback = function (string $alias): array {
            $this->assertSame('test-module', $alias);

            return [
                'module_name' => 'Test Module',
                'status' => 'success',
                'msg_success' => 'Updated successfully',
                'msg' => '',
                'download_error' => false,
                'download_msg' => '',
                'output' => "Line one\nLine two",
            ];
        };

        $command = new TestableModuleUpdate($this->mockModuleSource([
            ['alias' => 'test-module', 'version' => '2.0.0'],
        ]));

        $command->handle();

        $this->assertContains('[Test Module Module]', $command->recordingOutput()->infos);
        $this->assertContains('Updated successfully', $command->recordingOutput()->lines);
        $this->assertTrue(
            collect($command->recordingOutput()->lines)->contains(
                fn (string $line): bool => str_contains($line, '> Line one')
            )
        );
    }

    public function test_handle_prints_error_when_update_fails(): void
    {
        $installedModule = $this->mockInstalledModule('test-module', '1.0.0');

        ModuleFacade::shouldReceive('all')->once()->andReturn([$installedModule]);
        Artisan::shouldReceive('call')->once()->with('freescout:clear-cache')->andReturn(0);

        Module::$updateCallback = function (): array {
            return [
                'module_name' => 'Test Module',
                'status' => 'error',
                'msg_success' => '',
                'msg' => 'Update failed',
                'download_error' => true,
                'download_msg' => 'Download failed',
                'output' => '',
            ];
        };

        $command = new TestableModuleUpdate($this->mockModuleSource([
            ['alias' => 'test-module', 'version' => '2.0.0'],
        ]));

        $command->handle();

        $this->assertContains('ERROR: Update failed (Download failed)', $command->recordingOutput()->errors);
    }

    public function test_handle_filters_updates_by_module_alias_argument(): void
    {
        $target = $this->mockInstalledModule('target-module', '1.0.0');
        $other = $this->mockInstalledModule('other-module', '1.0.0');

        ModuleFacade::shouldReceive('all')->once()->andReturn([$target, $other]);
        Artisan::shouldReceive('call')->once()->with('freescout:clear-cache')->andReturn(0);

        Module::$updateCallback = function (string $alias): array {
            return [
                'module_name' => $alias === 'target-module' ? 'Target Module' : 'Unexpected Module',
                'status' => 'success',
                'msg_success' => 'Updated',
                'msg' => '',
                'download_error' => false,
                'download_msg' => '',
                'output' => '',
            ];
        };

        $command = new TestableModuleUpdate($this->mockModuleSource([
            ['alias' => 'target-module', 'version' => '2.0.0'],
            ['alias' => 'other-module', 'version' => '2.0.0'],
        ]));
        $command->moduleAlias = 'target-module';

        $command->handle();

        $this->assertContains('[Target Module Module]', $command->recordingOutput()->infos);
        $this->assertFalse(
            collect($command->recordingOutput()->infos)->contains(
                fn (string $line): bool => str_contains($line, 'Unexpected Module')
            )
        );
    }

    public function test_handle_reports_not_found_for_unknown_alias(): void
    {
        ModuleFacade::shouldReceive('all')->once()->andReturn([]);
        Artisan::shouldReceive('call')->once()->with('freescout:clear-cache')->andReturn(0);

        $command = new TestableModuleUpdate($this->mockModuleSource([]));
        $command->moduleAlias = 'missing-module';

        $command->handle();

        $this->assertContains(
            'Module with the following alias not found: missing-module',
            $command->recordingOutput()->errors
        );
    }

    public function test_handle_does_not_report_not_found_for_installed_custom_alias(): void
    {
        $customInstalled = $this->mockInstalledModule('custom-module', '1.0.0', null);

        ModuleFacade::shouldReceive('all')->once()->andReturn([$customInstalled]);
        Artisan::shouldReceive('call')->once()->with('freescout:clear-cache')->andReturn(0);

        $command = new TestableModuleUpdate($this->mockModuleSource([]));
        $command->moduleAlias = 'custom-module';

        $command->handle();

        $this->assertFalse(
            collect($command->recordingOutput()->errors)->contains(
                fn (string $line): bool => str_contains($line, 'not found')
            )
        );
    }

    private function mockModuleSource(array $modules): ModuleSourceService
    {
        $source = $this->createMock(ModuleSourceService::class);
        $source->method('getModules')->willReturn($modules);

        return $source;
    }

    private function mockInstalledModule(string $alias, string $version, ?string $latestVersionUrl = 'https://example.com/version'): InstalledModule
    {
        /** @var InstalledModule $module */
        $module = \Mockery::mock(InstalledModule::class);
        $module->shouldReceive('getAlias')->byDefault()->andReturn($alias);
        $module->shouldReceive('get')->byDefault()->with('version')->andReturn($version);
        $module->shouldReceive('get')->byDefault()->with('latestVersionUrl')->andReturn($latestVersionUrl);

        return $module;
    }
}

class TestableModuleUpdate extends ModuleUpdate
{
    public ?string $moduleAlias = null;

    private ModuleUpdateRecordingOutputStyle $recordingOutput;

    public function __construct(ModuleSourceService $moduleSource)
    {
        parent::__construct($moduleSource);

        $this->recordingOutput = new ModuleUpdateRecordingOutputStyle;

        $property = new \ReflectionProperty(\Illuminate\Console\Command::class, 'output');
        $property->setAccessible(true);
        $property->setValue($this, $this->recordingOutput);
    }

    public function argument($key = null)
    {
        if ($key === 'module_alias') {
            return $this->moduleAlias;
        }

        return parent::argument($key);
    }

    public function recordingOutput(): ModuleUpdateRecordingOutputStyle
    {
        return $this->recordingOutput;
    }
}

class ModuleUpdateRecordingOutputStyle extends OutputStyle
{
    /** @var array<int, string> */
    public array $lines = [];

    /** @var array<int, string> */
    public array $infos = [];

    /** @var array<int, string> */
    public array $errors = [];

    public function __construct()
    {
        parent::__construct(new ArrayInput([]), new BufferedOutput);
    }

    public function writeln(string|iterable $messages, int $type = self::OUTPUT_NORMAL): void
    {
        $text = is_iterable($messages)
            ? implode(PHP_EOL, array_map(static fn (mixed $message): string => (string) $message, iterator_to_array($messages)))
            : (string) $messages;

        $plain = preg_replace('/<[^>]+>/', '', $text);
        $value = $plain ?? $text;

        $this->lines[] = $value;

        if (str_contains($text, '<info>')) {
            $this->infos[] = $value;
        }

        if (str_contains($text, '<error>')) {
            $this->errors[] = $value;
        }

        parent::writeln($messages, $type);
    }
}
