<?php

use App\Models\Customer;
use App\Models\Email;
use App\Models\User;
use Modules\Crm\Models\CrmStagingRecord;

// use Illuminate\Foundation\Testing\RefreshDatabase; // Removed to avoid conflict with manual truncating

// Helper function to get CRM admin
function getCrmAdmin(): User
{
    $admin = User::firstOrCreate(['email' => 'crm-test-admin@example.com'], [
        'password' => bcrypt('password'),
        'role' => User::ROLE_ADMIN,
        'first_name' => 'CRM',
        'last_name' => 'Admin',
        'email_verified_at' => now(),
    ]);

    // Ensure password and role are correct (in case user persisted from previous runs)
    $admin->password = bcrypt('password');
    $admin->role = User::ROLE_ADMIN;
    $admin->save();

    return $admin;
}

test('crm staging new user creation flow', function () {
    // 1. Setup Data
    CrmStagingRecord::where('email', 'staging.new@example.com')->delete();
    $admin = getCrmAdmin();
    $record = CrmStagingRecord::create([
        'email' => 'staging.new@example.com',
        'source' => 'GoogleWorkspace',
        'proposed_changes' => [
            'first_name' => 'Staging',
            'last_name' => 'Tester',
            'job_title' => 'QA Lead',
        ],
        'status' => 'pending_review',
    ]);

    // 2. Perform Browser Test
    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]'); // Standard Login

    $browser = $this->visit('/crm/staging')
        ->waitForText('CRM Staging & Conflicts')

        // Assert record is visible (proving DB connection works)
        ->assertSee('staging.new@example.com');

    // Interact (Create) - Use $browser variable to break the chain
    $browser->script('window.confirm = () => true;');

    // Debug: Monitor network errors? Can't easily in Pest browser.
    // We can pause longer to see if it failed.

    $browser->click('//button[contains(text(), "Create")]')
        // Use a longer timeout
        ->waitForText('No pending conflicts found', 15)
        ->assertSee('No pending conflicts found');

    // 3. Verify Database
    $customer = Customer::where('first_name', 'Staging')->first();

    // Verify Staging Record Updated
    $record->refresh();
    expect($record->status)->toBe('approved');
})->group('crm', 'staging', 'lifecycle');

test('crm staging map to existing customer flow', function () {
    // 1. Setup Data
    CrmStagingRecord::where('email', 'google.sync@example.com')->delete();
    $admin = getCrmAdmin();

    // Explicit creation to bypass factory/special create method issues
    // Cleanup existing to avoid unique constraint violation if previous run failed
    $existing = Customer::where('first_name', 'Existing')->where('last_name', 'User')->first();
    if ($existing) {
        $existing->delete();
    }
    // Also delete any emails that might cause collision
    Email::where('email', 'manual.entry@example.com')->delete();

    $existing = new Customer;
    $existing->first_name = 'Existing';
    $existing->last_name = 'User';
    $existing->save();

    $email = new Email;
    $email->email = 'manual.entry@example.com';
    $email->type = 1;
    $existing->emails()->save($email);

    $record = CrmStagingRecord::create([
        'email' => 'google.sync@example.com',
        'source' => 'GoogleWorkspace',
        'proposed_changes' => [
            'first_name' => 'Existing',
            'last_name' => 'User',
            'job_title' => 'Mapped Role',
        ],
        'status' => 'pending_review',
    ]);

    // 2. Perform Browser Test
    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]')
        ->waitForText('Dashboard'); // Ensure login completed

    // Debug: Check if route exists on client side?
    // Visit dashboard first.

    $browser = $this->visit('/crm/staging')
        ->waitForText('google.sync@example.com')

        // Open Map Modal
        ->click('//button[contains(text(), "Map")]')
        ->waitForText('Map to Existing Customer');

    // Perform Search
    $browser->type('input[placeholder*="Type name"]', 'Existing');

    // Wait for result to appear by text
    $browser->waitForText('Existing User', 10)
        ->click('.cursor-pointer')
        ->waitForText('Selected: Existing User');

    // Submit
    $browser->click('//button[contains(text(), "Link & Update")]')

        // Verify
        ->waitForText('No pending conflicts found', 15);

    $record->refresh();

    expect($record->customer_id)->toBe($existing->id)
        ->and($record->status)->toBe('approved');
})->group('crm', 'staging', 'lifecycle');
