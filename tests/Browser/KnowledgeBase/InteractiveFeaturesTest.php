<?php

use App\Models\User;

it('displays multi-page tours and hides single-page tours in interactive features tab', function () {
    $admin = User::firstOrCreate(['email' => 'kb-int-test-admin@example.com'], [
        'password' => bcrypt('password'),
        'role' => User::ROLE_ADMIN,
        'first_name' => 'Int',
        'last_name' => 'Admin',
        'email_verified_at' => now(),
    ]);
    if (! $admin->isAdmin()) {
        $admin->role = User::ROLE_ADMIN;
        $admin->save();
    }

    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $browser = $this->visit('/knowledgebase/explore?view=tour');

    // Verify filtering logic works:
    // 1. Admin Setup (Multi-page) is visible
    $browser->assertSee('Admin Setup');
    // 2. Knowledge Base Tour (Single-page) is hidden
    $browser->assertDontSee('Knowledge Base Tour');

    // Verify "Start Demo Tour" button is present for Admin Setup
    $browser->assertSee('Start Demo Tour');
})->group('knowledgebase', 'interactive');
