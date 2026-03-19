<?php

use App\Models\User;
use Modules\ContractManager\Models\Milestone;

function getMilestoneAdmin(): User
{
    $admin = User::firstOrCreate(['email' => 'milestone-crud-admin@example.com'], [
        'password' => bcrypt('password'),
        'role' => User::ROLE_ADMIN,
        'first_name' => 'MilestoneCrud',
        'last_name' => 'Admin',
        'email_verified_at' => now(),
    ]);
    if (! $admin->isAdmin()) {
        $admin->role = User::ROLE_ADMIN;
        $admin->save();
    }

    return $admin;
}

it('milestone index page loads', function () {
    $admin = getMilestoneAdmin();

    browserLoginAdmin($this, $admin);

    $this->visit('/admin/milestones');
})->group('admin', 'milestone-crud');

it('milestone create page loads', function () {
    $admin = getMilestoneAdmin();

    browserLoginAdmin($this, $admin);

    $this->visit('/admin/milestones/create');
})->group('admin', 'milestone-crud');

it('milestone can be created via model', function () {
    $milestone = Milestone::create([
        'title' => 'Phase 1 - Requirements',
        'description' => 'Gather and document project requirements',
        'sequence_order' => 1,
        'status' => 'pending',
        'progress_percentage' => 0,
        'billing_amount' => 2500.00,
        'target_date' => now()->addMonth(),
    ]);

    expect($milestone->id)->toBeGreaterThan(0);
    expect($milestone->title)->toBe('Phase 1 - Requirements');
    expect($milestone->status)->toBe('pending');
    expect($milestone->isPending())->toBeTrue();
    expect($milestone->isAchieved())->toBeFalse();
    expect((float) $milestone->billing_amount)->toBe(2500.00);

    $found = Milestone::find($milestone->id);
    expect($found)->not->toBeNull();
    expect($found->description)->toBe('Gather and document project requirements');
})->group('admin', 'milestone-crud');

it('milestone progress can be updated', function () {
    $milestone = Milestone::create([
        'title' => 'Phase 2 - Design',
        'description' => 'Create system design documents',
        'sequence_order' => 2,
        'status' => 'pending',
        'progress_percentage' => 0,
        'target_date' => now()->addMonths(2),
    ]);

    expect((float) $milestone->progress_percentage)->toBe(0.00);

    $milestone->updateProgress(50);
    $milestone->refresh();

    expect((float) $milestone->progress_percentage)->toBe(50.00);
    expect($milestone->status)->toBe('in_progress');
    expect($milestone->isInProgress())->toBeTrue();
})->group('admin', 'milestone-crud');

it('milestone status transitions work', function () {
    $milestone = Milestone::create([
        'title' => 'Phase 3 - Implementation',
        'description' => 'Implement the solution',
        'sequence_order' => 3,
        'status' => 'pending',
        'progress_percentage' => 0,
        'target_date' => now()->addMonths(3),
    ]);

    expect($milestone->isPending())->toBeTrue();

    // Transition to in_progress
    $milestone->markAsInProgress();
    $milestone->refresh();
    expect($milestone->isInProgress())->toBeTrue();
    expect($milestone->started_at)->not->toBeNull();

    // Transition to achieved
    $milestone->markAsAchieved();
    $milestone->refresh();
    expect($milestone->isAchieved())->toBeTrue();
    expect((float) $milestone->progress_percentage)->toBe(100.00);
    expect($milestone->completed_at)->not->toBeNull();
})->group('admin', 'milestone-crud');
