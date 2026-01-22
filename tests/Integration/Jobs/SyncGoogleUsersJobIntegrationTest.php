<?php

declare(strict_types=1);

namespace Tests\Integration\Jobs;

use App\DataTransferObjects\GoogleUserSyncedData;
use Modules\GoogleAdmin\Events\GoogleUserSynced;
use Modules\GoogleAdmin\Jobs\SyncGoogleUsersJob;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * SyncGoogleUsersJob Integration Tests
 * 
 * Tests the Google Workspace user sync job infrastructure without requiring
 * actual Google API connections. Focuses on:
 * - DTO validation and data integrity
 * - Event structure and dispatching
 * - Job configuration (retries, backoff)
 * - Data transformation accuracy
 */
#[Group('integration')]
#[Group('jobs')]
#[Group('googleadmin')]
#[Group('external')]
class SyncGoogleUsersJobIntegrationTest extends TestCase
{
    /**
     * Test job has correct retry configuration.
     */
    public function test_job_has_correct_retry_configuration(): void
    {
        $job = new SyncGoogleUsersJob(
            clientId: 1,
            domain: 'example.com'
        );

        $this->assertEquals(3, $job->tries);
        $this->assertEquals(60, $job->backoff);
    }

    /**
     * Test job stores correct construction parameters.
     */
    public function test_job_stores_construction_parameters(): void
    {
        $job = new SyncGoogleUsersJob(
            clientId: 42,
            domain: 'test-domain.com',
            orgUnitPath: '/Engineering'
        );

        $this->assertEquals(42, $job->clientId);
        $this->assertEquals('test-domain.com', $job->domain);
        $this->assertEquals('/Engineering', $job->orgUnitPath);
    }

    /**
     * Test job allows null org unit path.
     */
    public function test_job_allows_null_org_unit_path(): void
    {
        $job = new SyncGoogleUsersJob(
            clientId: 1,
            domain: 'example.com',
            orgUnitPath: null
        );

        $this->assertNull($job->orgUnitPath);
    }

    /**
     * Test job tracks sync operation id.
     */
    public function test_job_tracks_sync_operation_id(): void
    {
        $job = new SyncGoogleUsersJob(
            clientId: 1,
            domain: 'example.com',
            syncOperationId: 123
        );

        $this->assertEquals(123, $job->syncOperationId);
    }

    /**
     * Test GoogleUserSyncedData DTO creation.
     */
    public function test_google_user_synced_data_dto_creation(): void
    {
        $dto = new GoogleUserSyncedData(
            clientId: 1,
            email: 'user@example.com',
            firstName: 'John',
            lastName: 'Doe',
            googleId: 'google-123',
            suspended: false,
            orgUnitPath: '/Users',
            metadata: ['is_admin' => true]
        );

        $this->assertEquals(1, $dto->clientId);
        $this->assertEquals('user@example.com', $dto->email);
        $this->assertEquals('John', $dto->firstName);
        $this->assertEquals('Doe', $dto->lastName);
        $this->assertEquals('google-123', $dto->googleId);
        $this->assertFalse($dto->suspended);
        $this->assertEquals('/Users', $dto->orgUnitPath);
        $this->assertTrue($dto->metadata['is_admin']);
    }

    /**
     * Test GoogleUserSyncedData fromArray factory.
     */
    public function test_google_user_synced_data_from_array(): void
    {
        $dto = GoogleUserSyncedData::fromArray([
            'client_id' => 5,
            'email' => 'test@company.com',
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'google_id' => 'g-456',
            'suspended' => true,
            'org_unit_path' => '/Contractors',
            'metadata' => ['creation_time' => '2024-01-01'],
        ]);

        $this->assertEquals(5, $dto->clientId);
        $this->assertEquals('test@company.com', $dto->email);
        $this->assertEquals('Jane', $dto->firstName);
        $this->assertEquals('Smith', $dto->lastName);
        $this->assertEquals('g-456', $dto->googleId);
        $this->assertTrue($dto->suspended);
        $this->assertEquals('/Contractors', $dto->orgUnitPath);
    }

    /**
     * Test GoogleUserSyncedData fromArray handles alternate key names.
     */
    public function test_google_user_synced_data_handles_camel_case_keys(): void
    {
        $dto = GoogleUserSyncedData::fromArray([
            'client_id' => 1,
            'email' => 'user@test.com',
            'firstName' => 'First',
            'lastName' => 'Last',
            'googleId' => 'gid-789',
            'orgUnitPath' => '/Sales',
            'metadata' => [],
        ]);

        $this->assertEquals('First', $dto->firstName);
        $this->assertEquals('Last', $dto->lastName);
        $this->assertEquals('gid-789', $dto->googleId);
        $this->assertEquals('/Sales', $dto->orgUnitPath);
    }

    /**
     * Test GoogleUserSyncedData toArray conversion.
     */
    public function test_google_user_synced_data_to_array(): void
    {
        $dto = new GoogleUserSyncedData(
            clientId: 10,
            email: 'convert@test.com',
            firstName: 'Convert',
            lastName: 'Test',
            googleId: 'convert-id',
            suspended: false,
            orgUnitPath: '/',
            metadata: ['key' => 'value']
        );

        $array = $dto->toArray();

        $this->assertEquals(10, $array['client_id']);
        $this->assertEquals('convert@test.com', $array['email']);
        $this->assertEquals('Convert', $array['first_name']);
        $this->assertEquals('Test', $array['last_name']);
        $this->assertEquals('convert-id', $array['google_id']);
        $this->assertFalse($array['suspended']);
        $this->assertEquals('/', $array['org_unit_path']);
        $this->assertEquals('value', $array['metadata']['key']);
    }

    /**
     * Test GoogleUserSynced event can be dispatched.
     */
    public function test_google_user_synced_event_dispatches(): void
    {
        Event::fake([GoogleUserSynced::class]);

        $dto = new GoogleUserSyncedData(
            clientId: 1,
            email: 'event@test.com',
            firstName: 'Event',
            lastName: 'Test',
            googleId: 'event-id',
            suspended: false,
            orgUnitPath: '/',
            metadata: []
        );

        event(new GoogleUserSynced($dto));

        Event::assertDispatched(GoogleUserSynced::class, function ($event) {
            return $event->data->email === 'event@test.com';
        });
    }

    /**
     * Test DTO handles missing optional metadata fields.
     */
    public function test_dto_handles_missing_optional_fields(): void
    {
        $dto = GoogleUserSyncedData::fromArray([
            'client_id' => 1,
            'email' => 'minimal@test.com',
            'google_id' => 'min-id',
            'metadata' => [],
        ]);

        $this->assertEquals('', $dto->firstName);
        $this->assertEquals('', $dto->lastName);
        $this->assertFalse($dto->suspended);
        $this->assertEquals('/', $dto->orgUnitPath);
    }

    /**
     * Test DTO preserves complex metadata.
     */
    public function test_dto_preserves_complex_metadata(): void
    {
        $metadata = [
            'last_login' => '2024-01-15T10:00:00Z',
            'creation_time' => '2023-06-01T08:00:00Z',
            'is_admin' => true,
            'is_delegated_admin' => false,
            'custom_attributes' => [
                'department' => 'Engineering',
                'employee_id' => 'EMP001',
            ],
        ];

        $dto = new GoogleUserSyncedData(
            clientId: 1,
            email: 'meta@test.com',
            firstName: 'Meta',
            lastName: 'Test',
            googleId: 'meta-id',
            suspended: false,
            orgUnitPath: '/',
            metadata: $metadata
        );

        $this->assertEquals('2024-01-15T10:00:00Z', $dto->metadata['last_login']);
        $this->assertTrue($dto->metadata['is_admin']);
        $this->assertEquals('Engineering', $dto->metadata['custom_attributes']['department']);
    }

    /**
     * Test round-trip from DTO to array and back.
     */
    public function test_dto_round_trip_conversion(): void
    {
        $original = new GoogleUserSyncedData(
            clientId: 99,
            email: 'roundtrip@test.com',
            firstName: 'Round',
            lastName: 'Trip',
            googleId: 'rt-id',
            suspended: true,
            orgUnitPath: '/TestOrg',
            metadata: ['test' => true]
        );

        $array = $original->toArray();
        $rebuilt = GoogleUserSyncedData::fromArray($array);

        $this->assertEquals($original->clientId, $rebuilt->clientId);
        $this->assertEquals($original->email, $rebuilt->email);
        $this->assertEquals($original->firstName, $rebuilt->firstName);
        $this->assertEquals($original->lastName, $rebuilt->lastName);
        $this->assertEquals($original->googleId, $rebuilt->googleId);
        $this->assertEquals($original->suspended, $rebuilt->suspended);
        $this->assertEquals($original->orgUnitPath, $rebuilt->orgUnitPath);
        $this->assertEquals($original->metadata, $rebuilt->metadata);
    }
}
