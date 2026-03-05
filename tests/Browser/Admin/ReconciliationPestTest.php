<?php

use App\Models\User;
use Modules\PIB\Models\ReconciliationRun;
use Modules\PIB\Models\ReconciliationDiscrepancy;

function getReconciliationAdmin(): User
{
    $admin = User::firstOrCreate(['email' => 'reconciliation-admin@example.com'], [
        'password' => bcrypt('password'),
        'role' => User::ROLE_ADMIN,
        'first_name' => 'Recon',
        'last_name' => 'Admin',
        'email_verified_at' => now(),
    ]);
    if (!$admin->isAdmin()) { $admin->role = User::ROLE_ADMIN; $admin->save(); }
    return $admin;
}

it('reconciliation dashboard loads', function () {
    $admin = getReconciliationAdmin();

    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/admin/reconciliation')
        ->assertSee('Reconciliation');
})->group('admin', 'reconciliation');

it('reconciliation shows empty state', function () {
    $admin = getReconciliationAdmin();

    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/admin/reconciliation')
        ->assertSee('No reconciliation runs yet');
})->group('admin', 'reconciliation');

it('reconciliation run model works', function () {
    $run = ReconciliationRun::create([
        'run_type' => 'full',
        'status' => 'completed',
        'started_at' => now()->subMinutes(5),
        'completed_at' => now(),
        'items_checked' => 100,
        'total_discrepancies' => 3,
        'auto_corrected' => 1,
        'manual_review_required' => 2,
        'critical_issues' => 0,
        'success_rate' => 97.00,
        'duration_seconds' => 300,
        'triggered_by' => 'manual',
    ]);

    expect($run->id)->toBeGreaterThan(0);
    expect($run->status)->toBe('completed');
    expect($run->isComplete())->toBeTrue();
    expect($run->isRunning())->toBeFalse();
    expect($run->items_checked)->toBe(100);
    expect($run->total_discrepancies)->toBe(3);

    $found = ReconciliationRun::find($run->id);
    expect($found)->not->toBeNull();
    expect($found->run_type)->toBe('full');
})->group('admin', 'reconciliation');

it('discrepancy can be created and resolved', function () {
    $run = ReconciliationRun::create([
        'run_type' => 'full',
        'status' => 'completed',
        'started_at' => now()->subMinutes(5),
        'completed_at' => now(),
        'items_checked' => 50,
        'total_discrepancies' => 1,
        'auto_corrected' => 0,
        'manual_review_required' => 1,
        'critical_issues' => 0,
        'success_rate' => 98.00,
        'duration_seconds' => 120,
        'triggered_by' => 'manual',
    ]);

    $discrepancy = ReconciliationDiscrepancy::create([
        'reconciliation_run_id' => $run->id,
        'entity_type' => 'client',
        'entity_id' => 1,
        'field_name' => 'email',
        'expected_value' => 'expected@example.com',
        'actual_value' => 'actual@example.com',
        'source_system' => 'google_workspace',
        'severity' => 'medium',
        'resolution_status' => 'pending',
    ]);

    expect($discrepancy->id)->toBeGreaterThan(0);
    expect($discrepancy->isResolved())->toBeFalse();
    expect($discrepancy->requiresManualReview())->toBeTrue();
    expect($discrepancy->isCritical())->toBeFalse();

    // Resolve the discrepancy
    $discrepancy->update([
        'resolution_status' => 'resolved',
        'resolution_action' => 'manual_fix',
        'resolved_at' => now(),
        'resolution_notes' => 'Manually corrected email',
    ]);

    $discrepancy->refresh();
    expect($discrepancy->isResolved())->toBeTrue();
    expect($discrepancy->requiresManualReview())->toBeFalse();

    // Verify relationship
    expect($discrepancy->run->id)->toBe($run->id);
    expect($run->discrepancies()->count())->toBe(1);
})->group('admin', 'reconciliation');
