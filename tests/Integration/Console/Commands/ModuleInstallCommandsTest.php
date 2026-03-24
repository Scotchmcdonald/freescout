<?php

declare(strict_types=1);

namespace Tests\Integration\Console\Commands;

use App\Console\Commands\ModuleInstall;
use Illuminate\Console\OutputStyle;
use Nwidart\Modules\Facades\Module as ModuleFacade;
use Nwidart\Modules\Module as NwidartModule;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\IntegrationTestCase;

class ModuleInstallCommandsTest extends IntegrationTestCase
{
    protected function tearDown(): void
    {
        $this->cleanupTestArtifacts();

        parent::tearDown();
    }

    public function test_command_metadata_is_correct(): void
    {
        $command = new ModuleInstall;

        $this->assertSame('freescout:module-install', $command->getName());
        $this->assertStringContainsString('install', strtolower($command->getDescription()));
        $this->assertFalse($command->getDefinition()->getArgument('module_alias')->isRequired());
        $this->assertTrue(method_exists($command, 'createModulePublicSymlink'));
    }

    public function test_handle_reports_missing_module_alias_after_clearing_cache(): void
    {
        ModuleFacade::shouldReceive('find')
            ->once()
            ->with('missing-module')
            ->andReturn(null);

        $command = new TestableModuleInstall;
        $command->argumentValue = 'missing-module';

        $exitCode = $command->handle();

        $this->assertSame(0, $exitCode);
        $this->assertSame([['cache:clear', []]], $command->commandCalls);
        $this->assertSame(['Module with the specified alias not found: missing-module'], $command->recordingOutput()->errors);
    }

    public function test_handle_reports_when_no_modules_are_available(): void
    {
        ModuleFacade::shouldReceive('all')
            ->once()
            ->andReturn([]);

        $command = new TestableModuleInstall;

        $exitCode = $command->handle();

        $this->assertSame(0, $exitCode);
        $this->assertSame([['cache:clear', []]], $command->commandCalls);
        $this->assertSame(['No modules found.'], $command->recordingOutput()->infos);
    }

    public function test_handle_installs_all_modules_after_confirmation(): void
    {
        $billing = $this->mockModule('Billing', 'billing');
        $support = $this->mockModule('Support', 'support');

        ModuleFacade::shouldReceive('all')
            ->once()
            ->andReturn([$billing, $support]);

        $command = new TestableModuleInstall;
        $command->recordingOutput()->confirmResult = true;
        $command->installResults = [0, 0];

        $exitCode = $command->handle();

        $this->assertSame(0, $exitCode);
        $this->assertSame([['cache:clear', []]], $command->commandCalls);
        $this->assertSame(['Billing', 'Support'], $command->installedModules);
        $this->assertSame(['Install all modules (Billing, Support)?'], $command->recordingOutput()->confirmQuestions);
    }

    public function test_handle_stops_when_an_installation_fails(): void
    {
        $billing = $this->mockModule('Billing', 'billing');
        $support = $this->mockModule('Support', 'support');

        ModuleFacade::shouldReceive('all')
            ->once()
            ->andReturn([$billing, $support]);

        $command = new TestableModuleInstall;
        $command->recordingOutput()->confirmResult = true;
        $command->installResults = [1];

        $exitCode = $command->handle();

        $this->assertSame(1, $exitCode);
        $this->assertSame(['Billing'], $command->installedModules);
    }

    public function test_install_module_enables_module_runs_migrations_and_skips_clear_cache_during_tests(): void
    {
        $module = $this->mockModule('Billing', 'billing');
        $module->shouldReceive('enable')->once();

        $command = new TestableModuleInstall;

        $exitCode = $command->runInstallModule($module);

        $this->assertSame(0, $exitCode);
        $this->assertSame(['Billing'], $command->symlinkedModules);
        $this->assertSame([
            ['module:migrate', ['module' => 'Billing', '--force' => true]],
        ], $command->commandCalls);
        $this->assertContains('Installing module: Billing', $command->recordingOutput()->lines);
        $this->assertContains('Clearing cache...', $command->recordingOutput()->lines);
    }

    public function test_install_module_returns_error_when_symlink_creation_fails(): void
    {
        $module = $this->mockModule('Billing', 'billing');
        $module->shouldReceive('enable')->once();

        $command = new TestableModuleInstall;
        $command->throwDuringSymlink = 'Cannot create symlink';

        $exitCode = $command->runInstallModule($module);

        $this->assertSame(1, $exitCode);
        $this->assertSame(['Cannot create symlink'], $command->recordingOutput()->errors);
    }

    public function test_create_module_public_symlink_creates_a_symlink_for_existing_assets(): void
    {
        $target = storage_path('framework/testing/module-assets-existing');
        $this->ensureDirectory($target);
        file_put_contents($target.'/app.js', 'console.log("test");');

        $module = $this->mockModule('TestModule', 'testmodule', $target);
        $command = $this->commandWithOutput(new ModuleInstall);

        $command->createModulePublicSymlink($module);

        $link = public_path('modules/testmodule');

        $this->assertTrue(is_link($link));
        $this->assertSame($target, readlink($link));
    }

    public function test_create_module_public_symlink_replaces_existing_directory(): void
    {
        $target = storage_path('framework/testing/module-assets-replace');
        $this->ensureDirectory($target);

        $link = public_path('modules/testmodule');
        $this->ensureDirectory($link);
        file_put_contents($link.'/old.txt', 'stale');

        $module = $this->mockModule('TestModule', 'testmodule', $target);
        $command = $this->commandWithOutput(new ModuleInstall);

        $command->createModulePublicSymlink($module);

        $this->assertTrue(is_link($link));
        $this->assertSame($target, readlink($link));
    }

    public function test_create_module_public_symlink_skips_when_assets_are_missing(): void
    {
        $module = $this->mockModule('MissingAssets', 'missingassets', storage_path('framework/testing/does-not-exist'));
        $command = $this->commandWithOutput(new ModuleInstall);

        $command->createModulePublicSymlink($module);

        $this->assertFalse(file_exists(public_path('modules/missingassets')));
        $this->assertFalse(is_link(public_path('modules/missingassets')));
    }

    private function mockModule(string $name, string $lowerName, ?string $assetsPath = null): NwidartModule
    {
        /** @var NwidartModule $module */
        $module = \Mockery::mock(NwidartModule::class);
        $module->shouldReceive('getName')->byDefault()->andReturn($name);
        $module->shouldReceive('getLowerName')->byDefault()->andReturn($lowerName);
        $module->shouldReceive('getExtraPath')
            ->byDefault()
            ->with('Resources/assets')
            ->andReturn($assetsPath ?? storage_path('framework/testing/'.$lowerName));

        return $module;
    }

    private function ensureDirectory(string $path): void
    {
        if (! is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }

    private function commandWithOutput(ModuleInstall $command): ModuleInstall
    {
        $property = new \ReflectionProperty(\Illuminate\Console\Command::class, 'output');
        $property->setAccessible(true);
        $property->setValue($command, new RecordingOutputStyle);

        return $command;
    }

    private function cleanupTestArtifacts(): void
    {
        $paths = [
            public_path('modules/testmodule'),
            public_path('modules/missingassets'),
            storage_path('framework/testing/module-assets-existing'),
            storage_path('framework/testing/module-assets-replace'),
        ];

        foreach ($paths as $path) {
            if (is_link($path) || is_file($path)) {
                @unlink($path);
                continue;
            }

            if (is_dir($path)) {
                app('files')->deleteDirectory($path);
            }
        }
    }
}

class TestableModuleInstall extends ModuleInstall
{
    public ?string $argumentValue = null;

    /** @var array<int, array{0:string,1:array<string,mixed>}> */
    public array $commandCalls = [];

    /** @var array<int, string> */
    public array $installedModules = [];

    /** @var array<int, int> */
    public array $installResults = [];

    /** @var array<int, string> */
    public array $symlinkedModules = [];

    public ?string $throwDuringSymlink = null;

    private RecordingOutputStyle $recordingOutput;

    public function __construct()
    {
        parent::__construct();

        $this->recordingOutput = new RecordingOutputStyle;

        $property = new \ReflectionProperty(\Illuminate\Console\Command::class, 'output');
        $property->setAccessible(true);
        $property->setValue($this, $this->recordingOutput);
    }

    public function argument($key = null)
    {
        if ($key === 'module_alias') {
            return $this->argumentValue;
        }

        return parent::argument($key);
    }

    public function recordingOutput(): RecordingOutputStyle
    {
        return $this->recordingOutput;
    }

    public function call($command, array $arguments = [])
    {
        $this->commandCalls[] = [$command, $arguments];

        return 0;
    }

    public function runInstallModule(NwidartModule $module): int
    {
        return $this->installModule($module);
    }

    protected function installModule(NwidartModule $module): int
    {
        $this->installedModules[] = $module->getName();

        if ($this->installResults !== []) {
            return array_shift($this->installResults);
        }

        return parent::installModule($module);
    }

    public function createModulePublicSymlink($module)
    {
        if ($this->throwDuringSymlink !== null) {
            throw new \RuntimeException($this->throwDuringSymlink);
        }

        $this->symlinkedModules[] = $module->getName();
    }
}

class RecordingOutputStyle extends OutputStyle
{
    public bool $confirmResult = false;

    /** @var array<int, string> */
    public array $lines = [];

    /** @var array<int, string> */
    public array $infos = [];

    /** @var array<int, string> */
    public array $errors = [];

    /** @var array<int, string> */
    public array $confirmQuestions = [];

    public function __construct()
    {
        parent::__construct(new ArrayInput([]), new BufferedOutput);
    }

    public function confirm(string $question, bool $default = true): bool
    {
        $this->confirmQuestions[] = $question;

        return $this->confirmResult;
    }

    public function writeln(string|iterable $messages, int $type = self::OUTPUT_NORMAL): void
    {
        $messageText = is_iterable($messages) ? implode(PHP_EOL, iterator_to_array((function () use ($messages) {
            foreach ($messages as $message) {
                yield (string) $message;
            }
        })())) : (string) $messages;

        $plain = preg_replace('/<[^>]+>/', '', $messageText);
        $text = $plain ?? $messageText;

        $this->lines[] = $text;

        if (str_contains($messageText, '<info>')) {
            $this->infos[] = $text;
        }

        if (str_contains($messageText, '<error>')) {
            $this->errors[] = $text;
        }

        parent::writeln($messages, $type);
    }
}
