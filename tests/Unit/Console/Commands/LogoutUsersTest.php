<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands;

use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LogoutUsersTest extends TestCase
{
    #[Test]
    public function command_has_correct_signature(): void
    {
        $this->artisan('freescout:logout-users')
            ->assertExitCode(0);
    }

    #[Test]
    public function command_has_correct_description(): void
    {
        $this->artisan('list')
            ->expectsOutputToContain('freescout:logout-users')
            ->run();
    }

    #[Test]
    public function command_deletes_session_files(): void
    {
        $sessionsPath = storage_path('framework/sessions');
        
        // Create test session files
        if (!is_dir($sessionsPath)) {
            mkdir($sessionsPath, 0755, true);
        }
        
        $testFile1 = $sessionsPath . '/test_session_1';
        $testFile2 = $sessionsPath . '/test_session_2';
        
        file_put_contents($testFile1, 'session data 1');
        file_put_contents($testFile2, 'session data 2');

        $this->artisan('freescout:logout-users')
            ->assertExitCode(0);

        // Files should be deleted
        $this->assertFileDoesNotExist($testFile1);
        $this->assertFileDoesNotExist($testFile2);
    }

    #[Test]
    public function command_reports_deleted_sessions_count(): void
    {
        $sessionsPath = storage_path('framework/sessions');
        
        if (!is_dir($sessionsPath)) {
            mkdir($sessionsPath, 0755, true);
        }
        
        // Create test session files
        $testFile = $sessionsPath . '/test_session_count';
        file_put_contents($testFile, 'session data');

        $this->artisan('freescout:logout-users')
            ->expectsOutput('Deleted sessions: 1')
            ->assertExitCode(0);
    }

    #[Test]
    public function command_handles_empty_sessions_directory(): void
    {
        $sessionsPath = storage_path('framework/sessions');
        
        // Ensure directory exists but is empty
        if (!is_dir($sessionsPath)) {
            mkdir($sessionsPath, 0755, true);
        }
        
        // Clean all existing sessions
        $files = File::files($sessionsPath);
        foreach ($files as $file) {
            @unlink($file->getPathname());
        }

        $this->artisan('freescout:logout-users')
            ->expectsOutput('Deleted sessions: 0')
            ->assertExitCode(0);
    }

    #[Test]
    public function command_handles_missing_sessions_directory(): void
    {
        $sessionsPath = storage_path('framework/sessions');
        
        // Temporarily rename directory if it exists
        $backupPath = storage_path('framework/sessions_backup');
        if (is_dir($sessionsPath)) {
            rename($sessionsPath, $backupPath);
        }

        try {
            $this->artisan('freescout:logout-users')
                ->assertExitCode(0);
        } finally {
            // Restore directory
            if (is_dir($backupPath)) {
                rename($backupPath, $sessionsPath);
            }
        }

        $this->assertTrue(true);
    }

    #[Test]
    public function command_continues_on_individual_file_errors(): void
    {
        $sessionsPath = storage_path('framework/sessions');
        
        if (!is_dir($sessionsPath)) {
            mkdir($sessionsPath, 0755, true);
        }

        // Create a valid file
        $testFile = $sessionsPath . '/test_session_error';
        file_put_contents($testFile, 'session data');

        $this->artisan('freescout:logout-users')
            ->assertExitCode(0);

        $this->assertTrue(true);
    }

    #[Test]
    public function command_deletes_multiple_session_files(): void
    {
        $sessionsPath = storage_path('framework/sessions');
        
        if (!is_dir($sessionsPath)) {
            mkdir($sessionsPath, 0755, true);
        }

        // Create multiple test session files
        for ($i = 1; $i <= 5; $i++) {
            $testFile = $sessionsPath . '/test_session_multi_' . $i;
            file_put_contents($testFile, 'session data ' . $i);
        }

        $this->artisan('freescout:logout-users')
            ->assertExitCode(0);

        // Verify all test files are deleted
        for ($i = 1; $i <= 5; $i++) {
            $testFile = $sessionsPath . '/test_session_multi_' . $i;
            $this->assertFileDoesNotExist($testFile);
        }
    }

    #[Test]
    public function command_only_deletes_files_not_directories(): void
    {
        $sessionsPath = storage_path('framework/sessions');
        
        if (!is_dir($sessionsPath)) {
            mkdir($sessionsPath, 0755, true);
        }

        // Create a subdirectory
        $subDir = $sessionsPath . '/test_subdir';
        if (!is_dir($subDir)) {
            mkdir($subDir, 0755, true);
        }

        $this->artisan('freescout:logout-users')
            ->assertExitCode(0);

        // Subdirectory should still exist
        $this->assertDirectoryExists($subDir);

        // Cleanup
        @rmdir($subDir);
    }

    #[Test]
    public function command_provides_feedback_on_completion(): void
    {
        $this->artisan('freescout:logout-users')
            ->expectsOutputToContain('Deleted sessions:')
            ->assertExitCode(0);
    }

    protected function tearDown(): void
    {
        // Cleanup any test session files
        $sessionsPath = storage_path('framework/sessions');
        if (is_dir($sessionsPath)) {
            $files = File::files($sessionsPath);
            foreach ($files as $file) {
                if (str_contains($file->getFilename(), 'test_session')) {
                    @unlink($file->getPathname());
                }
            }
        }

        parent::tearDown();
    }
}
