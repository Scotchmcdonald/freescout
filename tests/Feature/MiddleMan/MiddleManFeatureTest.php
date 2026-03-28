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
use Illuminate\Support\Facades\Queue;
use Modules\MiddleMan\Models\MiddleManAuditEntry;
use Modules\MiddleMan\Models\MiddleManIntercept;
use Modules\MiddleMan\Models\MiddleManLog;
use Modules\MiddleMan\Models\MiddleManSchema;
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
