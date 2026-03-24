<?php

declare(strict_types=1);

namespace Tests\Integration\Console\Commands;

use App\Console\Commands\ModuleBuild;
use Illuminate\Console\OutputStyle;
use Nwidart\Modules\Facades\Module as ModuleFacade;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\IntegrationTestCase;

/** @group console */
class ModuleBuildTest extends IntegrationTestCase
{
    protected function tearDown(): void
    {
        $this->cleanupPath(public_path('modules/test-build'));
        $this->cleanupPath(public_path('modules/vars-build'));
        $this->cleanupPath(public_path('modules/missing-symlink'));

        parent::tearDown();
    }

    public function test_command_metadata_is_correct(): void
    {
        $command = new ModuleBuild;

        $this->assertSame('freescout:module-build', $command->getName());
        $this->assertStringContainsString('build module', strtolower($command->getDescription()));
        $this->assertTrue($command->getDefinition()->hasArgument('module_alias'));
        $this->assertFalse($command->getDefinition()->getArgument('module_alias')->isRequired());
    }

    public function test_handle_returns_error_when_no_modules_exist(): void
    {
        ModuleFacade::shouldReceive('all')->once()->andReturn([]);

        $command = new TestableModuleBuildCommand;

        $exitCode = $command->handle();

        $this->assertSame(1, $exitCode);
        $this->assertContains('No modules found', $command->recordingOutput()->errors);
    }

    public function test_handle_builds_all_modules_when_alias_is_not_provided(): void
    {
        $moduleA = $this->mockModule('Module A', 'module-a');
        $moduleB = $this->mockModule('Module B', 'module-b');

        ModuleFacade::shouldReceive('all')->once()->andReturn([$moduleA, $moduleB]);
        ModuleFacade::shouldReceive('all')->once()->andReturn([$moduleA, $moduleB]);

        $command = new TestableModuleBuildCommand;

        $exitCode = $command->handle();

        $this->assertSame(0, $exitCode);
        $this->assertSame(['module-a', 'module-b'], $command->builtAliases);
        $this->assertContains('Building all modules...', $command->recordingOutput()->infos);
        $this->assertContains('Module build completed!', $command->recordingOutput()->infos);
    }

    public function test_handle_returns_error_when_specific_alias_is_not_found(): void
    {
        ModuleFacade::shouldReceive('findByAlias')->once()->with('missing')->andReturn(null);

        $command = new TestableModuleBuildCommand;
        $command->moduleAlias = 'missing';

        $exitCode = $command->handle();

        $this->assertSame(1, $exitCode);
        $this->assertContains('Module with the specified alias not found: missing', $command->recordingOutput()->errors);
    }

    public function test_handle_builds_specific_module_when_alias_is_provided(): void
    {
        $module = $this->mockModule('Module A', 'module-a');

        ModuleFacade::shouldReceive('findByAlias')->once()->with('module-a')->andReturn($module);

        $command = new TestableModuleBuildCommand;
        $command->moduleAlias = 'module-a';

        $exitCode = $command->handle();

        $this->assertSame(0, $exitCode);
        $this->assertSame(['module-a'], $command->builtAliases);
        $this->assertContains('Module build completed!', $command->recordingOutput()->infos);
    }

    public function test_build_module_reports_missing_public_symlink(): void
    {
        $module = $this->mockModule('Missing Symlink', 'missing-symlink');

        $command = new ExposedModuleBuildCommand;
        $command->runBuildModule($module);

        $this->assertTrue(
            collect($command->recordingOutput()->errors)
                ->contains(fn (string $line): bool => str_contains($line, 'Public symlink [') && str_contains($line, 'not found'))
        );
    }

    public function test_build_vars_skips_generation_when_view_does_not_exist(): void
    {
        $module = $this->mockModule('Test Build', 'test-build');

        $command = new ExposedModuleBuildCommand;
        $command->runBuildVars($module);

        $this->assertTrue(
            collect($command->recordingOutput()->comments)
                ->contains(fn (string $line): bool => str_contains($line, 'View test-build::js/vars not found'))
        );
    }

    public function test_build_vars_creates_vars_js_when_view_exists(): void
    {
        config(['app.locales' => ['en', 'fr']]);

        $module = $this->mockModule('Vars Build', 'vars-build');
        $viewBase = storage_path('framework/testing/modulebuild/views/vars-build');
        $viewDir = $viewBase.'/js';

        if (! is_dir($viewDir)) {
            mkdir($viewDir, 0755, true);
        }

        file_put_contents($viewDir.'/vars.blade.php', 'window.moduleLocales = @json($locales);');
        view()->addNamespace('vars-build', $viewBase);

        $targetRoot = public_path('modules/vars-build');
        $this->cleanupPath($targetRoot);

        $command = new ExposedModuleBuildCommand;
        $command->runBuildVars($module);

        $targetFile = public_path('modules/vars-build/js/vars.js');

        $this->assertFileExists($targetFile);
        $this->assertStringContainsString('["en","fr"]', (string) file_get_contents($targetFile));
    }

    public function test_build_vars_catches_exceptions_and_reports_error(): void
    {
        $module = \Mockery::mock();
        $module->shouldReceive('getAlias')->andThrow(new \RuntimeException('boom'));
        $module->shouldReceive('getName')->andReturn('Exploding Module');

        $command = new ExposedModuleBuildCommand;
        $command->runBuildVars($module);

        $this->assertTrue(
            collect($command->recordingOutput()->errors)
                ->contains(fn (string $line): bool => str_contains($line, 'Error building vars for Exploding Module: boom'))
        );
    }

    private function mockModule(string $name, string $alias)
    {
        $module = \Mockery::mock();
        $module->shouldReceive('getName')->byDefault()->andReturn($name);
        $module->shouldReceive('getAlias')->byDefault()->andReturn($alias);

        return $module;
    }

    private function cleanupPath(string $path): void
    {
        if (is_link($path) || is_file($path)) {
            @unlink($path);

            return;
        }

        if (is_dir($path)) {
            app('files')->deleteDirectory($path);
        }
    }
}

class TestableModuleBuildCommand extends ModuleBuild
{
    public ?string $moduleAlias = null;

    /** @var array<int, string> */
    public array $builtAliases = [];

    private ModuleBuildRecordingOutputStyle $recordingOutput;

    public function __construct()
    {
        parent::__construct();

        $this->recordingOutput = new ModuleBuildRecordingOutputStyle;

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

    protected function buildModule($module): void
    {
        $this->builtAliases[] = (string) $module->getAlias();
    }

    public function recordingOutput(): ModuleBuildRecordingOutputStyle
    {
        return $this->recordingOutput;
    }
}

class ExposedModuleBuildCommand extends ModuleBuild
{
    private ModuleBuildRecordingOutputStyle $recordingOutput;

    public function __construct()
    {
        parent::__construct();

        $this->recordingOutput = new ModuleBuildRecordingOutputStyle;

        $property = new \ReflectionProperty(\Illuminate\Console\Command::class, 'output');
        $property->setAccessible(true);
        $property->setValue($this, $this->recordingOutput);
    }

    public function runBuildModule($module): void
    {
        $this->buildModule($module);
    }

    public function runBuildVars($module): void
    {
        $this->buildVars($module);
    }

    public function recordingOutput(): ModuleBuildRecordingOutputStyle
    {
        return $this->recordingOutput;
    }
}

class ModuleBuildRecordingOutputStyle extends OutputStyle
{
    /** @var array<int, string> */
    public array $infos = [];

    /** @var array<int, string> */
    public array $errors = [];

    /** @var array<int, string> */
    public array $comments = [];

    public function __construct()
    {
        parent::__construct(new ArrayInput([]), new BufferedOutput);
    }

    public function writeln(string|iterable $messages, int $type = self::OUTPUT_NORMAL): void
    {
        $text = is_iterable($messages)
            ? implode(PHP_EOL, array_map(static fn (mixed $m): string => (string) $m, iterator_to_array($messages)))
            : (string) $messages;

        $plain = preg_replace('/<[^>]+>/', '', $text);
        $value = $plain ?? $text;

        if (str_contains($text, '<info>')) {
            $this->infos[] = $value;
        }

        if (str_contains($text, '<error>')) {
            $this->errors[] = $value;
        }

        if (str_contains($text, '<comment>')) {
            $this->comments[] = $value;
        }

        parent::writeln($messages, $type);
    }
}
