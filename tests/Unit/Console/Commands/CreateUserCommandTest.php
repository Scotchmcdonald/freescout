<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\CreateUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class CreateUserCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_user_command_creates_admin_user(): void
    {
        $this->artisan('freescout:create-user', [
            '--role' => 'admin',
            '--firstName' => 'John',
            '--lastName' => 'Doe',
            '--email' => 'john.doe@example.com',
            '--password' => 'password123',
            '--no-verification' => true,
        ])
            ->expectsConfirmation('Do you want to create the user?', 'yes')
            ->assertExitCode(0);

        $this->assertDatabaseHas('users', [
            'email' => 'john.doe@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'role' => User::ROLE_ADMIN,
        ]);
    }

    public function test_create_user_command_creates_regular_user(): void
    {
        $this->artisan('freescout:create-user', [
            '--role' => 'user',
            '--firstName' => 'Jane',
            '--lastName' => 'Smith',
            '--email' => 'jane.smith@example.com',
            '--password' => 'password123',
            '--no-verification' => true,
        ])
            ->expectsConfirmation('Do you want to create the user?', 'yes')
            ->assertExitCode(0);

        $this->assertDatabaseHas('users', [
            'email' => 'jane.smith@example.com',
            'role' => User::ROLE_USER,
        ]);
    }

    public function test_create_user_command_fails_with_invalid_email(): void
    {
        $this->artisan('freescout:create-user', [
            '--role' => 'admin',
            '--firstName' => 'John',
            '--lastName' => 'Doe',
            '--email' => 'invalid-email',
            '--password' => 'password123',
        ])
            ->assertExitCode(1);

        $this->assertDatabaseMissing('users', [
            'email' => 'invalid-email',
        ]);
    }

    public function test_create_user_command_fails_with_short_password(): void
    {
        $this->artisan('freescout:create-user', [
            '--role' => 'admin',
            '--firstName' => 'John',
            '--lastName' => 'Doe',
            '--email' => 'john@example.com',
            '--password' => 'short',
        ])
            ->assertExitCode(1);

        $this->assertDatabaseMissing('users', [
            'email' => 'john@example.com',
        ]);
    }

    public function test_create_user_command_fails_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $this->artisan('freescout:create-user', [
            '--role' => 'admin',
            '--firstName' => 'John',
            '--lastName' => 'Doe',
            '--email' => 'existing@example.com',
            '--password' => 'password123',
        ])
            ->assertExitCode(1);
    }

    public function test_create_user_command_respects_no_confirmation(): void
    {
        $this->artisan('freescout:create-user', [
            '--role' => 'admin',
            '--firstName' => 'Test',
            '--lastName' => 'User',
            '--email' => 'test.user@example.com',
            '--password' => 'password123',
            '--no-verification' => true,
        ])
            ->expectsConfirmation('Do you want to create the user?', 'no')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('users', [
            'email' => 'test.user@example.com',
        ]);
    }

    public function test_create_user_with_verification(): void
    {
        $this->artisan('freescout:create-user', [
            '--role' => 'admin',
            '--firstName' => 'Verified',
            '--lastName' => 'User',
            '--email' => 'verified@example.com',
            '--password' => 'password123',
        ])
            ->expectsConfirmation('Do you want to create the user?', 'yes')
            ->assertExitCode(0);

        $user = User::where('email', 'verified@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_create_user_without_verification(): void
    {
        $this->artisan('freescout:create-user', [
            '--role' => 'admin',
            '--firstName' => 'Unverified',
            '--lastName' => 'User',
            '--email' => 'unverified@example.com',
            '--password' => 'password123',
            '--no-verification' => true,
        ])
            ->expectsConfirmation('Do you want to create the user?', 'yes')
            ->assertExitCode(0);

        $user = User::where('email', 'unverified@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNull($user->email_verified_at);
    }
}
