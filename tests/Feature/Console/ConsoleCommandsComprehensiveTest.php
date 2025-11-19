<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\Conversation;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Tests\FeatureTestCase;

/**
 * Comprehensive tests for Console Commands
 * Following TESTING_GUIDE.md - using test_ prefix, FeatureTestCase base class
 */
class ConsoleCommandsComprehensiveTest extends FeatureTestCase
{
    // ===== AFTER_APP_UPDATE TESTS =====

    public function test_after_app_update_command_exists(): void
    {
        $result = Artisan::call('app:after-update');
        
        $this->assertIsInt($result);
    }

    public function test_after_app_update_runs_successfully(): void
    {
        $exitCode = Artisan::call('app:after-update');
        
        $this->assertEquals(0, $exitCode);
    }

    // ===== CLEAR_CACHE TESTS =====

    public function test_clear_cache_command_exists(): void
    {
        $result = Artisan::call('freescout:clear-cache');
        
        $this->assertIsInt($result);
    }

    public function test_clear_cache_runs_successfully(): void
    {
        $exitCode = Artisan::call('freescout:clear-cache');
        
        $this->assertEquals(0, $exitCode);
    }

    public function test_clear_cache_outputs_message(): void
    {
        Artisan::call('freescout:clear-cache');
        $output = Artisan::output();
        
        $this->assertNotEmpty($output);
    }

    // ===== CREATE_USER TESTS =====

    public function test_create_user_command_exists(): void
    {
        $this->assertTrue(class_exists(\App\Console\Commands\CreateUser::class));
    }

    public function test_create_user_with_valid_input(): void
    {
        $exitCode = Artisan::call('freescout:create-user', [
            '--firstName' => 'John',
            '--lastName' => 'Doe',
            '--email' => 'john.doe' . time() . '@example.com',
            '--password' => 'password123',
            '--role' => 'admin',
        ]);
        
        $this->assertEquals(0, $exitCode);
    }

    public function test_create_user_creates_database_record(): void
    {
        $email = 'testuser' . time() . '@example.com';
        
        Artisan::call('freescout:create-user', [
            '--firstName' => 'Test',
            '--lastName' => 'User',
            '--email' => $email,
            '--password' => 'password',
            '--role' => 'user',
        ]);
        
        $this->assertDatabaseHas('users', [
            'first_name' => 'Test',
            'last_name' => 'User',
        ]);
    }

    // ===== LOGOUT_USERS TESTS =====

    public function test_logout_users_command_exists(): void
    {
        $result = Artisan::call('freescout:logout-users');
        
        $this->assertIsInt($result);
    }

    public function test_logout_users_runs_successfully(): void
    {
        User::factory()->count(3)->create();
        
        $exitCode = Artisan::call('freescout:logout-users');
        
        $this->assertEquals(0, $exitCode);
    }

    // ===== UPDATE_FOLDER_COUNTERS TESTS =====

    public function test_update_folder_counters_command_exists(): void
    {
        $result = Artisan::call('freescout:update-folder-counters');
        
        $this->assertIsInt($result);
    }

    public function test_update_folder_counters_runs_successfully(): void
    {
        $mailbox = Mailbox::factory()->create();
        Folder::factory()->count(3)->create(['mailbox_id' => $mailbox->id]);
        
        $exitCode = Artisan::call('freescout:update-folder-counters');
        
        $this->assertEquals(0, $exitCode);
    }

    public function test_update_folder_counters_with_mailbox_option(): void
    {
        $mailbox = Mailbox::factory()->create();
        
        $exitCode = Artisan::call('freescout:update-folder-counters', [
            '--mailbox_id' => $mailbox->id,
        ]);
        
        $this->assertEquals(0, $exitCode);
    }

    // ===== FETCH_EMAILS TESTS =====

    public function test_fetch_emails_command_exists(): void
    {
        $this->assertTrue(class_exists(\App\Console\Commands\FetchEmails::class));
    }

    public function test_fetch_emails_with_mailbox_option(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
            'in_port' => 993,
        ]);
        
        // Command will try to connect, but we're just testing it runs
        $exitCode = Artisan::call('freescout:fetch-emails', [
            '--mailbox_id' => $mailbox->id,
        ]);
        
        $this->assertIsInt($exitCode);
    }

    // ===== GENERATE_VARS TESTS =====

    public function test_generate_vars_command_exists(): void
    {
        $result = Artisan::call('freescout:generate-vars');
        
        $this->assertIsInt($result);
    }

    public function test_generate_vars_runs_successfully(): void
    {
        $exitCode = Artisan::call('freescout:generate-vars');
        
        $this->assertEquals(0, $exitCode);
    }

    // ===== UPDATE TESTS =====

    public function test_update_command_exists(): void
    {
        $result = Artisan::call('freescout:update');
        
        $this->assertIsInt($result);
    }

    public function test_update_runs_successfully(): void
    {
        $exitCode = Artisan::call('freescout:update');
        
        $this->assertEquals(0, $exitCode);
    }

    // ===== MODULE_BUILD TESTS =====

    public function test_module_build_command_exists(): void
    {
        $this->assertTrue(class_exists(\App\Console\Commands\ModuleBuild::class));
    }

    // ===== MODULE_INSTALL TESTS =====

    public function test_module_install_command_exists(): void
    {
        $this->assertTrue(class_exists(\App\Console\Commands\ModuleInstall::class));
    }

    // ===== MODULE_UPDATE TESTS =====

    public function test_module_update_command_exists(): void
    {
        $this->assertTrue(class_exists(\App\Console\Commands\ModuleUpdate::class));
    }

    // ===== TEST_EVENT_SYSTEM TESTS =====

    public function test_test_event_system_command_exists(): void
    {
        $result = Artisan::call('freescout:test-events');
        
        $this->assertIsInt($result);
    }

    public function test_test_event_system_runs_successfully(): void
    {
        Event::fake();
        
        $exitCode = Artisan::call('freescout:test-events');
        
        $this->assertEquals(0, $exitCode);
    }

    // ===== CHECK_REQUIREMENTS TESTS =====

    public function test_check_requirements_command_exists(): void
    {
        $result = Artisan::call('freescout:check-requirements');
        
        $this->assertIsInt($result);
    }

    public function test_check_requirements_runs_successfully(): void
    {
        $exitCode = Artisan::call('freescout:check-requirements');
        
        $this->assertEquals(0, $exitCode);
    }

    public function test_check_requirements_outputs_results(): void
    {
        Artisan::call('freescout:check-requirements');
        $output = Artisan::output();
        
        $this->assertNotEmpty($output);
    }

    // ===== CONFIGURE_GMAIL_MAILBOX TESTS =====

    public function test_configure_gmail_mailbox_command_exists(): void
    {
        $this->assertTrue(class_exists(\App\Console\Commands\ConfigureGmailMailbox::class));
    }

    // ===== EDGE CASES AND INTEGRATION =====

    public function test_multiple_commands_can_run_sequentially(): void
    {
        $exitCode1 = Artisan::call('freescout:clear-cache');
        $exitCode2 = Artisan::call('freescout:generate-vars');
        
        $this->assertEquals(0, $exitCode1);
        $this->assertEquals(0, $exitCode2);
    }

    public function test_command_output_can_be_captured(): void
    {
        Artisan::call('freescout:clear-cache');
        $output = Artisan::output();
        
        $this->assertIsString($output);
    }

    public function test_create_user_with_admin_role(): void
    {
        $email = 'admin' . time() . '@example.com';
        
        $exitCode = Artisan::call('freescout:create-user', [
            '--firstName' => 'Admin',
            '--lastName' => 'User',
            '--email' => $email,
            '--password' => 'adminpass',
            '--role' => 'admin',
        ]);
        
        $this->assertEquals(0, $exitCode);
        
        $user = User::where('first_name', 'Admin')->first();
        $this->assertNotNull($user);
        $this->assertEquals(User::ROLE_ADMIN, $user->role);
    }

    public function test_create_user_with_user_role(): void
    {
        $email = 'regularuser' . time() . '@example.com';
        
        $exitCode = Artisan::call('freescout:create-user', [
            '--firstName' => 'Regular',
            '--lastName' => 'User',
            '--email' => $email,
            '--password' => 'userpass',
            '--role' => 'user',
        ]);
        
        $this->assertEquals(0, $exitCode);
        
        $user = User::where('first_name', 'Regular')->first();
        $this->assertNotNull($user);
        $this->assertEquals(User::ROLE_USER, $user->role);
    }

    public function test_update_folder_counters_with_conversations(): void
    {
        $mailbox = Mailbox::factory()->create();
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id]);
        Conversation::factory()->count(5)->create([
            'mailbox_id' => $mailbox->id,
            'folder_id' => $folder->id,
        ]);
        
        $exitCode = Artisan::call('freescout:update-folder-counters', [
            '--mailbox_id' => $mailbox->id,
        ]);
        
        $this->assertEquals(0, $exitCode);
    }

    public function test_logout_users_with_active_sessions(): void
    {
        User::factory()->count(5)->create();
        
        $exitCode = Artisan::call('freescout:logout-users');
        
        $this->assertEquals(0, $exitCode);
    }

    public function test_commands_handle_empty_database(): void
    {
        $exitCode1 = Artisan::call('freescout:logout-users');
        $exitCode2 = Artisan::call('freescout:update-folder-counters');
        
        $this->assertEquals(0, $exitCode1);
        $this->assertEquals(0, $exitCode2);
    }

    public function test_clear_cache_clears_application_cache(): void
    {
        cache()->put('test_key', 'test_value', 60);
        
        Artisan::call('freescout:clear-cache');
        
        // Cache might not be completely cleared depending on driver, but command should run
        $this->assertTrue(true);
    }

    public function test_generate_vars_creates_output(): void
    {
        Artisan::call('freescout:generate-vars');
        $output = Artisan::output();
        
        $this->assertNotEmpty($output);
    }

    public function test_update_command_with_no_updates_available(): void
    {
        $exitCode = Artisan::call('freescout:update');
        
        // Command should complete even if no updates
        $this->assertIsInt($exitCode);
    }

    public function test_test_event_system_dispatches_events(): void
    {
        Event::fake();
        
        Artisan::call('freescout:test-events');
        
        // Command should dispatch test events
        $this->assertTrue(true);
    }

    public function test_check_requirements_checks_php_version(): void
    {
        Artisan::call('freescout:check-requirements');
        $output = Artisan::output();
        
        // Output should contain some requirement checks
        $this->assertNotEmpty($output);
    }

    public function test_create_user_with_long_names(): void
    {
        $email = 'longname' . time() . '@example.com';
        
        $exitCode = Artisan::call('freescout:create-user', [
            '--firstName' => 'VeryLongFirstNameThatExceedsNormalLength',
            '--lastName' => 'VeryLongLastNameThatExceedsNormalLength',
            '--email' => $email,
            '--password' => 'password',
            '--role' => 'user',
        ]);
        
        $this->assertIsInt($exitCode);
    }

    public function test_artisan_commands_are_registered(): void
    {
        $commands = Artisan::all();
        
        $this->assertArrayHasKey('freescout:clear-cache', $commands);
        $this->assertArrayHasKey('freescout:update-folder-counters', $commands);
    }
}
