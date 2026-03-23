<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\AppHealth\Models\ScalingScorecardSnapshot;
use Modules\AppHealth\Services\TrendDeltaService;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    config([
        'apphealth.enabled' => true,
        'apphealth.operator_ui_enabled' => true,
        'apphealth.trigger_evaluation_enabled' => true,
        'apphealth.playbook.consecutive_breach_weeks_required' => 2,
    ]);
});

test('trend card shows stable when no historical snapshots exist', function (): void {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get('/app-health/scorecard');

    $response->assertOk();
    $response->assertSee('Weekly Trend');
    // No prior data → delta is 0 → direction stable
    $response->assertSee('STABLE');
});

test('trend delta service reports worsening when breach count increased vs last week', function (): void {
    // Seed a snapshot from 10 days ago with breach_count = 0
    ScalingScorecardSnapshot::query()->create([
        'snapshot_date' => now()->subDays(10)->toDateString(),
        'overall_status' => 'ok',
        'recommendation' => 'continue_observation',
        'breach_count' => 0,
        'payload' => [],
        'evaluated_at' => now()->subDays(10),
    ]);

    $service = app(TrendDeltaService::class);
    $trend = $service->weeklyDelta(3); // current week has 3 breaches vs 0 last week

    expect($trend['delta_7d'])->toBe(3)
        ->and($trend['direction'])->toBe('worsening');
});

test('trend delta service reports improving when breach count decreased', function (): void {
    ScalingScorecardSnapshot::query()->create([
        'snapshot_date' => now()->subDays(10)->toDateString(),
        'overall_status' => 'warning',
        'recommendation' => 'schedule_stage_a_work',
        'breach_count' => 4,
        'payload' => [],
        'evaluated_at' => now()->subDays(10),
    ]);

    $service = app(TrendDeltaService::class);
    $trend = $service->weeklyDelta(1);

    expect($trend['delta_7d'])->toBe(-3)
        ->and($trend['direction'])->toBe('improving');
});

test('gate condition met after two consecutive breach weeks', function (): void {
    // This week: 2 breaches (consecutive_breach_weeks = 1 already counted via current)
    // Previous week: 3 breaches
    $lastWeekStart = now()->subWeek()->startOfWeek()->toDateString();

    ScalingScorecardSnapshot::query()->create([
        'snapshot_date' => $lastWeekStart,
        'overall_status' => 'warning',
        'recommendation' => 'schedule_stage_a_work',
        'breach_count' => 3,
        'payload' => [],
        'evaluated_at' => now()->subWeek(),
    ]);

    // Also seed one for this week so consecutive detection works
    ScalingScorecardSnapshot::query()->create([
        'snapshot_date' => now()->toDateString(),
        'overall_status' => 'warning',
        'recommendation' => 'schedule_stage_a_work',
        'breach_count' => 2,
        'payload' => [],
        'evaluated_at' => now(),
    ]);

    $service = app(TrendDeltaService::class);
    $trend = $service->weeklyDelta(2);

    expect($trend['consecutive_breach_weeks'])->toBeGreaterThanOrEqual(2)
        ->and($trend['gate_condition_met'])->toBeTrue();
});

test('operator scorecard page shows gate condition banner when gate is met', function (): void {
    $admin = User::factory()->admin()->create();

    // Seed 2 consecutive weeks of breach snapshots
    $lastWeekStart = now()->subWeek()->startOfWeek()->toDateString();

    ScalingScorecardSnapshot::query()->create([
        'snapshot_date' => $lastWeekStart,
        'overall_status' => 'warning',
        'recommendation' => 'schedule_stage_a_work',
        'breach_count' => 3,
        'payload' => ['breach_count' => 3, 'overall_status' => 'warning', 'recommendation' => 'schedule_stage_a_work', 'checks' => []],
        'evaluated_at' => now()->subWeek(),
    ]);

    ScalingScorecardSnapshot::query()->create([
        'snapshot_date' => now()->toDateString(),
        'overall_status' => 'warning',
        'recommendation' => 'schedule_stage_a_work',
        'breach_count' => 2,
        'payload' => ['breach_count' => 2, 'overall_status' => 'warning', 'recommendation' => 'schedule_stage_a_work', 'checks' => []],
        'evaluated_at' => now(),
    ]);

    $response = $this->actingAs($admin)->get('/app-health/scorecard');

    $response->assertOk();
    $response->assertSee('Stage A Gate Condition Met');
    $response->assertSee('SCALING_PLAYBOOK');
});
