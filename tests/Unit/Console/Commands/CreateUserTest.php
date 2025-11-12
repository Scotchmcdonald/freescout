<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\CreateUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateUserTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function command_can_be_instantiated(): void
    {
        $command = new CreateUser();
        
        $this->assertInstanceOf(CreateUser::class, $command);
    }

    #[Test]
    public function command_has_correct_signature(): void
    {
        $command = new CreateUser();
        
        $this->assertEquals('freescout:create-user', $command->getName());
    }

    #[Test]
    public function command_has_description(): void
    {
        $command = new CreateUser();
        
        $this->assertNotEmpty($command->getDescription());
        $this->assertStringContainsString('user', $command->getDescription());
    }

    #[Test]
    public function command_accepts_role_option(): void
    {
        $command = new CreateUser();
        
        $this->assertTrue($command->getDefinition()->hasOption('role'));
    }

    #[Test]
    public function command_accepts_first_name_option(): void
    {
        $command = new CreateUser();
        
        $this->assertTrue($command->getDefinition()->hasOption('firstName'));
    }

    #[Test]
    public function command_accepts_last_name_option(): void
    {
        $command = new CreateUser();
        
        $this->assertTrue($command->getDefinition()->hasOption('lastName'));
    }

    #[Test]
    public function command_accepts_email_option(): void
    {
        $command = new CreateUser();
        
        $this->assertTrue($command->getDefinition()->hasOption('email'));
    }

    #[Test]
    public function command_accepts_password_option(): void
    {
        $command = new CreateUser();
        
        $this->assertTrue($command->getDefinition()->hasOption('password'));
    }

    #[Test]
    public function command_accepts_no_verification_option(): void
    {
        $command = new CreateUser();
        
        $this->assertTrue($command->getDefinition()->hasOption('no-verification'));
    }

    #[Test]
    public function command_creates_admin_user(): void
    {
        $exitCode = Artisan::call('freescout:create-user', [
            '--role' => 'admin',
            '--firstName' => 'Admin',
            '--lastName' => 'User',
            '--email' => 'admin@example.com',
            '--password' => 'password123',
            '--no-interaction' => true,
        ]);

        $this->assertEquals(0, $exitCode);
        $this->assertDatabaseHas('users', [
            'email' => 'admin@example.com',
            'first_name' => 'Admin',
            'last_name' => 'User',
            'role' => User::ROLE_ADMIN,
        ]);
    }

    #[Test]
    public function command_creates_regular_user(): void
    {
        $exitCode = Artisan::call('freescout:create-user', [
            '--role' => 'user',
            '--firstName' => 'Regular',
            '--lastName' => 'User',
            '--email' => 'user@example.com',
            '--password' => 'password123',
            '--no-interaction' => true,
        ]);

        $this->assertEquals(0, $exitCode);
        $this->assertDatabaseHas('users', [
            'email' => 'user@example.com',
            'role' => User::ROLE_USER,
        ]);
    }

    #[Test]
    public function command_hashes_password(): void
    {
        Artisan::call('freescout:create-user', [
            '--role' => 'admin',
            '--firstName' => 'Test',
            '--lastName' => 'User',
            '--email' => 'test@example.com',
            '--password' => 'mypassword',
            '--no-interaction' => true,
        ]);

        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('mypassword', $user->password));
    }

    #[Test]
    public function command_sets_user_status_to_active(): void
    {
        Artisan::call('freescout:create-user', [
            '--role' => 'user',
            '--firstName' => 'Active',
            '--lastName' => 'User',
            '--email' => 'active@example.com',
            '--password' => 'password123',
            '--no-interaction' => true,
        ]);

        $user = User::where('email', 'active@example.com')->first();
        $this->assertEquals(User::STATUS_ACTIVE, $user->status);
    }

    #[Test]
    public function command_verifies_email_by_default(): void
    {
        Artisan::call('freescout:create-user', [
            '--role' => 'user',
            '--firstName' => 'Verified',
            '--lastName' => 'User',
            '--email' => 'verified@example.com',
            '--password' => 'password123',
            '--no-interaction' => true,
        ]);

        $user = User::where('email', 'verified@example.com')->first();
        $this->assertNotNull($user->email_verified_at);
    }

    #[Test]
    public function command_skips_verification_with_flag(): void
    {
        Artisan::call('freescout:create-user', [
            '--role' => 'user',
            '--firstName' => 'Unverified',
            '--lastName' => 'User',
            '--email' => 'unverified@example.com',
            '--password' => 'password123',
            '--no-verification' => true,
            '--no-interaction' => true,
        ]);

        $user = User::where('email', 'unverified@example.com')->first();
        $this->assertNull($user->email_verified_at);
    }

    #[Test]
    public function command_validates_email_format(): void
    {
        $exitCode = Artisan::call('freescout:create-user', [
            '--role' => 'user',
            '--firstName' => 'Test',
            '--lastName' => 'User',
            '--email' => 'invalid-email',
            '--password' => 'password123',
            '--no-interaction' => true,
        ]);

        $this->assertEquals(1, $exitCode);
        $this->assertDatabaseMissing('users', [
            'email' => 'invalid-email',
        ]);
    }

    #[Test]
    public function command_validates_password_minimum_length(): void
    {
        $exitCode = Artisan::call('freescout:create-user', [
            '--role' => 'user',
            '--firstName' => 'Test',
            '--lastName' => 'User',
            '--email' => 'test@example.com',
            '--password' => 'short',
            '--no-interaction' => true,
        ]);

        $this->assertEquals(1, $exitCode);
    }

    #[Test]
    public function command_validates_unique_email(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $exitCode = Artisan::call('freescout:create-user', [
            '--role' => 'user',
            '--firstName' => 'Duplicate',
            '--lastName' => 'User',
            '--email' => 'existing@example.com',
            '--password' => 'password123',
            '--no-interaction' => true,
        ]);

        $this->assertEquals(1, $exitCode);
    }

    #[Test]
    public function command_validates_role_values(): void
    {
        $exitCode = Artisan::call('freescout:create-user', [
            '--role' => 'invalid_role',
            '--firstName' => 'Test',
            '--lastName' => 'User',
            '--email' => 'test@example.com',
            '--password' => 'password123',
            '--no-interaction' => true,
        ]);

        $this->assertEquals(1, $exitCode);
    }

    #[Test]
    public function command_outputs_success_message(): void
    {
        Artisan::call('freescout:create-user', [
            '--role' => 'user',
            '--firstName' => 'Success',
            '--lastName' => 'User',
            '--email' => 'success@example.com',
            '--password' => 'password123',
            '--no-interaction' => true,
        ]);
        
        $output = Artisan::output();
        $this->assertStringContainsString('created', $output);
    }
}
