<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\AfterAppUpdate;
use App\Console\Commands\CheckRequirements;
use App\Console\Commands\ClearCache;
use App\Console\Commands\CreateUser;
use App\Console\Commands\GenerateVars;
use App\Console\Commands\LogoutUsers;
use App\Console\Commands\ModuleBuild;
use App\Console\Commands\ModuleUpdate;
use App\Console\Commands\UpdateFolderCounters;
use Tests\UnitTestCase;

class RemainingCommandsTest extends UnitTestCase
{
    // AfterAppUpdate Tests
    
    public function test_after_app_update_command_can_be_instantiated(): void
    {
        $command = new AfterAppUpdate();
        
        $this->assertInstanceOf(AfterAppUpdate::class, $command);
    }

    public function test_after_app_update_has_correct_signature(): void
    {
        $command = new AfterAppUpdate();
        
        $this->assertEquals('freescout:after-app-update', $command->getName());
    }

    public function test_after_app_update_handle_executes_successfully(): void
    {
        $this->artisan('freescout:after-app-update')
            ->assertExitCode(0);
    }

    // CheckRequirements Tests
    
    public function test_check_requirements_command_can_be_instantiated(): void
    {
        $command = new CheckRequirements();
        
        $this->assertInstanceOf(CheckRequirements::class, $command);
    }

    public function test_check_requirements_has_correct_signature(): void
    {
        $command = new CheckRequirements();
        
        $this->assertEquals('freescout:check-requirements', $command->getName());
    }

    public function test_check_requirements_handle_executes_successfully(): void
    {
        $this->artisan('freescout:check-requirements')
            ->assertExitCode(0);
    }

    // ClearCache Tests
    
    public function test_clear_cache_command_can_be_instantiated(): void
    {
        $command = new ClearCache();
        
        $this->assertInstanceOf(ClearCache::class, $command);
    }

    public function test_clear_cache_has_correct_signature(): void
    {
        $command = new ClearCache();
        
        $this->assertEquals('freescout:clear-cache', $command->getName());
    }

    public function test_clear_cache_handle_executes_successfully(): void
    {
        $this->artisan('freescout:clear-cache')
            ->assertExitCode(0);
    }

    // CreateUser Tests
    
    public function test_create_user_command_can_be_instantiated(): void
    {
        $command = new CreateUser();
        
        $this->assertInstanceOf(CreateUser::class, $command);
    }

    public function test_create_user_has_correct_signature(): void
    {
        $command = new CreateUser();
        
        $this->assertEquals('freescout:create-user', $command->getName());
    }

    // GenerateVars Tests
    
    public function test_generate_vars_command_can_be_instantiated(): void
    {
        $command = new GenerateVars();
        
        $this->assertInstanceOf(GenerateVars::class, $command);
    }

    public function test_generate_vars_has_correct_signature(): void
    {
        $command = new GenerateVars();
        
        $this->assertEquals('freescout:generate-vars', $command->getName());
    }

    public function test_generate_vars_handle_executes_successfully(): void
    {
        $this->artisan('freescout:generate-vars')
            ->assertExitCode(0);
    }

    // LogoutUsers Tests
    
    public function test_logout_users_command_can_be_instantiated(): void
    {
        $command = new LogoutUsers();
        
        $this->assertInstanceOf(LogoutUsers::class, $command);
    }

    public function test_logout_users_has_correct_signature(): void
    {
        $command = new LogoutUsers();
        
        $this->assertEquals('freescout:logout-users', $command->getName());
    }

    public function test_logout_users_handle_executes_successfully(): void
    {
        $this->artisan('freescout:logout-users')
            ->assertExitCode(0);
    }

    // ModuleBuild Tests
    
    public function test_module_build_command_can_be_instantiated(): void
    {
        $command = new ModuleBuild();
        
        $this->assertInstanceOf(ModuleBuild::class, $command);
    }

    public function test_module_build_has_correct_signature(): void
    {
        $command = new ModuleBuild();
        
        $this->assertEquals('freescout:module-build', $command->getName());
    }

    // ModuleUpdate Tests
    
    public function test_module_update_command_can_be_instantiated(): void
    {
        $command = new ModuleUpdate();
        
        $this->assertInstanceOf(ModuleUpdate::class, $command);
    }

    public function test_module_update_has_correct_signature(): void
    {
        $command = new ModuleUpdate();
        
        $this->assertEquals('freescout:module-update', $command->getName());
    }

    // UpdateFolderCounters Tests
    
    public function test_update_folder_counters_command_can_be_instantiated(): void
    {
        $command = new UpdateFolderCounters();
        
        $this->assertInstanceOf(UpdateFolderCounters::class, $command);
    }

    public function test_update_folder_counters_has_correct_signature(): void
    {
        $command = new UpdateFolderCounters();
        
        $this->assertEquals('freescout:update-folder-counters', $command->getName());
    }

    public function test_update_folder_counters_handle_executes_successfully(): void
    {
        $this->artisan('freescout:update-folder-counters')
            ->assertExitCode(0);
    }
}
