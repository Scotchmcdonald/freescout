<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use Illuminate\Support\Facades\Artisan;
use Nwidart\Modules\Facades\Module as ModuleFacade;
use Mockery;
use App\Services\ModuleSourceService;

it('reports modules up to date when no modules found', function () {
    ModuleFacade::shouldReceive('allEnabled')->andReturn([])->byDefault();
    ModuleFacade::shouldReceive('all')
        ->once()
        ->andReturn([]);
        
    $mockSource = Mockery::mock(ModuleSourceService::class);
    $mockSource->shouldReceive('getModules')->andReturn([]);
    $this->app->instance(ModuleSourceService::class, $mockSource);

    $this->artisan('freescout:module-update')
        ->expectsOutput('All modules are up-to-date')
        ->assertExitCode(0);
});

