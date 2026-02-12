<?php

use App\Models\User;

function getReportingMilestoneAdmin(): User
{
    $admin = User::firstOrCreate(['email' => 'milestone-reporting-admin@example.com'], [
        'password' => bcrypt('password'),
        'role' => User::ROLE_ADMIN,
        'first_name' => 'Project',
        'last_name' => 'Manager',
        'email_verified_at' => now(),
    ]);

    if (!$admin->isAdmin()) {
        $admin->role = User::ROLE_ADMIN;
        $admin->save();
    }
    
    return $admin;
}

it('tracks project milestones', function () {
    $admin = getReportingMilestoneAdmin();

    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]')
        ->assertPathIs('/dashboard');

    // 1. Visit Milestones Index
    $this->visit(route('milestones.index'))
        ->assertSee('Milestones');
        
        // 2. Create Project/Milestone
    $this->visit(route('milestones.create'))
        ->assertSee('Create Project') // Check header
        
        // Fill details
        ->type('title', 'Phase 1 Completion')
        ->select('status', 'pending')
        ->type('target_date', now()->addDays(7)->format('Y-m-d'))
        ->type('progress_percentage', '0')
        ->click('button[type="submit"]')
        
        // 3. Verify in List
        ->assertSee('Phase 1 Completion')
        ->assertSee('Pending')
        ->assertSee('0%');
        
    // 4. Update Progress
    $this->visit(route('milestones.edit', \App\Models\Milestone::where('title', 'Phase 1 Completion')->firstOrFail()))
         ->assertSee('Edit Milestone')
         ->select('status', 'in_progress')
         ->type('sequence_order', '1') // Ensure sequence is sent
         ->type('progress_percentage', '50')
        ->type('title', 'Phase 1 Updated')
        ->type('sequence_order', '1')
        ->select('status', 'in_progress')
        ->select('project_type', 'project')
        ->type('project_id', '1')
        ->press('Update Milestone')
        ->assertDontSee('There were errors')
        // ->dump() // Uncomment to debug if needed
         
         // 5. Verify Updates
         ->assertSee('Milestone updated')
         ->assertSee('In Progress')
         ->assertSee('50%');

})->group('reporting', 'milestones');
