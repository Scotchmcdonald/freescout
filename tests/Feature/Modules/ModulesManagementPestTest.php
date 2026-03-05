<?php

use App\Models\User;
use App\Models\Module;
use Illuminate\Support\Facades\Artisan;
use Nwidart\Modules\Facades\Module as NwidartModule;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

test('modules index requires admin', function () {
    // Create 'user' with type 0 so they are not considered 'internal' by the admin middleware
    $user = User::factory()->create(['role' => User::ROLE_USER, 'type' => 0]);

    $this->actingAs($user)
        ->get(route('modules'))
        ->assertForbidden();
});

test('modules index shows all modules', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    Module::factory()->count(3)->create();

    $this->actingAs($admin)
        ->get(route('modules'))
        ->assertOk()
        ->assertViewIs('modules.index')
        ->assertViewHas('modules');
});

test('modules can activate module', function () {
    if (!class_exists(\Nwidart\Modules\Module::class)) {
        $this->markTestSkipped('Nwidart Modules package not installed');
    }

    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $module = Module::factory()->create(['active' => false]);

    $nwidartModule = Mockery::mock(\Nwidart\Modules\Module::class);
    $nwidartModule->shouldReceive('getName')->andReturn('TestModule');
    $nwidartModule->shouldReceive('enable')->once();
    
    NwidartModule::shouldReceive('find')
        ->with($module->alias)
        ->andReturn($nwidartModule);
        
    Artisan::shouldReceive('call')->andReturn(0);
    Log::shouldReceive('info')->withAnyArgs();

    $this->actingAs($admin)
        ->post(route('modules.enable', $module->alias))
        ->assertOk();
});

test('modules can deactivate module', function () {
    if (!class_exists(\Nwidart\Modules\Module::class)) {
        $this->markTestSkipped('Nwidart Modules package not installed');
    }

    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $module = Module::factory()->create(['active' => true]);

    $nwidartModule = Mockery::mock(\Nwidart\Modules\Module::class);
    $nwidartModule->shouldReceive('getName')->andReturn('TestModule');
    $nwidartModule->shouldReceive('disable')->once();
    
    NwidartModule::shouldReceive('find')
        ->with($module->alias)
        ->andReturn($nwidartModule);
        
    Artisan::shouldReceive('call')->andReturn(0);

    $this->actingAs($admin)
        ->post(route('modules.disable', $module->alias))
        ->assertOk();
});

test('modules can delete module', function () {
    // WORKAROUND: Use array driver to allow File facade mocking
    config(['session.driver' => 'array']);

    if (!class_exists(\Nwidart\Modules\Module::class)) {
        $this->markTestSkipped('Nwidart Modules package not installed');
    }

    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $module = Module::factory()->create();

    $nwidartModule = Mockery::mock(\Nwidart\Modules\Module::class);
    $nwidartModule->shouldIgnoreMissing();
    $nwidartModule->shouldReceive('getName')->andReturn('TestModule');
    $nwidartModule->shouldReceive('isEnabled')->andReturn(true);
    $nwidartModule->shouldReceive('disable')->once();
    $nwidartModule->shouldReceive('delete')->andReturn(true);
    $nwidartModule->shouldReceive('getPath')->andReturn('/tmp/module/path');
    
    NwidartModule::shouldReceive('find')
        ->with($module->alias)
        ->andReturn($nwidartModule);
        
    Artisan::shouldReceive('call')->andReturn(0);
    
    File::shouldReceive('deleteDirectory')->with('/tmp/module/path')->andReturn(true);
    File::shouldReceive('exists')->andReturn(true);
    File::shouldReceive('get')->andReturn('{"name": "TestModule"}');
    File::shouldReceive('getRequire')->andReturn([]);

    $this->actingAs($admin)
        ->delete(route('modules.delete', $module->alias))
        ->assertOk();
});

test('modules log important actions', function () {
    if (!class_exists(\Nwidart\Modules\Module::class)) {
        $this->markTestSkipped('Nwidart Modules package not installed');
    }

    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $module = Module::factory()->create(); // alias needed?
    // Factory usually creates alias.

    $nwidartModule = Mockery::mock(\Nwidart\Modules\Module::class);
    $nwidartModule->shouldReceive('getName')->andReturn('TestModule');
    $nwidartModule->shouldReceive('enable')->once();
    
    NwidartModule::shouldReceive('find')
        ->with($module->alias)
        ->andReturn($nwidartModule);
        
    Artisan::shouldReceive('call')->andReturn(0);

    Log::shouldReceive('info')
        ->once()
        ->with(\Mockery::pattern('/Module.*activated/'));

    // Use the correct route name 'modules.enable' (formerly aliased as 'modules.activate')
    $this->actingAs($admin)->post(route('modules.enable', $module->alias));
});

test('guest cannot view modules list', function () {
    $this->get(route('modules'))
        ->assertRedirect(route('login'));
});

test('enable module returns error for non existent module', function () {
    if (!class_exists(\Nwidart\Modules\Module::class)) {
        $this->markTestSkipped('Nwidart Modules package not installed');
    }

    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    
    // Mock Nwidart to return null for find
    NwidartModule::shouldReceive('find')
        ->with('non-existent-module')
        ->andReturn(null);

    $this->actingAs($admin)
        ->postJson(route('modules.enable', 'non-existent-module'))
        ->assertNotFound();
});
