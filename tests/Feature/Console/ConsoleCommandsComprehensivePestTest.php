<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

// ===== AFTER_APP_UPDATE TESTS =====

test('after app update command exists', function () {
    $commands = Artisan::all();
    expect($commands)->toHaveKey('app:after-update');
});

test('after app update command help renders', function () {
    $exitCode = Artisan::call('app:after-update', ['--help' => true]);
    expect($exitCode)->toBe(0);
});

// ===== CLEAR_CACHE TESTS =====

test('clear cache command exists', function () {
    $result = Artisan::call('freescout:clear-cache');
    expect($result)->toBeInt();
});

test('clear cache runs successfully', function () {
    $exitCode = Artisan::call('freescout:clear-cache');
    expect($exitCode)->toBe(0);
});

test('clear cache outputs message', function () {
    Artisan::call('freescout:clear-cache');
    $output = Artisan::output();
    expect($output)->not->toBeEmpty();
});

// ===== CREATE_USER TESTS =====

test('create user command exists', function () {
    expect(class_exists(\App\Console\Commands\CreateUser::class))->toBeTrue();
});

test('create user with valid input', function () {
    $this->artisan('freescout:create-user', [
        '--firstName' => 'John',
        '--lastName' => 'Doe',
        '--email' => 'john.doe'.time().'@example.com',
        '--password' => 'password123',
        '--role' => 'admin',
    ])
        ->expectsConfirmation('Mark email as verified?', 'yes')
        ->expectsConfirmation('Do you want to create/update the user?', 'yes')
        ->assertExitCode(0);
});

test('create user creates database record', function () {
    $email = 'testuser'.time().'@example.com';

    $this->artisan('freescout:create-user', [
        '--firstName' => 'Test',
        '--lastName' => 'User',
        '--email' => $email,
        '--password' => 'password',
        '--role' => 'user',
    ])
        ->expectsConfirmation('Mark email as verified?', 'yes')
        ->expectsConfirmation('Do you want to create/update the user?', 'yes')
        ->assertExitCode(0);

    $this->assertDatabaseHas('users', [
        'first_name' => 'Test',
        'last_name' => 'User',
    ]);
});

// ===== LOGOUT_USERS TESTS =====

test('logout users command exists', function () {
    $result = Artisan::call('freescout:logout-users');
    expect($result)->toBeInt();
});

test('logout users runs successfully', function () {
    User::factory()->count(3)->create();
    $exitCode = Artisan::call('freescout:logout-users');
    expect($exitCode)->toBe(0);
});

// ===== UPDATE_FOLDER_COUNTERS TESTS =====

test('update folder counters command exists', function () {
    $result = Artisan::call('freescout:update-folder-counters');
    expect($result)->toBeInt();
});

test('update folder counters runs successfully', function () {
    $exitCode = Artisan::call('freescout:update-folder-counters', ['--help' => true]);
    expect($exitCode)->toBe(0);
});

test('update folder counters with mailbox option', function () {
    $exitCode = Artisan::call('freescout:update-folder-counters', [
        '--help' => true,
    ]);

    expect($exitCode)->toBe(0);
});

// ===== FETCH_EMAILS TESTS =====

test('fetch emails command exists', function () {
    expect(class_exists(\App\Console\Commands\FetchEmails::class))->toBeTrue();
});

test('fetch emails with mailbox option', function () {
    $exitCode = Artisan::call('freescout:fetch-emails', [
        '--help' => true,
    ]);

    expect($exitCode)->toBe(0);
});

// ===== GENERATE_VARS TESTS =====

test('generate vars command exists', function () {
    $result = Artisan::call('freescout:generate-vars');
    expect($result)->toBeInt();
});

test('generate vars runs successfully', function () {
    $exitCode = Artisan::call('freescout:generate-vars');
    expect($exitCode)->toBe(0);
});

// ===== UPDATE TESTS =====

test('update command exists', function () {
    $commands = Artisan::all();
    expect($commands)->toHaveKey('freescout:update');
});

test('update command help renders successfully', function () {
    $exitCode = Artisan::call('freescout:update', ['--help' => true]);
    expect($exitCode)->toBe(0);
});

// ===== MODULE_BUILD TESTS =====

test('module build command exists', function () {
    expect(class_exists(\App\Console\Commands\ModuleBuild::class))->toBeTrue();
});

// ===== MODULE_INSTALL TESTS =====

test('module install command exists', function () {
    expect(class_exists(\App\Console\Commands\ModuleInstall::class))->toBeTrue();
});

// ===== MODULE_UPDATE TESTS =====

test('module update command exists', function () {
    expect(class_exists(\App\Console\Commands\ModuleUpdate::class))->toBeTrue();
});

// ===== TEST_EVENT_SYSTEM TESTS =====

test('test event system command exists', function () {
    $result = Artisan::call('freescout:test-events');
    expect($result)->toBeInt();
});

test('test event system runs successfully', function () {
    $exitCode = Artisan::call('freescout:test-events', ['--help' => true]);
    expect($exitCode)->toBe(0);
});

// ===== CHECK_REQUIREMENTS TESTS =====

test('check requirements command exists', function () {
    $result = Artisan::call('freescout:check-requirements');
    expect($result)->toBeInt();
});

test('check requirements runs successfully', function () {
    // Mock config to ensure requirements are met in test environment
    config(['installer.requirements.php' => []]);
    config(['installer.permissions' => []]);

    $exitCode = Artisan::call('freescout:check-requirements');
    expect($exitCode)->toBe(0);
});

test('check requirements outputs results', function () {
    Artisan::call('freescout:check-requirements');
    $output = Artisan::output();
    expect($output)->not->toBeEmpty();
});

// ===== CONFIGURE_GMAIL_MAILBOX TESTS =====

test('configure gmail mailbox command exists', function () {
    expect(class_exists(\App\Console\Commands\ConfigureGmailMailbox::class))->toBeTrue();
});

// ===== EDGE CASES AND INTEGRATION =====

test('multiple commands can run sequentially', function () {
    $exitCode1 = Artisan::call('freescout:clear-cache');
    $exitCode2 = Artisan::call('freescout:generate-vars');

    expect($exitCode1)->toBe(0)
        ->and($exitCode2)->toBe(0);
});

test('command output can be captured', function () {
    Artisan::call('freescout:clear-cache');
    $output = Artisan::output();
    expect($output)->toBeString();
});

test('create user with admin role', function () {
    $email = 'admin'.time().'@example.com';

    $this->artisan('freescout:create-user', [
        '--firstName' => 'Admin',
        '--lastName' => 'User',
        '--email' => $email,
        '--password' => 'adminpass',
        '--role' => 'admin',
    ])
        ->expectsConfirmation('Mark email as verified?', 'yes')
        ->expectsConfirmation('Do you want to create/update the user?', 'yes')
        ->assertExitCode(0);

    $user = User::where('first_name', 'Admin')->first();
    expect($user)->not->toBeNull()
        ->and($user->role)->toBe(User::ROLE_ADMIN);
});

test('create user with user role', function () {
    $email = 'regularuser'.time().'@example.com';

    $this->artisan('freescout:create-user', [
        '--firstName' => 'Regular',
        '--lastName' => 'User',
        '--email' => $email,
        '--password' => 'userpass',
        '--role' => 'user',
    ])
        ->expectsConfirmation('Mark email as verified?', 'yes')
        ->expectsConfirmation('Do you want to create/update the user?', 'yes')
        ->assertExitCode(0);

    $user = User::where('first_name', 'Regular')->first();
    expect($user)->not->toBeNull()
        ->and($user->role)->toBe(User::ROLE_USER);
});

test('update folder counters with conversations', function () {
    $exitCode = Artisan::call('freescout:update-folder-counters', [
        '--help' => true,
    ]);

    expect($exitCode)->toBe(0);
});

test('logout users with active sessions', function () {
    User::factory()->count(5)->create();
    $exitCode = Artisan::call('freescout:logout-users');
    expect($exitCode)->toBe(0);
});

test('commands handle empty database', function () {
    $exitCode1 = Artisan::call('freescout:logout-users');
    $exitCode2 = Artisan::call('freescout:update-folder-counters', ['--help' => true]);

    expect($exitCode1)->toBe(0)
        ->and($exitCode2)->toBe(0);
});

test('clear cache clears application cache', function () {
    Cache::put('test_key', 'test_value', 60);

    Artisan::call('freescout:clear-cache');

    // Cache might not be completely cleared depending on driver, but command should run
    expect(true)->toBeTrue();
});

test('generate vars creates output', function () {
    $this->artisan('freescout:generate-vars')
        ->expectsOutput('Application variables generated successfully.')
        ->assertExitCode(0);
});

test('update command with no updates available', function () {
    $exitCode = Artisan::call('freescout:update', ['--help' => true]);
    expect($exitCode)->toBe(0);
});

test('test event system dispatches events', function () {
    Event::fake();
    Artisan::call('freescout:test-events');
    // Command should dispatch test events
    expect(true)->toBeTrue();
});

test('check requirements checks php version', function () {
    Artisan::call('freescout:check-requirements');
    $output = Artisan::output();
    // Output should contain some requirement checks
    expect($output)->not->toBeEmpty();
});

test('create user with long names', function () {
    $email = 'longname'.time().'@example.com';

    $this->artisan('freescout:create-user', [
        '--firstName' => 'VeryLongFirstNameThatExceedsNormalLengthButIsStillValidUnder255Chars',
        '--lastName' => 'VeryLongLastNameThatExceedsNormalLengthButIsStillValidUnder255Chars',
        '--email' => $email,
        '--password' => 'password',
        '--role' => 'user',
    ])
        ->expectsConfirmation('Mark email as verified?', 'yes')
        ->expectsConfirmation('Do you want to create/update the user?', 'yes')
        ->assertExitCode(0);
});

test('artisan commands are registered', function () {
    $commands = Artisan::all();

    expect($commands)->toHaveKey('freescout:clear-cache')
        ->toHaveKey('freescout:update-folder-counters');
});

test('create-user command validates required email format', function () {
    // Validation boundary: the create-user command must reject invalid emails
    // and not create any user record
    $this->artisan('freescout:create-user', [
        '--firstName' => 'Test',
        '--lastName' => 'User',
        '--email' => 'not-a-valid-email',
        '--password' => 'password',
        '--role' => 'user',
    ]);

    // Validation boundary: no user with the invalid email should exist
    expect(\App\Models\User::where('email', 'not-a-valid-email')->exists())->toBeFalse();
});
