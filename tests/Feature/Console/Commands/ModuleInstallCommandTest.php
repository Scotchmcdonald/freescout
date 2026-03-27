<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use Illuminate\Support\Facades\Artisan;
use Nwidart\Modules\Facades\Module as ModuleFacade;
use Mockery;

// TestCase and RefreshDatabase are inherited from Pest.php Feature binding

it('shows not found when aliased module is missing', function () {
    ModuleFacade::shouldReceive('allEnabled')->andReturn([])->byDefault();
    ModuleFacade::shouldReceive('find')
        ->with('missing_alias')
        ->once()
        ->andReturn(null);

    $this->artisan('freescout:module-install', ['module_alias' => 'missing_alias'])
        ->expectsOutput('Module with the specified alias not found: missing_alias')
        ->assertExitCode(0);
});

it('installs a specific module when alias is provided', function () {
    $mockModule = Mockery::mock(\Nwidart\Modules\Module::class);
    $mockModule->shouldReceive('getName')->andReturn('TestModule');
    $mockModule->shouldReceive('getLowerName')->andReturn('testmodule');
    $mockModule->shouldReceive('enable')->once();
    $mockModule->shouldReceive('getExtraPath')->with('Resources/assets')->andReturn('/fake/path/to/assets');

    ModuleFacade::shouldReceive('allEnabled')->andReturn([])->byDefault();
    ModuleFacade::shouldReceive('find')
        ->with('testmodule')
        ->once()
        ->andReturn($mockModule);

    Artisan::command('module:migrate {module} {--force}', function () {
        return 0;
    });

    $this->artisan('freescout:module-install', ['module_alias' => 'testmodule'])
        ->expectsOutput('Installing module: TestModule')
        ->assertExitCode(0);
});

it('installs all modules when no alias is provided', function () {
    $mockModule1 = Mockery::mock(\Nwidart\Modules\Module::class);
    $mockModule1->shouldReceive('getName')->andReturn('Module1');
    $mockModule1->shouldReceive('getLowerName')->andReturn('module1');
    $mockModule1->shouldReceive('enable')->once();
    $mockModule1->shouldReceive('getExtraPath')->with('Resources/assets')->andReturn('/fake/path/to/assets1');

    $mockModule2 = Mockery::mock(\Nwidart\Modules\Module::class);
    $mockModule2->shouldReceive('getName')->andReturn('Module2');
    $mockModule2->shouldReceive('getLowerName')->andReturn('module2');
    $mockModule2->shouldReceive('enable')->once();
    $mockModule2->shouldReceive('getExtraPath')->with('Resources/assets')->andReturn('/fake/path/to/assets2');

    ModuleFacade::shouldReceive('allEnabled')->andReturn([])->byDefault();
    ModuleFacade::shouldReceive('all')
        ->once()
        ->andReturn([$mockModule1, $mockModule2]);

    Artisan::command('module:migrate {module} {--force}', function () {
        return 0;
    });

    $this->artisan('freescout:module-install')
        ->expectsConfirmation('Install all modules (Module1, Module2)?', 'yes')
        ->expectsOutput('Installing module: Module1')
        ->expectsOutput('Installing module: Module2')
        ->assertExitCode(0);
});

it('stops on first error when installing all modules', function () {
    $mockModule1 = Mockery::mock(\Nwidart\Modules\Module::class);
    $mockModule1->shouldReceive('getName')->andReturn('Module1');
    $mockModule1->shouldReceive('getLowerName')->andReturn('module1');
    $mockModule1->shouldReceive('enable')->andThrow(new \Exception('Install Error!'));

    $mockModule2 = Mockery::mock(\Nwidart\Modules\Module::class);
    $mockModule2->shouldReceive('getName')->andReturn('Module2');

    // It should not reach module2 because module1 stops it

    ModuleFacade::shouldReceive('allEnabled')->andReturn([])->byDefault();
    ModuleFacade::shouldReceive('all')
        ->once()
        ->andReturn([$mockModule1, $mockModule2]);

    $this->artisan('freescout:module-install')
        ->expectsConfirmation('Install all modules (Module1, Module2)?', 'yes')
        ->expectsOutput('Installing module: Module1')
        ->expectsOutput('Install Error!')
        ->assertExitCode(1);
});

it('reports no modules found', function () {
    ModuleFacade::shouldReceive('allEnabled')->andReturn([])->byDefault();
    ModuleFacade::shouldReceive('all')
        ->once()
        ->andReturn([]);

    $this->artisan('freescout:module-install')
        ->expectsOutput('No modules found.')
        ->assertExitCode(0);
});

