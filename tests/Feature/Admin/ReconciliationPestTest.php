<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\PIB\Models\ReconciliationDiscrepancy;
use Modules\PIB\Models\ReconciliationRun;

uses(RefreshDatabase::class);

it('reconciliation dashboard loads for admin', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)
        ->get('/admin/reconciliation')
        ->assertOk()
        ->assertSee('Reconciliation');
});

it('reconciliation dashboard shows empty state', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)
        ->get('/admin/reconciliation')
        ->assertOk()
        ->assertSee('No reconciliation runs yet');
});

it('reconciliation run and discrepancy model lifecycle works', function () {
    $run = ReconciliationRun::create([
        'run_type' => 'full',
        'status' => 'completed',
        'started_at' => now()->subMinutes(5),
        'completed_at' => now(),
        'items_checked' => 100,
        'total_discrepancies' => 1,
        'auto_corrected' => 0,
        'manual_review_required' => 1,
        'critical_issues' => 0,
        'success_rate' => 99.0,
        'duration_seconds' => 300,
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

    $discrepancy->update([
        'resolution_status' => 'resolved',
        'resolution_action' => 'manual_fix',
        'resolved_at' => now(),
    ]);

    expect($run->isComplete())->toBeTrue()
        ->and($discrepancy->fresh()->isResolved())->toBeTrue();
});
