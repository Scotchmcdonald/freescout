<?php

declare(strict_types=1);

use App\Models\User;
use App\Widgets\Dashboard\AdminDashboardWidget;
use App\Widgets\Dashboard\AgentDashboardWidget;
use App\Widgets\Dashboard\FinanceDashboardWidget;
use App\Widgets\Dashboard\ReporterDashboardWidget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Payment\Models\Payment;
use Modules\PIB\Models\Invoice;

uses(RefreshDatabase::class);

test('admin widget renders only for admin users', function () {
    $widget = new AdminDashboardWidget;

    $nonAdmin = User::factory()->create(['role' => User::ROLE_USER]);
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    expect($widget->render(['user' => $nonAdmin]))->toBeNull();
    expect($widget->render(['user' => $admin]))->not()->toBeNull();
});

test('agent widget renders only for agent users', function () {
    $widget = new AgentDashboardWidget;

    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $finance = User::factory()->create(['role' => User::ROLE_FINANCE]);
    $reporter = User::factory()->create(['role' => User::ROLE_REPORTER]);
    $agent = User::factory()->create(['role' => User::ROLE_USER]);

    expect($widget->render(['user' => $admin]))->toBeNull();
    expect($widget->render(['user' => $finance]))->toBeNull();
    expect($widget->render(['user' => $reporter]))->toBeNull();

    $html = $widget->render(['user' => $agent]);

    expect($html)->not()->toBeNull()
        ->and($html)->toContain('Assigned to Me')
        ->and($html)->toContain('No open cases assigned to you.');
});

test('finance widget renders only for finance users', function () {
    $widget = new FinanceDashboardWidget;

    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $finance = User::factory()->create(['role' => User::ROLE_FINANCE]);

    expect($widget->render(['user' => $admin]))->toBeNull();

    $html = $widget->render(['user' => $finance]);

    expect($html)->not()->toBeNull()
        ->and($html)->toContain('Total Open AR')
        ->and($html)->toContain('No overdue invoices');
});

test('finance widget shows overdue invoice table and recent completed payments', function () {
    $widget = new FinanceDashboardWidget;
    $finance = User::factory()->create(['role' => User::ROLE_FINANCE]);

    $overdueInvoice = Invoice::factory()->create([
        'status' => 'overdue',
        'invoice_number' => 'INV-OVERDUE-001',
        'due_date' => now()->subDays(12),
        'total_amount' => 1234.56,
    ]);

    Payment::factory()->create([
        'status' => 'successful',
        'amount' => 250.00,
        'fee_amount' => 0,
        'total_amount' => 250.00,
    ]);

    $html = $widget->render(['user' => $finance]);

    expect($html)->toContain('INV-OVERDUE-001')
        ->and($html)->toContain('Recent Payments Received')
        ->and($html)->toContain((string) $overdueInvoice->client->name)
        ->and($html)->toContain('$1,234.56');
});

test('reporter widget renders only for reporter users', function () {
    $widget = new ReporterDashboardWidget;

    $agent = User::factory()->create(['role' => User::ROLE_USER]);
    $reporter = User::factory()->create(['role' => User::ROLE_REPORTER]);

    expect($widget->render(['user' => $agent]))->toBeNull();

    $html = $widget->render(['user' => $reporter]);

    expect($html)->not()->toBeNull()
        ->and($html)->toContain('Business Snapshot')
        ->and($html)->toContain('Monthly Financials')
        ->and($html)->toContain('Business Snapshot');
});

test('reporter widget monthly financials reflect billed and collected values', function () {
    $widget = new ReporterDashboardWidget;
    $reporter = User::factory()->create(['role' => User::ROLE_REPORTER]);

    Invoice::factory()->create([
        'status' => 'submitted',
        'created_at' => now()->startOfMonth()->addDay(),
        'updated_at' => now()->startOfMonth()->addDay(),
        'total_amount' => 300.00,
    ]);

    Invoice::factory()->create([
        'status' => 'paid',
        'created_at' => now()->startOfMonth()->addDays(2),
        'updated_at' => now()->startOfMonth()->addDays(3),
        'total_amount' => 700.00,
    ]);

    Invoice::factory()->create([
        'status' => 'overdue',
        'created_at' => now()->subMonth(),
        'updated_at' => now()->subMonth(),
        'total_amount' => 125.00,
    ]);

    $html = $widget->render(['user' => $reporter]);

    expect($html)->toContain('Monthly Financials')
        ->and($html)->toContain('$1,000.00')
        ->and($html)->toContain('$700.00')
        ->and($html)->toContain('$125.00');
});

test('widget metadata contracts are stable', function () {
    $admin = new AdminDashboardWidget;
    $agent = new AgentDashboardWidget;
    $finance = new FinanceDashboardWidget;
    $reporter = new ReporterDashboardWidget;

    expect($admin->getZone())->toBe('dashboard.main')
        ->and($agent->getZone())->toBe('dashboard.main')
        ->and($finance->getZone())->toBe('dashboard.main')
        ->and($reporter->getZone())->toBe('dashboard.main')
        ->and($admin->getId())->toBe('dashboard.admin_overview')
        ->and($agent->getId())->toBe('dashboard.agent_overview')
        ->and($finance->getId())->toBe('dashboard.finance_overview')
        ->and($reporter->getId())->toBe('dashboard.reporter_overview');
});
test('widgets enforce authorization by rendering only for appropriate roles', function () {
    // Authorization boundary: admin widget must return null for non-admin users,
    // enforcing role-based authorization at the render layer
    $admin    = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $nonAdmin = User::factory()->create(['role' => User::ROLE_USER]);

    $adminWidget = new AdminDashboardWidget;

    expect($adminWidget->render(['user' => $nonAdmin]))->toBeNull(
        'Authorization must deny admin widget rendering for non-admin roles'
    )
        ->and($adminWidget->render(['user' => $admin]))->not->toBeNull(
            'Authorization must allow admin widget rendering for admin role'
        );
});
