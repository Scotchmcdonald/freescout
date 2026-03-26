<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\SystemDiagnosticsService;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Schema\Builder as SchemaBuilder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\MockInterface;
use RuntimeException;
use Tests\PureUnitTestCase;

/**
 * Pure-unit tests for SystemDiagnosticsService.
 *
 * All database interactions are replaced with Mockery doubles;
 * no framework is booted, no real DB is touched.
 */
final class SystemDiagnosticsServiceTest extends PureUnitTestCase
{
    /** @var MockInterface&DatabaseManager */
    private MockInterface $db;

    /** @var MockInterface&Connection */
    private MockInterface $connection;

    private SystemDiagnosticsService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = Mockery::mock(Connection::class);
        $this->db         = Mockery::mock(DatabaseManager::class);
        $this->db->shouldReceive('connection')->andReturn($this->connection)->byDefault();

        $this->service = new SystemDiagnosticsService($this->db);
    }

    // -------------------------------------------------------------------------
    // getDatabaseVersion
    // -------------------------------------------------------------------------

    public function test_get_database_version_mysql(): void
    {
        $this->connection->shouldReceive('getDriverName')->andReturn('mysql');
        $this->db->shouldReceive('select')
            ->with('SELECT VERSION() as version')
            ->andReturn([(object) ['version' => '8.0.32']]);

        $this->assertSame('8.0.32', $this->service->getDatabaseVersion());
    }

    public function test_get_database_version_sqlite(): void
    {
        $this->connection->shouldReceive('getDriverName')->andReturn('sqlite');
        $this->db->shouldReceive('select')
            ->with('SELECT sqlite_version() as version')
            ->andReturn([(object) ['version' => '3.39.0']]);

        $this->assertSame('3.39.0', $this->service->getDatabaseVersion());
    }

    public function test_get_database_version_pgsql(): void
    {
        $this->connection->shouldReceive('getDriverName')->andReturn('pgsql');
        $this->db->shouldReceive('select')
            ->with('SELECT version()')
            ->andReturn([(object) ['version' => 'PostgreSQL 15.2']]);

        $this->assertSame('PostgreSQL 15.2', $this->service->getDatabaseVersion());
    }

    public function test_get_database_version_unknown_driver(): void
    {
        $this->connection->shouldReceive('getDriverName')->andReturn('sqlsrv');

        $this->assertSame('Unknown', $this->service->getDatabaseVersion());
    }

    public function test_get_database_version_returns_unknown_on_exception(): void
    {
        $this->connection->shouldReceive('getDriverName')->andThrow(new RuntimeException('Connection refused'));

        $this->assertSame('Unknown', $this->service->getDatabaseVersion());
    }

    // -------------------------------------------------------------------------
    // checkDatabaseConnection
    // -------------------------------------------------------------------------

    public function test_check_database_connection_success(): void
    {
        $this->connection->shouldReceive('getPdo')->once();

        $result = $this->service->checkDatabaseConnection();

        $this->assertSame('ok', $result['status']);
        $this->assertSame('Database connection successful', $result['message']);
    }

    public function test_check_database_connection_failure(): void
    {
        $this->connection->shouldReceive('getPdo')
            ->andThrow(new RuntimeException('SQLSTATE[HY000]: Connection refused'));

        $result = $this->service->checkDatabaseConnection();

        $this->assertSame('error', $result['status']);
        $this->assertStringContainsString('Connection refused', $result['message']);
    }

    // -------------------------------------------------------------------------
    // checkQueueHealth
    // -------------------------------------------------------------------------

    public function test_check_queue_health_no_failed_jobs_table(): void
    {
        $schema = Mockery::mock(SchemaBuilder::class);
        $schema->shouldReceive('hasTable')->with('failed_jobs')->andReturn(false);
        $this->db->shouldReceive('getSchemaBuilder')->andReturn($schema);

        $result = $this->service->checkQueueHealth();

        $this->assertSame('ok', $result['status']);
        $this->assertSame('Queue table not configured', $result['message']);
    }

    public function test_check_queue_health_zero_failures(): void
    {
        $schema = Mockery::mock(SchemaBuilder::class);
        $schema->shouldReceive('hasTable')->with('failed_jobs')->andReturn(true);
        $this->db->shouldReceive('getSchemaBuilder')->andReturn($schema);

        $qb = Mockery::mock(QueryBuilder::class);
        $qb->shouldReceive('count')->andReturn(0);
        $this->db->shouldReceive('table')->with('failed_jobs')->andReturn($qb);

        $result = $this->service->checkQueueHealth();

        $this->assertSame('ok', $result['status']);
        $this->assertSame('Queue is healthy', $result['message']);
    }

    public function test_check_queue_health_with_failed_jobs(): void
    {
        $schema = Mockery::mock(SchemaBuilder::class);
        $schema->shouldReceive('hasTable')->with('failed_jobs')->andReturn(true);
        $this->db->shouldReceive('getSchemaBuilder')->andReturn($schema);

        $qb = Mockery::mock(QueryBuilder::class);
        $qb->shouldReceive('count')->andReturn(7);
        $this->db->shouldReceive('table')->with('failed_jobs')->andReturn($qb);

        $result = $this->service->checkQueueHealth();

        $this->assertSame('warning', $result['status']);
        $this->assertSame('7 failed jobs found', $result['message']);
    }

    public function test_check_queue_health_returns_error_on_exception(): void
    {
        $schema = Mockery::mock(SchemaBuilder::class);
        $schema->shouldReceive('hasTable')->andThrow(new RuntimeException('DB unavailable'));
        $this->db->shouldReceive('getSchemaBuilder')->andReturn($schema);

        $result = $this->service->checkQueueHealth();

        $this->assertSame('error', $result['status']);
        $this->assertStringContainsString('DB unavailable', $result['message']);
    }

    // -------------------------------------------------------------------------
    // cancelJob / retryJob
    // -------------------------------------------------------------------------

    public function test_cancel_job_deletes_from_jobs_table(): void
    {
        $qb = Mockery::mock(QueryBuilder::class);
        $qb->shouldReceive('where')->with('id', 42)->andReturnSelf();
        $qb->shouldReceive('delete')->once();
        $this->db->shouldReceive('table')->with('jobs')->andReturn($qb);

        $this->service->cancelJob(42);

        // Expectation already asserted by Mockery (->once())
        $this->addToAssertionCount(1);
    }

    public function test_retry_job_sets_available_at_to_now(): void
    {
        $qb = Mockery::mock(QueryBuilder::class);
        $qb->shouldReceive('where')->with('id', 'abc-uuid')->andReturnSelf();
        $qb->shouldReceive('update')
            ->with(Mockery::on(fn (array $data): bool => isset($data['available_at']) && is_int($data['available_at'])))
            ->once();
        $this->db->shouldReceive('table')->with('jobs')->andReturn($qb);

        $this->service->retryJob('abc-uuid');

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // deleteFailedJobsByQueue / getFailedJobsByQueue
    // -------------------------------------------------------------------------

    public function test_delete_failed_jobs_by_queue(): void
    {
        $qb = Mockery::mock(QueryBuilder::class);
        $qb->shouldReceive('where')->with('queue', 'emails')->andReturnSelf();
        $qb->shouldReceive('delete')->once();
        $this->db->shouldReceive('table')->with('failed_jobs')->andReturn($qb);

        $this->service->deleteFailedJobsByQueue('emails');

        $this->addToAssertionCount(1);
    }

    public function test_get_failed_jobs_by_queue_returns_collection(): void
    {
        $jobs = new Collection([(object) ['id' => 1, 'uuid' => 'abc']]);

        $qb = Mockery::mock(QueryBuilder::class);
        $qb->shouldReceive('where')->with('queue', 'default')->andReturnSelf();
        $qb->shouldReceive('get')->andReturn($jobs);
        $this->db->shouldReceive('table')->with('failed_jobs')->andReturn($qb);

        $result = $this->service->getFailedJobsByQueue('default');

        $this->assertCount(1, $result);
    }

    // -------------------------------------------------------------------------
    // getPolycastEvents — filter logic
    // -------------------------------------------------------------------------

    private function makeQueryBuilderWithPaginator(LengthAwarePaginator $paginator): MockInterface
    {
        $qb = Mockery::mock(QueryBuilder::class);
        $qb->shouldReceive('where')->andReturnSelf()->byDefault();
        $qb->shouldReceive('orderBy')->with('created_at', 'desc')->andReturnSelf();
        $qb->shouldReceive('paginate')->andReturn($paginator);
        $paginator->shouldReceive('withQueryString')->andReturn($paginator)->byDefault();

        return $qb;
    }

    /** @return MockInterface&LengthAwarePaginator */
    private function makePaginator(): MockInterface
    {
        return Mockery::mock(LengthAwarePaginator::class);
    }

    public function test_get_polycast_events_no_filters(): void
    {
        $paginator = $this->makePaginator();
        $paginator->shouldReceive('withQueryString')->andReturn($paginator);
        $qb = Mockery::mock(QueryBuilder::class);
        $qb->shouldReceive('orderBy')->with('created_at', 'desc')->andReturnSelf();
        $qb->shouldReceive('paginate')->with(20)->andReturn($paginator);
        $this->db->shouldReceive('table')->with('polycast_events')->andReturn($qb);

        $result = $this->service->getPolycastEvents();

        $this->assertSame($paginator, $result);
    }

    public function test_get_polycast_events_with_search_filter(): void
    {
        $paginator = $this->makePaginator();
        $paginator->shouldReceive('withQueryString')->andReturn($paginator);

        $qb = Mockery::mock(QueryBuilder::class);
        $qb->shouldReceive('where')
            ->with(Mockery::on(fn ($arg): bool => $arg instanceof \Closure))
            ->once()
            ->andReturnSelf();
        $qb->shouldReceive('orderBy')->andReturnSelf();
        $qb->shouldReceive('paginate')->andReturn($paginator);
        $this->db->shouldReceive('table')->with('polycast_events')->andReturn($qb);

        $result = $this->service->getPolycastEvents(['search' => 'test-event']);

        $this->assertSame($paginator, $result);
    }

    public function test_get_polycast_events_with_event_type_filter(): void
    {
        $paginator = $this->makePaginator();
        $paginator->shouldReceive('withQueryString')->andReturn($paginator);

        $qb = Mockery::mock(QueryBuilder::class);
        $qb->shouldReceive('where')->with('event', 'like', '%OrderCreated%')->once()->andReturnSelf();
        $qb->shouldReceive('orderBy')->andReturnSelf();
        $qb->shouldReceive('paginate')->andReturn($paginator);
        $this->db->shouldReceive('table')->with('polycast_events')->andReturn($qb);

        $result = $this->service->getPolycastEvents(['event_type' => 'OrderCreated']);

        $this->assertSame($paginator, $result);
    }

    public function test_get_polycast_events_with_date_range(): void
    {
        $paginator = $this->makePaginator();
        $paginator->shouldReceive('withQueryString')->andReturn($paginator);

        $qb = Mockery::mock(QueryBuilder::class);
        $qb->shouldReceive('where')->with('created_at', '>=', '2024-01-01')->once()->andReturnSelf();
        $qb->shouldReceive('where')->with('created_at', '<=', '2024-01-31 23:59:59')->once()->andReturnSelf();
        $qb->shouldReceive('orderBy')->andReturnSelf();
        $qb->shouldReceive('paginate')->andReturn($paginator);
        $this->db->shouldReceive('table')->with('polycast_events')->andReturn($qb);

        $result = $this->service->getPolycastEvents(['date_from' => '2024-01-01', 'date_to' => '2024-01-31']);

        $this->assertSame($paginator, $result);
    }
}
