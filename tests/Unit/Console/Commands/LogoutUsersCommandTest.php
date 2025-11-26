<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\LogoutUsers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class LogoutUsersCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_logout_users_command_executes_successfully(): void
    {
        // Ensure session directory exists
        $sessionPath = storage_path('framework/sessions');
        if (!File::exists($sessionPath)) {
            File::makeDirectory($sessionPath, 0755, true);
        }

        $this->artisan('freescout:logout-users')
            ->assertSuccessful();
    }

    public function test_logout_users_command_clears_session_files(): void
    {
        $sessionPath = storage_path('framework/sessions');
        
        // Ensure directory exists
        if (!File::exists($sessionPath)) {
            File::makeDirectory($sessionPath, 0755, true);
        }

        // Create a test session file
        $testFile = $sessionPath . '/test_session_' . uniqid();
        File::put($testFile, 'test session data');
        
        $this->assertTrue(File::exists($testFile));

        $this->artisan('freescout:logout-users')
            ->assertSuccessful();

        // The session file should be deleted
        $this->assertFalse(File::exists($testFile));
    }

    public function test_logout_users_command_handles_empty_sessions_directory(): void
    {
        $sessionPath = storage_path('framework/sessions');
        
        // Ensure directory exists and is empty
        if (!File::exists($sessionPath)) {
            File::makeDirectory($sessionPath, 0755, true);
        }

        // Remove all files first
        $files = File::files($sessionPath);
        foreach ($files as $file) {
            File::delete($file->getPathname());
        }

        $this->artisan('freescout:logout-users')
            ->expectsOutputToContain('Deleted sessions: 0')
            ->assertSuccessful();
    }

    public function test_logout_users_command_reports_deleted_count(): void
    {
        $sessionPath = storage_path('framework/sessions');
        
        // Ensure directory exists
        if (!File::exists($sessionPath)) {
            File::makeDirectory($sessionPath, 0755, true);
        }

        // Clear existing files
        $existingFiles = File::files($sessionPath);
        foreach ($existingFiles as $file) {
            File::delete($file->getPathname());
        }

        // Create multiple test session files
        for ($i = 0; $i < 3; $i++) {
            $testFile = $sessionPath . '/test_session_' . uniqid() . '_' . $i;
            File::put($testFile, 'test session data ' . $i);
        }

        $this->artisan('freescout:logout-users')
            ->expectsOutputToContain('Deleted sessions:')
            ->assertSuccessful();
    }

    public function test_logout_users_command_class_exists(): void
    {
        $this->assertTrue(class_exists(LogoutUsers::class));
    }
}
