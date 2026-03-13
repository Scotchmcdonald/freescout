<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Services\AuditLogService;
use App\Traits\AuditsSensitiveOperations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->auditService = app(AuditLogService::class);
    $this->user = User::factory()->create([
        'email' => 'admin@example.com',
        'first_name' => 'Admin',
        'last_name' => 'User',
    ]);
    $this->actingAs($this->user);
});

test('audit service logs sensitive operations', function () {
    $this->auditService->logSensitiveOperation(
        operation: 'test_operation',
        subject: $this->user,
        properties: ['test_key' => 'test_value'],
        logName: 'test_log',
        causer: $this->user
    );

    $activity = Activity::latest()->first();

    expect($activity)->not->toBeNull()
        ->and($activity->log_name)->toBe('test_log')
        ->and($activity->description)->toBe('test_operation')
        ->and($activity->causer_id)->toBe($this->user->id)
        ->and($activity->subject_id)->toBe($this->user->id)
        ->and($activity->properties->get('test_key'))->toBe('test_value')
        ->and($activity->properties->get('ip_address'))->not->toBeNull()
        ->and($activity->properties->get('timestamp'))->not->toBeNull();
});

test('audit service enriches properties with request context', function () {
    $this->auditService->logSensitiveOperation(
        operation: 'enrichment_test',
        subject: null,
        properties: ['original' => 'data'],
        logName: 'test',
        causer: $this->user
    );

    $activity = Activity::latest()->first();
    $properties = $activity->properties;

    expect($properties)->toHaveKey('original')
        ->and($properties)->toHaveKey('ip_address')
        ->and($properties)->toHaveKey('user_agent')
        ->and($properties)->toHaveKey('timestamp')
        ->and($properties->get('original'))->toBe('data');
});

test('audit service queries logs with filters', function () {
    // Create multiple log entries
    $this->auditService->logSensitiveOperation(
        operation: 'operation_1',
        subject: $this->user,
        properties: [],
        logName: 'financial_operations',
        causer: $this->user
    );

    $this->auditService->logSensitiveOperation(
        operation: 'operation_2',
        subject: null,
        properties: [],
        logName: 'bulk_operations',
        causer: $this->user
    );

    // Query by log name
    $results = $this->auditService->queryLogs(['log_name' => 'financial_operations'])->get();
    expect($results)->toHaveCount(1)
        ->and($results->first()->description)->toBe('operation_1');

    // Query by causer
    $results = $this->auditService->queryLogs(['causer_id' => $this->user->id])->get();
    expect($results)->toHaveCount(2);
});

test('audit service gets subject audit trail', function () {
    // Create multiple operations on the same subject
    $this->auditService->logSensitiveOperation('operation_1', $this->user, [], 'test', $this->user);
    $this->auditService->logSensitiveOperation('operation_2', $this->user, [], 'test', $this->user);
    $this->auditService->logSensitiveOperation('operation_3', $this->user, [], 'test', $this->user);

    $trail = $this->auditService->getSubjectAuditTrail($this->user);

    expect($trail)->toHaveCount(3)
        ->and($trail->pluck('description')->toArray())->toContain('operation_1', 'operation_2', 'operation_3');
});

test('audit service gets recent sensitive operations', function () {
    $this->auditService->logSensitiveOperation('op1', null, [], 'sensitive_operations', $this->user);
    $this->auditService->logSensitiveOperation('op2', null, [], 'financial_operations', $this->user);
    $this->auditService->logSensitiveOperation('op3', null, [], 'other_log', $this->user);

    $recent = $this->auditService->getRecentSensitiveOperations();

    expect($recent)->toHaveCount(2) // Only sensitive and financial, not 'other_log'
        ->and($recent->pluck('description')->toArray())->toContain('op1', 'op2');
});

test('trait audits sensitive operations', function () {
    $service = new class
    {
        use AuditsSensitiveOperations;

        public function performSensitiveOperation($user, $data): void
        {
            $this->auditSensitiveOperation(
                'sensitive_op',
                $user,
                $data
            );
        }
    };

    $service->performSensitiveOperation($this->user, ['key' => 'value']);

    $activity = Activity::latest()->first();
    expect($activity)->not->toBeNull()
        ->and($activity->log_name)->toBe('sensitive_operations')
        ->and($activity->description)->toBe('sensitive_op')
        ->and($activity->properties->get('key'))->toBe('value');
});

test('trait audits bulk operations', function () {
    $service = new class
    {
        use AuditsSensitiveOperations;

        public function performBulkOperation(int $count): void
        {
            $this->auditBulkOperation('bulk_update', $count, ['type' => 'test']);
        }
    };

    $service->performBulkOperation(100);

    $activity = Activity::latest()->first();
    expect($activity)->not->toBeNull()
        ->and($activity->log_name)->toBe('bulk_operations')
        ->and($activity->properties->get('count'))->toBe(100)
        ->and($activity->properties->get('type'))->toBe('test');
});

test('trait audits financial operations', function () {
    $service = new class
    {
        use AuditsSensitiveOperations;

        public function performFinancialOperation($user, int $cents): void
        {
            $this->auditFinancialOperation(
                'credit_adjustment',
                $user,
                $cents,
                ['reason' => 'refund']
            );
        }
    };

    $service->performFinancialOperation($this->user, 5000); // $50.00

    $activity = Activity::latest()->first();
    expect($activity)->not->toBeNull()
        ->and($activity->log_name)->toBe('financial_operations')
        ->and($activity->properties->get('amount_cents'))->toBe(5000)
        ->and($activity->properties->get('amount_dollars'))->toEqual(50.0)
        ->and($activity->properties->get('reason'))->toBe('refund');
});

test('trait audits data access operations', function () {
    $service = new class
    {
        use AuditsSensitiveOperations;

        public function exportData(string $dataType, array $filters, int $count): void
        {
            $this->auditDataAccess('data_export', $dataType, $filters, $count);
        }
    };

    $service->exportData('invoices', ['status' => 'paid', 'year' => 2026], 150);

    $activity = Activity::latest()->first();
    expect($activity)->not->toBeNull()
        ->and($activity->log_name)->toBe('data_access')
        ->and($activity->properties->get('data_type'))->toBe('invoices')
        ->and($activity->properties->get('filters'))->toBe(['status' => 'paid', 'year' => 2026])
        ->and($activity->properties->get('record_count'))->toBe(150);
});

test('audit logs are written to database', function () {
    expect(Activity::count())->toBe(0);

    $this->auditService->logSensitiveOperation(
        'test_database_write',
        $this->user,
        ['data' => 'test'],
        'test',
        $this->user
    );

    expect(Activity::count())->toBe(1);
});

test('audit logs can be filtered by date range', function () {
    // Create logs at different times
    $this->auditService->logSensitiveOperation('old_op', null, [], 'test', $this->user);

    $oldActivity = Activity::latest()->first();
    $oldActivity->created_at = now()->subDays(5);
    $oldActivity->save();

    $this->auditService->logSensitiveOperation('recent_op', null, [], 'test', $this->user);

    $results = $this->auditService->queryLogs([
        'date_from' => now()->subDays(1),
    ])->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->description)->toBe('recent_op');
});
