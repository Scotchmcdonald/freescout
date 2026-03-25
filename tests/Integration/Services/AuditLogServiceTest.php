<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use App\Models\User;
use App\Services\AuditLogService;
use Spatie\Activitylog\Models\Activity;
use Tests\IntegrationTestCase;

class AuditLogServiceTest extends IntegrationTestCase
{
    private AuditLogService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AuditLogService::class);
    }

    public function test_log_sensitive_operation_records_activity(): void
    {
        $causer = User::factory()->create();
        $subject = User::factory()->create();

        $activity = $this->service->logSensitiveOperation(
            'user.permission_changed',
            $subject,
            ['from' => 'user', 'to' => 'admin'],
            'security_operations',
            $causer
        );

        $this->assertNotNull($activity->id);
        $this->assertDatabaseHas('activity_log', [
            'description' => 'user.permission_changed',
            'log_name' => 'security_operations',
            'causer_id' => $causer->id,
            'subject_id' => $subject->id,
            'subject_type' => User::class,
        ]);
    }

    public function test_log_sensitive_operation_without_subject(): void
    {
        $causer = User::factory()->create();

        $activity = $this->service->logSensitiveOperation(
            'system.config_changed',
            null,
            ['setting' => 'payment_gateway'],
            'system_operations',
            $causer
        );

        $this->assertNotNull($activity->id);
        $this->assertDatabaseHas('activity_log', [
            'description' => 'system.config_changed',
            'log_name' => 'system_operations',
            'causer_id' => $causer->id,
            'subject_id' => null,
        ]);
    }

    public function test_log_sensitive_operation_without_causer(): void
    {
        $subject = User::factory()->create();

        $activity = $this->service->logSensitiveOperation(
            'batch.import_executed',
            $subject,
            ['count' => 100],
            'data_operations'
        );

        $this->assertNotNull($activity->id);
        $this->assertDatabaseHas('activity_log', [
            'description' => 'batch.import_executed',
            'log_name' => 'data_operations',
            'causer_id' => null,
            'subject_id' => $subject->id,
        ]);
    }

    public function test_log_sensitive_operation_default_log_name(): void
    {
        $activity = $this->service->logSensitiveOperation('test.operation');

        $this->assertDatabaseHas('activity_log', [
            'description' => 'test.operation',
            'log_name' => 'sensitive_operations',
        ]);
    }

    public function test_query_logs_by_log_name(): void
    {
        Activity::query()->delete();
        for ($i = 0; $i < 3; $i++) {
            Activity::create([
                'log_name' => 'security_operations',
                'description' => "operation_$i",
                'subject_type' => null,
                'subject_id' => null,
                'causer_type' => null,
                'causer_id' => null,
                'properties' => [],
            ]);
        }
        for ($i = 0; $i < 2; $i++) {
            Activity::create([
                'log_name' => 'data_operations',
                'description' => "operation_$i",
                'subject_type' => null,
                'subject_id' => null,
                'causer_type' => null,
                'causer_id' => null,
                'properties' => [],
            ]);
        }

        $results = $this->service->queryLogs(['log_name' => 'security_operations'])->get();

        $this->assertCount(3, $results);
        $this->assertTrue($results->every(fn ($a) => $a->log_name === 'security_operations'));
    }

    public function test_query_logs_by_causer_id(): void
    {
        Activity::query()->delete();
        $causer1 = User::factory()->create();
        $causer2 = User::factory()->create();

        for ($i = 0; $i < 2; $i++) {
            Activity::create([
                'log_name' => 'test',
                'description' => "op_$i",
                'subject_type' => null,
                'subject_id' => null,
                'causer_type' => User::class,
                'causer_id' => $causer1->id,
                'properties' => [],
            ]);
        }
        for ($i = 0; $i < 3; $i++) {
            Activity::create([
                'log_name' => 'test',
                'description' => "op_$i",
                'subject_type' => null,
                'subject_id' => null,
                'causer_type' => User::class,
                'causer_id' => $causer2->id,
                'properties' => [],
            ]);
        }

        $results = $this->service->queryLogs(['causer_id' => $causer1->id])->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($a) => $a->causer_id === $causer1->id));
    }

    public function test_query_logs_by_subject_type(): void
    {
        Activity::query()->delete();
        for ($i = 0; $i < 2; $i++) {
            Activity::create([
                'log_name' => 'test',
                'description' => "op_$i",
                'subject_type' => User::class,
                'subject_id' => 1,
                'causer_type' => null,
                'causer_id' => null,
                'properties' => [],
            ]);
        }
        Activity::create([
            'log_name' => 'test',
            'description' => 'op_1',
            'subject_type' => 'App\Models\Invoice',
            'subject_id' => 1,
            'causer_type' => null,
            'causer_id' => null,
            'properties' => [],
        ]);

        $results = $this->service->queryLogs(['subject_type' => User::class])->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($a) => $a->subject_type === User::class));
    }

    public function test_query_logs_by_subject_id(): void
    {
        Activity::query()->delete();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        for ($i = 0; $i < 2; $i++) {
            Activity::create([
                'log_name' => 'test',
                'description' => "op_$i",
                'subject_type' => User::class,
                'subject_id' => $user1->id,
                'causer_type' => null,
                'causer_id' => null,
                'properties' => [],
            ]);
        }
        Activity::create([
            'log_name' => 'test',
            'description' => 'op_1',
            'subject_type' => User::class,
            'subject_id' => $user2->id,
            'causer_type' => null,
            'causer_id' => null,
            'properties' => [],
        ]);

        $results = $this->service->queryLogs(['subject_id' => $user1->id])->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($a) => $a->subject_id === $user1->id));
    }

    public function test_query_logs_by_date_range(): void
    {
        Activity::query()->delete();
        $base = now();

        Activity::create([
            'log_name' => 'test',
            'description' => 'old',
            'subject_type' => null,
            'subject_id' => null,
            'causer_type' => null,
            'causer_id' => null,
            'properties' => [],
            'created_at' => $base->clone()->subDays(5),
        ]);
        Activity::create([
            'log_name' => 'test',
            'description' => 'in_range_early',
            'subject_type' => null,
            'subject_id' => null,
            'causer_type' => null,
            'causer_id' => null,
            'properties' => [],
            'created_at' => $base->clone()->subDay(),
        ]);
        Activity::create([
            'log_name' => 'test',
            'description' => 'in_range_mid',
            'subject_type' => null,
            'subject_id' => null,
            'causer_type' => null,
            'causer_id' => null,
            'properties' => [],
            'created_at' => $base,
        ]);
        Activity::create([
            'log_name' => 'test',
            'description' => 'future',
            'subject_type' => null,
            'subject_id' => null,
            'causer_type' => null,
            'causer_id' => null,
            'properties' => [],
            'created_at' => $base->clone()->addDays(5),
        ]);

        $results = $this->service->queryLogs([
            'date_from' => $base->clone()->subDays(2),
            'date_to' => $base->clone()->addDays(2),
        ])->get();

        // Should get both mid and early records
        $this->assertCount(2, $results);
        $this->assertTrue($results->contains('description', 'in_range_early'));
        $this->assertTrue($results->contains('description', 'in_range_mid'));
    }

    public function test_query_logs_by_description_like(): void
    {
        Activity::query()->delete();
        Activity::create([
            'log_name' => 'test',
            'description' => 'user.created',
            'subject_type' => null,
            'subject_id' => null,
            'causer_type' => null,
            'causer_id' => null,
            'properties' => [],
        ]);
        Activity::create([
            'log_name' => 'test',
            'description' => 'user.deleted',
            'subject_type' => null,
            'subject_id' => null,
            'causer_type' => null,
            'causer_id' => null,
            'properties' => [],
        ]);
        Activity::create([
            'log_name' => 'test',
            'description' => 'permission.changed',
            'subject_type' => null,
            'subject_id' => null,
            'causer_type' => null,
            'causer_id' => null,
            'properties' => [],
        ]);

        $results = $this->service->queryLogs(['description_like' => 'user'])->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($a) => str_contains($a->description, 'user')));
    }

    public function test_query_logs_combined_filters(): void
    {
        Activity::query()->delete();
        $causer = User::factory()->create();
        $subject = User::factory()->create();

        Activity::create([
            'log_name' => 'security_operations',
            'causer_type' => User::class,
            'causer_id' => $causer->id,
            'subject_id' => $subject->id,
            'subject_type' => User::class,
            'description' => 'user.permission_changed',
            'properties' => [],
        ]);
        for ($i = 0; $i < 3; $i++) {
            Activity::create([
                'log_name' => 'test',
                'description' => "noise_$i",
                'subject_type' => null,
                'subject_id' => null,
                'causer_type' => null,
                'causer_id' => null,
                'properties' => [],
            ]);
        }

        $results = $this->service->queryLogs([
            'log_name' => 'security_operations',
            'causer_id' => $causer->id,
            'subject_type' => User::class,
        ])->get();

        $this->assertCount(1, $results);
        $this->assertEquals($subject->id, $results[0]->subject_id);
    }

    public function test_query_logs_returns_latest_first(): void
    {
        Activity::query()->delete();
        $first = Activity::create([
            'log_name' => 'test',
            'description' => 'first',
            'subject_type' => null,
            'subject_id' => null,
            'causer_type' => null,
            'causer_id' => null,
            'properties' => [],
            'created_at' => now()->subDays(1),
        ]);
        $second = Activity::create([
            'log_name' => 'test',
            'description' => 'second',
            'subject_type' => null,
            'subject_id' => null,
            'causer_type' => null,
            'causer_id' => null,
            'properties' => [],
            'created_at' => now(),
        ]);

        $results = $this->service->queryLogs()->get();

        $this->assertEquals($second->id, $results[0]->id);
        $this->assertEquals($first->id, $results[1]->id);
    }

    public function test_get_subject_audit_trail(): void
    {
        Activity::query()->delete();
        $subject = User::factory()->create();
        $otherSubject = User::factory()->create();

        for ($i = 0; $i < 3; $i++) {
            Activity::create([
                'log_name' => 'test',
                'description' => "op_$i",
                'subject_id' => $subject->id,
                'subject_type' => User::class,
                'causer_type' => null,
                'causer_id' => null,
                'properties' => [],
            ]);
        }
        for ($i = 0; $i < 2; $i++) {
            Activity::create([
                'log_name' => 'test',
                'description' => "op_$i",
                'subject_id' => $otherSubject->id,
                'subject_type' => User::class,
                'causer_type' => null,
                'causer_id' => null,
                'properties' => [],
            ]);
        }

        $trail = $this->service->getSubjectAuditTrail($subject);

        $this->assertCount(3, $trail);
        $this->assertTrue($trail->every(
            fn ($a) => $a->subject_id === $subject->id && $a->subject_type === User::class
        ));
    }

    public function test_get_subject_audit_trail_respects_limit(): void
    {
        Activity::query()->delete();
        $subject = User::factory()->create();
        for ($i = 0; $i < 100; $i++) {
            Activity::create([
                'log_name' => 'test',
                'description' => "op_$i",
                'subject_id' => $subject->id,
                'subject_type' => User::class,
                'causer_type' => null,
                'causer_id' => null,
                'properties' => [],
            ]);
        }

        $trail = $this->service->getSubjectAuditTrail($subject, 10);

        $this->assertCount(10, $trail);
    }

    public function test_enriched_properties_included_in_log(): void
    {
        $causer = User::factory()->create();
        $this->actingAs($causer);

        $activity = $this->service->logSensitiveOperation(
            'test.operation',
            null,
            ['custom_field' => 'custom_value'],
            'test_operations',
            $causer
        );

        $record = Activity::find($activity->id);
        $properties = $record->properties;

        // enrichProperties adds ip_address, user_agent, timestamp
        $this->assertArrayHasKey('custom_field', $properties);
        $this->assertEquals('custom_value', $properties['custom_field']);
        $this->assertArrayHasKey('ip_address', $properties);
        $this->assertArrayHasKey('user_agent', $properties);
        $this->assertArrayHasKey('timestamp', $properties);
    }

    public function test_empty_filter_returns_all_logs(): void
    {
        Activity::query()->delete();
        for ($i = 0; $i < 5; $i++) {
            Activity::create([
                'log_name' => 'test',
                'description' => "op_$i",
                'subject_type' => null,
                'subject_id' => null,
                'causer_type' => null,
                'causer_id' => null,
                'properties' => [],
            ]);
        }

        $results = $this->service->queryLogs([])->get();

        $this->assertCount(5, $results);
    }
}
