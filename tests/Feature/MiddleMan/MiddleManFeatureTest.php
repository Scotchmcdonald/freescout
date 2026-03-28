<?php

declare(strict_types=1);

/**
 * Feature & Integration Tests for MiddleMan module.
 *
 * These tests boot the full Laravel application, use RefreshDatabase,
 * and verify HTTP endpoints, authorization boundaries, queue/event
 * side-effects, and background job behaviour.
 *
 * Convention: every write-endpoint test includes a side-effect assertion
 * (assertDatabaseHas, Queue::assertPushed, Event::assertDispatched).
 */

use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Modules\MiddleMan\Models\MiddleManAuditEntry;
use Modules\MiddleMan\Models\MiddleManIntercept;
use Modules\MiddleMan\Models\MiddleManLog;
use Modules\MiddleMan\Models\MiddleManPreset;
use Modules\MiddleMan\Models\MiddleManSchema;
use Modules\MiddleMan\Services\CircuitBreaker;
use Modules\MiddleMan\Jobs\DetectSchemaDriftJob;
use Modules\MiddleMan\Jobs\WriteLogEntryJob;

// ── Helpers ──────────────────────────────────────────────────────────────────

/**
 * Create an admin user with view_middleman and manage_middleman permissions.
 */
function createMiddleManAdmin(): User
{
    /** @var User $admin */
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    return $admin;
}

/**
 * Create a non-admin user with no MiddleMan permissions.
 */
function createUnprivilegedUser(): User
{
    /** @var User $user */
    $user = User::factory()->create(['role' => User::ROLE_USER]);

    return $user;
}

// Stub event class for replay tests — lives in the test file namespace scope
if (! class_exists('Tests\Feature\MiddleMan\ReplayableTestEvent')) {
    final class ReplayableTestEvent
    {
        public function __construct(
            public readonly string $message = 'hello',
            public readonly int $code = 200,
        ) {}
    }
}

beforeEach(function (): void {
    // Keep RuleEngine/CircuitBreaker reads in-memory during tests so Redis is never required.
    config()->set('middleman.cache_store', 'array');
});

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// Authorization Boundary Tests (403 Forbidden)
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

test('authorization boundary: unauthenticated user cannot access middleman dashboard', function (): void {
    $this->get('/middleman')
        ->assertRedirect('/login');
});

test('authorization boundary: unprivileged user gets 403 on middleman dashboard', function (): void {
    $user = createUnprivilegedUser();

    $this->actingAs($user)
        ->get('/middleman')
        ->assertStatus(403);
});

test('authorization boundary: unprivileged user gets 403 on replay endpoint', function (): void {
    $user = createUnprivilegedUser();

    $this->actingAs($user)
        ->post('/middleman/replay/1')
        ->assertStatus(403);
});

test('authorization boundary: unprivileged user gets 403 on logging toggle', function (): void {
    $user = createUnprivilegedUser();

    $this->actingAs($user)
        ->post('/middleman/logging/toggle', ['active' => true])
        ->assertStatus(403);
});

test('authorization boundary: unprivileged user gets 403 on intercept toggle', function (): void {
    $user = createUnprivilegedUser();

    $this->actingAs($user)
        ->post('/middleman/intercept/toggle', ['active' => true])
        ->assertStatus(403);
});

test('authorization boundary: unprivileged user gets 403 on muting add', function (): void {
    $user = createUnprivilegedUser();

    $this->actingAs($user)
        ->post('/middleman/muting/add', ['listener_class' => 'App\\Listeners\\Foo'])
        ->assertStatus(403);
});

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// Validation Boundary Tests (422 Unprocessable Entity)
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

test('validation boundary: adding a log rule with missing event_class returns 422', function (): void {
    $admin = createMiddleManAdmin();

    $this->actingAs($admin)
        ->postJson('/middleman/logging/rules', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['event_class']);
});

test('validation boundary: adding an intercept rule with missing event_class returns 422', function (): void {
    $admin = createMiddleManAdmin();

    $this->actingAs($admin)
        ->postJson('/middleman/intercept/rules', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['event_class']);
});

test('validation boundary: adding a muted listener with missing class returns 422', function (): void {
    $admin = createMiddleManAdmin();

    $this->actingAs($admin)
        ->postJson('/middleman/muting/add', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['listener_class']);
});

test('validation boundary: updating intercept payload with invalid data returns 422', function (): void {
    $admin = createMiddleManAdmin();

    $intercept = MiddleManIntercept::create([
        'event_class'    => 'App\\Events\\TestEvent',
        'event_name'     => 'TestEvent',
        'payload'        => ['key' => 'value'],
        'metadata'       => [],
        'status'         => MiddleManIntercept::STATUS_PENDING,
        'sort_order'     => 1,
        'intercepted_at' => now(),
    ]);

    $this->actingAs($admin)
        ->putJson("/middleman/intercept/{$intercept->id}/payload", ['payload' => 'not-an-array'])
        ->assertStatus(422);
});

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// Logging Toggle — Write Endpoint with Side-Effect Assertion
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

test('toggling logging on creates an audit trail entry', function (): void {
    $admin = createMiddleManAdmin();

    $this->actingAs($admin)
        ->postJson('/middleman/logging/toggle', ['active' => true])
        ->assertOk()
        ->assertJsonFragment(['active' => true]);

    // Side-effect assertion: audit entry was written
    $this->assertDatabaseHas('middleman_audit_trail', [
        'user_id' => $admin->id,
        'action'  => MiddleManAuditEntry::ACTION_LOGGING_TOGGLED,
    ]);
});

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// Intercept Toggle — Write Endpoint with Side-Effect Assertion
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

test('toggling intercept on creates an audit trail entry', function (): void {
    $admin = createMiddleManAdmin();

    $this->actingAs($admin)
        ->postJson('/middleman/intercept/toggle', ['active' => true])
        ->assertOk()
        ->assertJsonFragment(['active' => true]);

    $this->assertDatabaseHas('middleman_audit_trail', [
        'user_id' => $admin->id,
        'action'  => MiddleManAuditEntry::ACTION_INTERCEPT_TOGGLED,
    ]);
});

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// Intercept Fire — Write Endpoint with Side-Effect Assertions
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

test('firing a pending intercept marks it as fired and writes audit entry', function (): void {
    $admin = createMiddleManAdmin();

    $intercept = MiddleManIntercept::create([
        'event_class'    => 'App\\Events\\TestEvent',
        'event_name'     => 'TestEvent',
        'payload'        => ['key' => 'value'],
        'metadata'       => [],
        'status'         => MiddleManIntercept::STATUS_PENDING,
        'sort_order'     => 1,
        'intercepted_at' => now(),
    ]);

    $this->actingAs($admin)
        ->postJson("/middleman/intercept/{$intercept->id}/fire")
        ->assertOk()
        ->assertJsonFragment(['success' => true]);

    // Side-effect: intercept status changed
    $this->assertDatabaseHas('middleman_intercepts', [
        'id'     => $intercept->id,
        'status' => MiddleManIntercept::STATUS_FIRED,
    ]);

    // Side-effect: audit trail written
    $this->assertDatabaseHas('middleman_audit_trail', [
        'user_id' => $admin->id,
        'action'  => MiddleManAuditEntry::ACTION_INTERCEPT_FIRED,
    ]);
});

test('firing a non-pending intercept returns 422', function (): void {
    $admin = createMiddleManAdmin();

    $intercept = MiddleManIntercept::create([
        'event_class'    => 'App\\Events\\TestEvent',
        'event_name'     => 'TestEvent',
        'payload'        => ['key' => 'value'],
        'metadata'       => [],
        'status'         => MiddleManIntercept::STATUS_FIRED,
        'sort_order'     => 1,
        'intercepted_at' => now(),
        'fired_at'       => now(),
        'fired_by'       => $admin->id,
    ]);

    $this->actingAs($admin)
        ->postJson("/middleman/intercept/{$intercept->id}/fire")
        ->assertStatus(422)
        ->assertJsonFragment(['error' => 'Only pending intercepts can be fired.']);
});

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// Intercept Discard — Write Endpoint with Side-Effect Assertion
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

test('discarding a pending intercept marks status as discarded', function (): void {
    $admin = createMiddleManAdmin();

    $intercept = MiddleManIntercept::create([
        'event_class'    => 'App\\Events\\TestEvent',
        'event_name'     => 'TestEvent',
        'payload'        => ['data' => 1],
        'metadata'       => [],
        'status'         => MiddleManIntercept::STATUS_PENDING,
        'sort_order'     => 1,
        'intercepted_at' => now(),
    ]);

    $this->actingAs($admin)
        ->postJson("/middleman/intercept/{$intercept->id}/discard")
        ->assertOk();

    $this->assertDatabaseHas('middleman_intercepts', [
        'id'     => $intercept->id,
        'status' => MiddleManIntercept::STATUS_DISCARDED,
    ]);

    $this->assertDatabaseHas('middleman_audit_trail', [
        'action' => MiddleManAuditEntry::ACTION_INTERCEPT_DISCARDED,
    ]);
});

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// Intercept Payload Edit — Write Endpoint
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

test('updating intercept payload persists new payload and writes audit', function (): void {
    $admin = createMiddleManAdmin();

    $intercept = MiddleManIntercept::create([
        'event_class'    => 'App\\Events\\EditableEvent',
        'event_name'     => 'EditableEvent',
        'payload'        => ['original' => true],
        'metadata'       => [],
        'status'         => MiddleManIntercept::STATUS_PENDING,
        'sort_order'     => 1,
        'intercepted_at' => now(),
    ]);

    $newPayload = ['original' => false, 'added_key' => 'new_value'];

    $this->actingAs($admin)
        ->putJson("/middleman/intercept/{$intercept->id}/payload", ['payload' => $newPayload])
        ->assertOk();

    $intercept->refresh();
    expect($intercept->payload)->toBe($newPayload);

    $this->assertDatabaseHas('middleman_audit_trail', [
        'action'       => MiddleManAuditEntry::ACTION_PAYLOAD_EDITED,
        'subject_type' => MiddleManIntercept::class,
        'subject_id'   => $intercept->id,
    ]);
});

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// Historical Replay — Feature Test with Event Dispatch + Audit Assertion
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

test('replay endpoint dispatches the event and writes audit log', function (): void {
    Event::fake([ReplayableTestEvent::class]);

    $admin = createMiddleManAdmin();

    $log = MiddleManLog::create([
        'event_class'      => ReplayableTestEvent::class,
        'event_name'       => 'ReplayableTestEvent',
        'payload'          => ['message' => 'replayed', 'code' => 201],
        'metadata'         => ['class' => ReplayableTestEvent::class],
        'fired_at'         => now(),
        'correlation_id'   => null,
        'causation_id'     => null,
        'is_replay'        => false,
        'has_schema_drift' => false,
    ]);

    $this->actingAs($admin)
        ->postJson("/middleman/replay/{$log->id}")
        ->assertOk()
        ->assertJsonFragment(['success' => true]);

    // Side-effect: event was dispatched
    Event::assertDispatched(ReplayableTestEvent::class, function (ReplayableTestEvent $event): bool {
        return $event->message === 'replayed' && $event->code === 201;
    });

    // Side-effect: audit entry was written
    $this->assertDatabaseHas('middleman_audit_trail', [
        'user_id' => $admin->id,
        'action'  => 'event_replayed',
    ]);
});

test('replay endpoint returns 422 for non-existent log entry', function (): void {
    $admin = createMiddleManAdmin();

    $this->actingAs($admin)
        ->postJson('/middleman/replay/999999')
        ->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('corrupted', false);
});

test('replay sequence endpoint replays selected logs and writes sequence audit', function (): void {
    Event::fake([ReplayableTestEvent::class]);

    $admin = createMiddleManAdmin();

    $logA = MiddleManLog::create([
        'event_class'      => ReplayableTestEvent::class,
        'event_name'       => 'ReplayableTestEvent',
        'payload'          => ['message' => 'first', 'code' => 101],
        'metadata'         => ['class' => ReplayableTestEvent::class],
        'fired_at'         => now()->subMinute(),
        'correlation_id'   => null,
        'causation_id'     => null,
        'is_replay'        => false,
        'has_schema_drift' => false,
    ]);

    $logB = MiddleManLog::create([
        'event_class'      => ReplayableTestEvent::class,
        'event_name'       => 'ReplayableTestEvent',
        'payload'          => ['message' => 'second', 'code' => 202],
        'metadata'         => ['class' => ReplayableTestEvent::class],
        'fired_at'         => now(),
        'correlation_id'   => null,
        'causation_id'     => null,
        'is_replay'        => false,
        'has_schema_drift' => false,
    ]);

    $this->actingAs($admin)
        ->postJson('/middleman/replay/sequence', [
            'source' => 'logs',
            'ids' => [$logB->id, $logA->id],
        ])
        ->assertOk()
        ->assertJsonPath('source', 'logs')
        ->assertJsonPath('requested', 2)
        ->assertJsonPath('succeeded', 2)
        ->assertJsonPath('failed', 0);

    Event::assertDispatchedTimes(ReplayableTestEvent::class, 2);

    $this->assertDatabaseHas('middleman_audit_trail', [
        'user_id' => $admin->id,
        'action'  => 'sequence_replayed',
    ]);
});

test('replay sequence endpoint replays selected intercept captures', function (): void {
    Event::fake([ReplayableTestEvent::class]);

    $admin = createMiddleManAdmin();

    $intercept = MiddleManIntercept::create([
        'event_class'    => ReplayableTestEvent::class,
        'event_name'     => 'ReplayableTestEvent',
        'payload'        => ['message' => 'from-intercept', 'code' => 303],
        'metadata'       => ['source' => 'test'],
        'status'         => MiddleManIntercept::STATUS_PENDING,
        'sort_order'     => 1,
        'intercepted_at' => now(),
    ]);

    $this->actingAs($admin)
        ->postJson('/middleman/replay/sequence', [
            'source' => 'intercepts',
            'ids' => [$intercept->id],
        ])
        ->assertOk()
        ->assertJsonPath('source', 'intercepts')
        ->assertJsonPath('requested', 1)
        ->assertJsonPath('succeeded', 1)
        ->assertJsonPath('failed', 0);

    Event::assertDispatched(ReplayableTestEvent::class, function (ReplayableTestEvent $event): bool {
        return $event->message === 'from-intercept' && $event->code === 303;
    });
});

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// Listener Muting — Write Endpoints with Side-Effect Assertions
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

test('adding a muted listener returns updated list and writes audit', function (): void {
    $admin = createMiddleManAdmin();

    $this->actingAs($admin)
        ->postJson('/middleman/muting/add', ['listener_class' => 'App\\Listeners\\SendNotification'])
        ->assertOk()
        ->assertJsonFragment(['success' => true]);

    $this->assertDatabaseHas('middleman_audit_trail', [
        'user_id' => $admin->id,
        'action'  => 'listener_muted',
    ]);
});

test('removing a muted listener writes audit trail', function (): void {
    $admin = createMiddleManAdmin();

    // First add, then remove
    $this->actingAs($admin)
        ->postJson('/middleman/muting/add', ['listener_class' => 'App\\Listeners\\ToDel']);

    $this->actingAs($admin)
        ->deleteJson('/middleman/muting/remove', ['listener_class' => 'App\\Listeners\\ToDel'])
        ->assertOk();

    $this->assertDatabaseHas('middleman_audit_trail', [
        'action' => 'listener_unmuted',
    ]);
});

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// Schema Drift Detection — Background Job Integration Test
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

test('DetectSchemaDriftJob creates baseline on first run', function (): void {
    $log = MiddleManLog::create([
        'event_class'      => 'App\\Events\\BaselineEvent',
        'event_name'       => 'BaselineEvent',
        'payload'          => ['user_id' => 1, 'email' => 'test@example.com'],
        'metadata'         => [],
        'fired_at'         => now(),
        'correlation_id'   => null,
        'causation_id'     => null,
        'is_replay'        => false,
        'has_schema_drift' => false,
    ]);

    (new DetectSchemaDriftJob($log->id))->handle();

    $this->assertDatabaseHas('middleman_schemas', [
        'event_class' => 'App\\Events\\BaselineEvent',
        'version'     => 1,
    ]);

    // No drift on first run
    $log->refresh();
    expect($log->has_schema_drift)->toBeFalsy();
});

test('DetectSchemaDriftJob flags drift when payload diverges from baseline', function (): void {
    // Create baseline first
    MiddleManSchema::create([
        'event_class' => 'App\\Events\\DriftEvent',
        'schema'      => ['name' => 'string', 'count' => 'integer'],
        'version'     => 1,
        'locked_at'   => now(),
    ]);

    // Create a log entry with a different schema (new field + type change)
    $log = MiddleManLog::create([
        'event_class'      => 'App\\Events\\DriftEvent',
        'event_name'       => 'DriftEvent',
        'payload'          => ['name' => 'Alice', 'count' => 'not-a-number', 'new_field' => true],
        'metadata'         => [],
        'fired_at'         => now(),
        'correlation_id'   => null,
        'causation_id'     => null,
        'is_replay'        => false,
        'has_schema_drift' => false,
    ]);

    (new DetectSchemaDriftJob($log->id))->handle();

    $log->refresh();
    expect($log->has_schema_drift)->toBeTrue();

    $driftDetails = $log->metadata['schema_drift'] ?? null;
    expect($driftDetails)->not->toBeNull();
    expect($driftDetails['has_drift'])->toBeTrue();
    expect($driftDetails['added'])->toContain('new_field');
    expect($driftDetails['type_changed'])->toHaveKey('count');
});

test('DetectSchemaDriftJob does nothing when log entry does not exist', function (): void {
    // Should not throw
    (new DetectSchemaDriftJob(999999))->handle();

    expect(true)->toBeTrue();
});

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// WriteLogEntryJob — Chains DetectSchemaDriftJob
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

test('WriteLogEntryJob creates log record with tracing columns', function (): void {
    Queue::fake([DetectSchemaDriftJob::class]);

    $job = new WriteLogEntryJob(
        eventClass: 'App\\Events\\TracedEvent',
        eventName: 'TracedEvent',
        payload: ['data' => 'value'],
        metadata: ['class' => 'App\\Events\\TracedEvent'],
        firedAt: now()->toIso8601String(),
        correlationId: 'corr-abc-123',
        causationId: 'cause-xyz-789',
        isReplay: false,
    );

    $job->handle();

    $this->assertDatabaseHas('middleman_logs', [
        'event_class'    => 'App\\Events\\TracedEvent',
        'correlation_id' => 'corr-abc-123',
        'causation_id'   => 'cause-xyz-789',
        'is_replay'      => false,
    ]);

    // Side-effect: drift detection job dispatched
    Queue::assertPushed(DetectSchemaDriftJob::class);
});

test('WriteLogEntryJob marks is_replay flag when set', function (): void {
    Queue::fake([DetectSchemaDriftJob::class]);

    $job = new WriteLogEntryJob(
        eventClass: 'App\\Events\\ReplayedEvent',
        eventName: 'ReplayedEvent',
        payload: ['replayed' => true],
        metadata: [],
        firedAt: now()->toIso8601String(),
        correlationId: null,
        causationId: null,
        isReplay: true,
    );

    $job->handle();

    $this->assertDatabaseHas('middleman_logs', [
        'event_class' => 'App\\Events\\ReplayedEvent',
        'is_replay'   => true,
    ]);
});

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// Log Filtering — Read Endpoint
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

test('log filter endpoint returns paginated results with event_class filter', function (): void {
    $admin = createMiddleManAdmin();

    MiddleManLog::create([
        'event_class'      => 'App\\Events\\TargetEvent',
        'event_name'       => 'TargetEvent',
        'payload'          => [],
        'metadata'         => [],
        'fired_at'         => now(),
        'correlation_id'   => null,
        'causation_id'     => null,
        'is_replay'        => false,
        'has_schema_drift' => false,
    ]);

    MiddleManLog::create([
        'event_class'      => 'App\\Events\\OtherEvent',
        'event_name'       => 'OtherEvent',
        'payload'          => [],
        'metadata'         => [],
        'fired_at'         => now(),
        'correlation_id'   => null,
        'causation_id'     => null,
        'is_replay'        => false,
        'has_schema_drift' => false,
    ]);

    $response = $this->actingAs($admin)
        ->getJson('/middleman/logging/filter?event_class=App%5CEvents%5CTargetEvent')
        ->assertOk();

    $data = $response->json('data');
    expect($data)->toHaveCount(1);
    expect($data[0]['event_class'])->toBe('App\\Events\\TargetEvent');
});

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// Log Clear — Write Endpoint with Side-Effect Assertion
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

test('clearing all logs removes records and writes audit', function (): void {
    $admin = createMiddleManAdmin();

    MiddleManLog::create([
        'event_class'      => 'App\\Events\\ClearMe',
        'event_name'       => 'ClearMe',
        'payload'          => [],
        'metadata'         => [],
        'fired_at'         => now(),
        'correlation_id'   => null,
        'causation_id'     => null,
        'is_replay'        => false,
        'has_schema_drift' => false,
    ]);

    $this->actingAs($admin)
        ->deleteJson('/middleman/logging/clear')
        ->assertOk()
        ->assertJsonFragment(['deleted' => 1]);

    $this->assertDatabaseMissing('middleman_logs', [
        'event_class' => 'App\\Events\\ClearMe',
    ]);

    $this->assertDatabaseHas('middleman_audit_trail', [
        'action' => 'logs_cleared',
    ]);
});

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// README Coverage: Tab Routes and Advanced Views
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

test('all middleman tab pages load for admin', function (): void {
    $admin = createMiddleManAdmin();

    $this->actingAs($admin)->get('/middleman')->assertOk();
    $this->actingAs($admin)->get('/middleman/logging')->assertOk();
    $this->actingAs($admin)->get('/middleman/intercept')->assertOk();
    $this->actingAs($admin)->get('/middleman/marshal')->assertOk();
    $this->actingAs($admin)->get('/middleman/topology')->assertOk();
    $this->actingAs($admin)->get('/middleman/schema')->assertOk();
    $this->actingAs($admin)->get('/middleman/tracing')->assertOk();
    $this->actingAs($admin)->get('/middleman/replay')->assertOk();
    $this->actingAs($admin)->get('/middleman/muting')->assertOk();
});

test('topology diagram endpoint returns 404 when kroki is disabled', function (): void {
    $admin = createMiddleManAdmin();
    config()->set('middleman.kroki.enabled', false);

    $this->actingAs($admin)
        ->get('/middleman/topology/diagram.svg')
        ->assertStatus(404);
});

test('dashboard circuit breaker reset endpoint returns closed state and writes audit', function (): void {
    $admin = createMiddleManAdmin();

    $this->actingAs($admin)
        ->postJson('/middleman/circuit-breaker/reset')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('state', 'closed');

    $this->assertDatabaseHas('middleman_audit_trail', [
        'user_id' => $admin->id,
        'action'  => 'circuit_breaker_reset',
    ]);
});

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// README Coverage: Intercept Bulk & Queue Workflows
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

test('intercept reorder updates pending sort order and writes audit', function (): void {
    $admin = createMiddleManAdmin();

    $first = MiddleManIntercept::create([
        'event_class'    => 'App\\Events\\A',
        'event_name'     => 'A',
        'payload'        => ['k' => 1],
        'metadata'       => [],
        'status'         => MiddleManIntercept::STATUS_PENDING,
        'sort_order'     => 1,
        'intercepted_at' => now(),
    ]);

    $second = MiddleManIntercept::create([
        'event_class'    => 'App\\Events\\B',
        'event_name'     => 'B',
        'payload'        => ['k' => 2],
        'metadata'       => [],
        'status'         => MiddleManIntercept::STATUS_PENDING,
        'sort_order'     => 2,
        'intercepted_at' => now(),
    ]);

    $this->actingAs($admin)
        ->postJson('/middleman/intercept/reorder', [
            'order' => [
                ['id' => $first->id, 'sort' => 9],
                ['id' => $second->id, 'sort' => 3],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    $this->assertDatabaseHas('middleman_intercepts', ['id' => $first->id, 'sort_order' => 9]);
    $this->assertDatabaseHas('middleman_intercepts', ['id' => $second->id, 'sort_order' => 3]);
    $this->assertDatabaseHas('middleman_audit_trail', ['action' => MiddleManAuditEntry::ACTION_ORDER_CHANGED]);
});

test('intercept fire-selected and fire-all return fired and corrupted counters', function (): void {
    $admin = createMiddleManAdmin();

    $a = MiddleManIntercept::create([
        'event_class'    => 'App\\Events\\One',
        'event_name'     => 'One',
        'payload'        => ['x' => 1],
        'metadata'       => [],
        'status'         => MiddleManIntercept::STATUS_PENDING,
        'sort_order'     => 1,
        'intercepted_at' => now(),
    ]);

    $b = MiddleManIntercept::create([
        'event_class'    => 'App\\Events\\Two',
        'event_name'     => 'Two',
        'payload'        => ['x' => 2],
        'metadata'       => [],
        'status'         => MiddleManIntercept::STATUS_PENDING,
        'sort_order'     => 2,
        'intercepted_at' => now(),
    ]);

    $this->actingAs($admin)
        ->postJson('/middleman/intercept/fire-selected', ['ids' => [$a->id]])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('fired', 1)
        ->assertJsonStructure(['corrupted']);

    $this->actingAs($admin)
        ->postJson('/middleman/intercept/fire-all')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['fired', 'corrupted']);

    $this->assertDatabaseHas('middleman_audit_trail', ['action' => MiddleManAuditEntry::ACTION_BATCH_FIRED]);
});

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// README Coverage: Marshal Workflows and Allowlist Guardrails
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

test('marshal parameters endpoint returns parameter metadata payload', function (): void {
    $admin = createMiddleManAdmin();

    $this->actingAs($admin)
        ->getJson('/middleman/marshal/parameters?event_class=' . urlencode(ReplayableTestEvent::class))
        ->assertOk()
        ->assertJsonStructure(['parameters', 'presets']);
});

test('marshal searchable model endpoint blocks non-allowlisted model with 403', function (): void {
    $admin = createMiddleManAdmin();

    $this->actingAs($admin)
        ->getJson('/middleman/marshal/search-model?model_class=' . urlencode(User::class) . '&query=adm')
        ->assertStatus(403)
        ->assertJsonPath('error', 'Model is not searchable. Implement MiddleManSearchable or add it to middleman.searchable_models.');
});

test('marshal searchable model endpoint allows model in config allowlist', function (): void {
    $admin = createMiddleManAdmin();

    config()->set('middleman.searchable_models', [User::class]);

    $user = User::factory()->create([
        'first_name' => 'Search',
        'last_name'  => 'Target',
        'email'      => 'search.target@example.test',
    ]);

    $this->actingAs($admin)
        ->getJson('/middleman/marshal/search-model?model_class=' . urlencode(User::class) . '&query=search')
        ->assertOk()
        ->assertJsonStructure(['results'])
        ->assertJsonFragment(['id' => $user->id]);
});

test('marshal fire with hold creates pending intercept and audit entry', function (): void {
    $admin = createMiddleManAdmin();

    $this->actingAs($admin)
        ->postJson('/middleman/marshal/fire', [
            'event_class' => ReplayableTestEvent::class,
            'payload'     => ['message' => 'held', 'code' => 204],
            'hold'        => true,
        ])
        ->assertOk()
        ->assertJsonPath('action', 'held');

    $this->assertDatabaseHas('middleman_intercepts', [
        'event_class' => ReplayableTestEvent::class,
        'status'      => MiddleManIntercept::STATUS_PENDING,
    ]);

    $this->assertDatabaseHas('middleman_audit_trail', [
        'user_id' => $admin->id,
        'action'  => MiddleManAuditEntry::ACTION_EVENT_MARSHALLED,
    ]);
});

test('marshal batch with hold creates multiple intercepts', function (): void {
    $admin = createMiddleManAdmin();

    $this->actingAs($admin)
        ->postJson('/middleman/marshal/batch', [
            'event_class' => ReplayableTestEvent::class,
            'items'       => [
                ['message' => 'a', 'code' => 101],
                ['message' => 'b', 'code' => 202],
            ],
            'hold'        => true,
        ])
        ->assertOk()
        ->assertJsonPath('success', 2)
        ->assertJsonPath('failed', 0);

    expect(MiddleManIntercept::query()->where('event_class', ReplayableTestEvent::class)->count())->toBeGreaterThanOrEqual(2);
});

test('marshal preset save and delete endpoints work', function (): void {
    $admin = createMiddleManAdmin();

    $save = $this->actingAs($admin)
        ->postJson('/middleman/marshal/presets', [
            'event_class' => ReplayableTestEvent::class,
            'name'        => 'Smoke Preset',
            'payload'     => ['message' => 'preset', 'code' => 200],
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    $presetId = (int) $save->json('preset.id');
    expect($presetId)->toBeGreaterThan(0);

    $this->actingAs($admin)
        ->deleteJson('/middleman/marshal/presets/' . $presetId)
        ->assertOk()
        ->assertJsonPath('success', true);

    $this->assertDatabaseMissing((new MiddleManPreset())->getTable(), ['id' => $presetId]);
});

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// README Coverage: Replay Sequence Multi-Status Behaviour
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

test('replay sequence returns 207 multi-status when results are mixed', function (): void {
    $admin = createMiddleManAdmin();

    $ok = MiddleManLog::create([
        'event_class'      => ReplayableTestEvent::class,
        'event_name'       => 'ReplayableTestEvent',
        'payload'          => ['message' => 'ok', 'code' => 200],
        'metadata'         => ['class' => ReplayableTestEvent::class],
        'fired_at'         => now()->subSecond(),
        'correlation_id'   => null,
        'causation_id'     => null,
        'is_replay'        => false,
        'has_schema_drift' => false,
    ]);

    $bad = MiddleManLog::create([
        'event_class'      => 'App\\Events\\ClassDoesNotExist',
        'event_name'       => 'ClassDoesNotExist',
        'payload'          => ['message' => 'bad', 'code' => 500],
        'metadata'         => ['class' => 'App\\Events\\ClassDoesNotExist'],
        'fired_at'         => now(),
        'correlation_id'   => null,
        'causation_id'     => null,
        'is_replay'        => false,
        'has_schema_drift' => false,
    ]);

    $this->actingAs($admin)
        ->postJson('/middleman/replay/sequence', [
            'source' => 'logs',
            'ids'    => [$ok->id, $bad->id],
        ])
        ->assertStatus(207)
        ->assertJsonPath('processed', 2)
        ->assertJsonPath('succeeded', 1)
        ->assertJsonPath('failed', 1)
        ->assertJsonCount(1, 'errors');
});

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// README Coverage: Muting data + clear-all endpoints
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

test('muting data and clear-all endpoints return current state', function (): void {
    $admin = createMiddleManAdmin();

    $this->actingAs($admin)
        ->postJson('/middleman/muting/add', ['listener_class' => 'App\\Listeners\\One'])
        ->assertOk();

    $this->actingAs($admin)
        ->getJson('/middleman/muting/data')
        ->assertOk()
        ->assertJsonStructure(['muted_listeners']);

    $this->actingAs($admin)
        ->deleteJson('/middleman/muting/clear')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('muted_listeners', []);

    $this->assertDatabaseHas('middleman_audit_trail', ['action' => 'muted_listeners_cleared']);
});

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// README Coverage: Prune command operational path
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

test('middleman prune command dry-run executes successfully', function (): void {
    MiddleManLog::create([
        'event_class'      => 'App\\Events\\OldLog',
        'event_name'       => 'OldLog',
        'payload'          => ['k' => 'v'],
        'metadata'         => [],
        'fired_at'         => now()->subDays(30),
        'correlation_id'   => null,
        'causation_id'     => null,
        'is_replay'        => false,
        'has_schema_drift' => false,
    ]);

    $this->artisan('middleman:prune', ['--dry-run' => true, '--logs-days' => 7])
        ->assertSuccessful();
});

test('logging and intercept detail endpoints return selected records', function (): void {
    $admin = createMiddleManAdmin();

    $log = MiddleManLog::create([
        'event_class'      => ReplayableTestEvent::class,
        'event_name'       => 'ReplayableTestEvent',
        'payload'          => ['message' => 'detail', 'code' => 200],
        'metadata'         => [],
        'fired_at'         => now(),
        'correlation_id'   => null,
        'causation_id'     => null,
        'is_replay'        => false,
        'has_schema_drift' => false,
    ]);

    $intercept = MiddleManIntercept::create([
        'event_class'    => ReplayableTestEvent::class,
        'event_name'     => 'ReplayableTestEvent',
        'payload'        => ['message' => 'intercept-detail', 'code' => 200],
        'metadata'       => [],
        'status'         => MiddleManIntercept::STATUS_PENDING,
        'sort_order'     => 1,
        'intercepted_at' => now(),
    ]);

    $this->actingAs($admin)
        ->getJson('/middleman/logging/' . $log->id)
        ->assertOk()
        ->assertJsonPath('id', $log->id);

    $this->actingAs($admin)
        ->getJson('/middleman/intercept/' . $intercept->id)
        ->assertOk()
        ->assertJsonPath('id', $intercept->id);
});

test('marshal fire without hold dispatches event immediately', function (): void {
    Event::fake([ReplayableTestEvent::class]);

    $admin = createMiddleManAdmin();

    $this->actingAs($admin)
        ->postJson('/middleman/marshal/fire', [
            'event_class' => ReplayableTestEvent::class,
            'payload'     => ['message' => 'live-fire', 'code' => 299],
            'hold'        => false,
        ])
        ->assertOk()
        ->assertJsonPath('action', 'fired');

    Event::assertDispatched(ReplayableTestEvent::class, function (ReplayableTestEvent $event): bool {
        return $event->message === 'live-fire' && $event->code === 299;
    });
});

test('tracing page filter scopes results to selected correlation id', function (): void {
    $admin = createMiddleManAdmin();

    MiddleManLog::create([
        'event_class'      => 'App\\Events\\TraceA',
        'event_name'       => 'TraceA',
        'payload'          => [],
        'metadata'         => [],
        'fired_at'         => now(),
        'correlation_id'   => 'corr-a',
        'causation_id'     => null,
        'is_replay'        => false,
        'has_schema_drift' => false,
    ]);

    MiddleManLog::create([
        'event_class'      => 'App\\Events\\TraceB',
        'event_name'       => 'TraceB',
        'payload'          => [],
        'metadata'         => [],
        'fired_at'         => now(),
        'correlation_id'   => 'corr-b',
        'causation_id'     => null,
        'is_replay'        => false,
        'has_schema_drift' => false,
    ]);

    $this->actingAs($admin)
        ->get('/middleman/tracing?correlation_id=corr-a')
        ->assertOk()
        ->assertSee('corr-a')
        ->assertSee('TraceA')
        ->assertDontSee('TraceB');
});

test('intercept fire marks record corrupted and returns 422 when dispatch throws', function (): void {
    $admin = createMiddleManAdmin();

    $intercept = MiddleManIntercept::create([
        'event_class'    => ReplayableTestEvent::class,
        'event_name'     => 'ReplayableTestEvent',
        'payload'        => ['message' => 'boom', 'code' => 500],
        'metadata'       => ['source' => 'test'],
        'status'         => MiddleManIntercept::STATUS_PENDING,
        'sort_order'     => 1,
        'intercepted_at' => now(),
    ]);

    $dispatcher = \Mockery::mock(\Modules\MiddleMan\Services\MiddleManDispatcher::class, [app()])->makePartial();
    $dispatcher->shouldReceive('dispatchBypassing')->andThrow(new \RuntimeException('forced dispatch failure'));
    app()->instance(\Illuminate\Contracts\Events\Dispatcher::class, $dispatcher);

    $this->actingAs($admin)
        ->postJson('/middleman/intercept/' . $intercept->id . '/fire')
        ->assertStatus(422)
        ->assertJsonPath('error', 'Event hydration failed. The intercept has been marked CORRUPTED.');

    $intercept->refresh();
    expect($intercept->status)->toBe(MiddleManIntercept::STATUS_CORRUPTED);
    expect((string) $intercept->resolution_notes)->toContain('forced dispatch failure');
});

test('replay sequence endpoint rejects payloads over 200 ids', function (): void {
    $admin = createMiddleManAdmin();

    $this->actingAs($admin)
        ->postJson('/middleman/replay/sequence', [
            'source' => 'logs',
            'ids'    => range(1, 201),
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['ids']);
});

test('topology diagram endpoint returns svg when kroki is enabled and reachable', function (): void {
    $admin = createMiddleManAdmin();

    config()->set('middleman.kroki.enabled', true);
    config()->set('middleman.kroki.base_url', 'http://kroki.test');

    Http::fake([
        'http://kroki.test/graphviz/svg' => Http::response('<svg xmlns="http://www.w3.org/2000/svg"></svg>', 200),
    ]);

    $this->actingAs($admin)
        ->get('/middleman/topology/diagram.svg')
        ->assertOk()
        ->assertHeader('Content-Type', 'image/svg+xml');
});

test('circuit breaker trip and close manage open state and fallback flag file', function (): void {
    $breaker = app(CircuitBreaker::class);
    $flag = storage_path('framework/middleman_breaker.flag');

    if (file_exists($flag)) {
        unlink($flag);
    }

    $breaker->trip('forced infrastructure failure', true);

    expect($breaker->getState())->toBe(CircuitBreaker::STATE_OPEN);
    expect(file_exists($flag))->toBeTrue();

    $breaker->close('test cleanup');

    expect($breaker->getState())->toBe(CircuitBreaker::STATE_CLOSED);
    expect(file_exists($flag))->toBeFalse();
});
