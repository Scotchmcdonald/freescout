<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    config([
        'apphealth.enabled' => true,
        'apphealth.operator_ui_enabled' => true,
        'apphealth.scheduler.enabled' => false,
    ]);
});

test('operator scorecard page redirects guests to login', function (): void {
    $this->get('/app-health/scorecard')->assertRedirect(route('login'));
});

test('operator scorecard page forbids non-admin users', function (): void {
    $user = User::factory()->clientAdmin()->create(['role' => User::ROLE_USER]);

    $this->actingAs($user)
        ->get('/app-health/scorecard')
        ->assertForbidden();
});

test('operator scorecard page loads for admins', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/app-health/scorecard')
        ->assertOk()
        ->assertSee('AppHealth Operator Scorecard');
});

test('operator scorecard page includes resilience link in observability console', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/app-health/scorecard')
        ->assertOk()
        ->assertSee('Observability Console')
        ->assertSee('Resilience Dashboard');
});

test('operator scorecard page shows external grafana and prometheus links when configured', function (): void {
    config([
        'apphealth.observability.grafana_url' => 'https://grafana.example.com/d/app-health',
        'apphealth.observability.prometheus_url' => 'https://prometheus.example.com',
    ]);

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/app-health/scorecard')
        ->assertOk()
        ->assertSee('Grafana Dashboards')
        ->assertSee('Prometheus')
        ->assertSee('External');
});
