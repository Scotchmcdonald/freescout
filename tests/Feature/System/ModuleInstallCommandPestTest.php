<?php

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Nwidart\Modules\Facades\Module;

uses(RefreshDatabase::class);

// Helper to create module
function createTestModule($path, $name)
{
    if (! File::exists($path)) {
        File::makeDirectory($path, 0755, true);
    }
    if (! File::exists("$path/Resources/assets")) {
        File::makeDirectory("$path/Resources/assets", 0755, true);
    }

    $moduleJson = [
        'name' => $name,
        'alias' => strtolower($name),
        'description' => "Test module {$name}",
        'active' => true,
    ];

    File::put(
        "{$path}/module.json",
        json_encode($moduleJson, JSON_PRETTY_PRINT)
    );
}

beforeEach(function () {
    $this->originalModulesPath = config('modules.paths.modules');
    $this->originalDiscoveryPaths = config('modules.discovery.paths');
    $this->originalStatusesFile = config('modules.activators.file.statuses-file');

    $token = preg_replace('/[^a-z0-9]/', '', strtolower((string) (env('TEST_TOKEN') ?? getmypid())));
    $this->testModulesPath = storage_path('framework/testing/module-install-command/worker_'.$token);
    $this->testStatusesFile = $this->testModulesPath.'/modules_statuses.json';

    if (! File::isDirectory($this->testModulesPath)) {
        File::makeDirectory($this->testModulesPath, 0755, true);
    }
    if (! File::exists($this->testStatusesFile)) {
        File::put($this->testStatusesFile, '{}');
    }

    config([
        'modules.paths.modules' => $this->testModulesPath,
        'modules.discovery.paths' => [$this->testModulesPath],
        'modules.activators.file.statuses-file' => $this->testStatusesFile,
    ]);

    $uniqueSuffix = substr(str_replace('.', '', uniqid('', true)), -6);
    $this->testModuleName = 'TestModule'.$token.$uniqueSuffix;
    $this->testModuleAlias = strtolower($this->testModuleName);
    $this->testModulePath = $this->testModulesPath.'/'.$this->testModuleName;
    createTestModule($this->testModulePath, $this->testModuleName);
});

afterEach(function () {
    config([
        'modules.paths.modules' => $this->originalModulesPath,
        'modules.discovery.paths' => $this->originalDiscoveryPaths,
        'modules.activators.file.statuses-file' => $this->originalStatusesFile,
    ]);

    if (File::isDirectory($this->testModulesPath)) {
        File::deleteDirectory($this->testModulesPath);
    }

    $publicSymlink = public_path('modules/'.$this->testModuleAlias);
    if (is_link($publicSymlink)) {
        unlink($publicSymlink);
    } elseif (File::exists($publicSymlink)) {
        File::deleteDirectory($publicSymlink);
    }
});

test('installs specific module successfully', function () {
    $module = Module::find($this->testModuleName);
    expect($module)->not->toBeNull();

    // Disable the module
    $module->disable();
    expect($module->isEnabled())->toBeFalse();

    $this->artisan('freescout:module-install', ['module_alias' => $this->testModuleAlias])
        ->expectsOutputToContain('symlink has been created')
        ->assertExitCode(0);

    $updatedModule = Module::find($this->testModuleName);
    expect($updatedModule->isEnabled())->toBeTrue();
});

test('creates symlink in public directory', function () {
    $publicSymlink = public_path('modules/'.$this->testModuleAlias);

    if (is_link($publicSymlink)) {
        unlink($publicSymlink);
    }

    expect(is_link($publicSymlink))->toBeFalse();

    $this->artisan('freescout:module-install', ['module_alias' => $this->testModuleAlias])
        ->assertExitCode(0);

    expect(is_link($publicSymlink))->toBeTrue();
});

test('clears cache before installation', function () {
    $this->artisan('freescout:module-install', ['module_alias' => $this->testModuleAlias])
        ->expectsOutputToContain('Clearing cache...')
        ->assertExitCode(0);
});

test('fails gracefully when module not found', function () {
    $this->artisan('freescout:module-install', ['module_alias' => 'nonexistentmodule'])
        ->expectsOutput('Module with the specified alias not found: nonexistentmodule')
        ->assertExitCode(0);
});

test('handles invalid permissions', function () {
    $mock = Mockery::mock(Filesystem::class)->makePartial();
    $mock->shouldReceive('link')
        ->once()
        ->andThrow(new \Exception('Permission denied'));

    $this->instance('files', $mock);
    $this->instance(Filesystem::class, $mock);

    $this->artisan('freescout:module-install', ['module_alias' => $this->testModuleAlias])
        ->expectsOutputToContain('Permission denied')
        ->assertExitCode(1);
});

test('validates module alias format', function () {
    $this->artisan('freescout:module-install', ['module_alias' => 'Invalid-Module-Name'])
        ->expectsOutput('Module with the specified alias not found: Invalid-Module-Name')
        ->assertExitCode(0);
});
