<?php

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Nwidart\Modules\Facades\Module;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Helper to create module
function createTestModule($path, $name) {
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
    $this->testModuleName = 'TestModule';
    $this->testModulePath = base_path('Modules/TestModule');
    createTestModule($this->testModulePath, $this->testModuleName);
});

afterEach(function () {
    if (File::exists($this->testModulePath)) {
        File::deleteDirectory($this->testModulePath);
    }
    $publicSymlink = public_path('modules/'.strtolower($this->testModuleName));
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

    $this->artisan('freescout:module-install', ['module_alias' => strtolower($this->testModuleName)])
        ->expectsOutputToContain('symlink has been created')
        ->assertExitCode(0);

    $updatedModule = Module::find($this->testModuleName);
    expect($updatedModule->isEnabled())->toBeTrue();
});

test('creates symlink in public directory', function () {
    $publicSymlink = public_path('modules/'.strtolower($this->testModuleName));
    
    if (is_link($publicSymlink)) {
        unlink($publicSymlink);
    }
    
    expect(is_link($publicSymlink))->toBeFalse();

    $this->artisan('freescout:module-install', ['module_alias' => strtolower($this->testModuleName)])
        ->assertExitCode(0);

    expect(is_link($publicSymlink))->toBeTrue();
});

test('clears cache before installation', function () {
    $this->artisan('freescout:module-install', ['module_alias' => strtolower($this->testModuleName)])
        ->expectsOutputToContain('Clearing cache...')
        ->assertExitCode(0);
});

test('fails gracefully when module not found', function () {
    $this->artisan('freescout:module-install', ['module_alias' => 'nonexistentmodule'])
        ->expectsOutput('Module with the specified alias not found: nonexistentmodule')
        ->assertExitCode(0);
});

test('handles missing module json', function () {
    File::delete($this->testModulePath.'/module.json');

    $this->artisan('freescout:module-install', ['module_alias' => strtolower($this->testModuleName)])
        ->expectsOutput('Module with the specified alias not found: '.strtolower($this->testModuleName))
        ->assertExitCode(0);
});

test('handles invalid permissions', function () {
    $mock = Mockery::mock(Filesystem::class)->makePartial();
    $mock->shouldReceive('link')
        ->once()
        ->andThrow(new \Exception('Permission denied'));

    $this->instance('files', $mock);
    $this->instance(Filesystem::class, $mock);

    $this->artisan('freescout:module-install', ['module_alias' => strtolower($this->testModuleName)])
        ->expectsOutputToContain('Permission denied')
        ->assertExitCode(1);
});

test('validates module alias format', function () {
     $this->artisan('freescout:module-install', ['module_alias' => 'Invalid-Module-Name'])
        ->expectsOutput('Module with the specified alias not found: Invalid-Module-Name')
        ->assertExitCode(0);
});
