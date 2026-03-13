<?php

namespace Tests\Traits;

use App\Events\VersionedEvent;

/**
 * CreatesTestEvents - Test helper for creating events
 *
 * Provides convenient methods for creating test events and DTOs
 * with predictable data.
 */
trait CreatesTestEvents
{
    /**
     * Create a test event instance
     *
     * @param  string  $eventClass  Event class name
     * @param  array  $data  Event data
     * @param  string|null  $eventId  Optional event ID for idempotency testing
     */
    protected function createTestEvent(string $eventClass, array $data, ?string $eventId = null): VersionedEvent
    {
        // Check if event class has a DTO
        $reflection = new \ReflectionClass($eventClass);
        $constructor = $reflection->getConstructor();
        $params = $constructor->getParameters();

        // If first parameter is a DTO, create it
        if (count($params) > 0) {
            $firstParam = $params[0];
            $type = $firstParam->getType();

            if ($type && ! $type->isBuiltin()) {
                $dtoClass = $type->getName();

                // Check if DTO has fromArray method
                if (method_exists($dtoClass, 'fromArray')) {
                    $dto = $dtoClass::fromArray($data);

                    return new $eventClass($dto, $eventId);
                }
            }
        }

        // Fallback: pass data directly
        return new $eventClass($data, $eventId);
    }

    /**
     * Create a test DTO instance
     *
     * @param  string  $dtoClass  DTO class name
     * @param  array  $data  DTO data
     */
    protected function createTestDTO(string $dtoClass, array $data): object
    {
        if (method_exists($dtoClass, 'fromArray')) {
            return $dtoClass::fromArray($data);
        }

        // Fallback: use constructor
        return new $dtoClass(...array_values($data));
    }

    /**
     * Create test Google user data
     *
     * @param  array  $overrides  Data to override defaults
     */
    protected function createTestGoogleUserData(array $overrides = []): array
    {
        return array_merge([
            'client_id' => 1,
            'email' => 'test@example.com',
            'first_name' => 'Test',
            'last_name' => 'User',
            'google_id' => 'google_'.uniqid(),
            'suspended' => false,
            'org_unit_path' => '/',
            'metadata' => [],
        ], $overrides);
    }

    /**
     * Create test Chromebook data
     *
     * @param  array  $overrides  Data to override defaults
     */
    protected function createTestChromebookData(array $overrides = []): array
    {
        return array_merge([
            'client_id' => 1,
            'serial_number' => 'CHR-'.uniqid(),
            'model' => 'HP Chromebook 14',
            'status' => 'active',
            'assigned_user_email' => 'test@example.com',
            'metadata' => [],
        ], $overrides);
    }

    /**
     * Create test Action1 device data
     *
     * @param  array  $overrides  Data to override defaults
     */
    protected function createTestAction1DeviceData(array $overrides = []): array
    {
        return array_merge([
            'client_id' => 1,
            'hostname' => 'TEST-PC-'.uniqid(),
            'os_type' => 'windows',
            'os_version' => 'Windows 11 Pro',
            'action1_device_id' => 'action1_'.uniqid(),
            'is_online' => true,
            'assigned_user_email' => 'test@example.com',
            'metadata' => [],
        ], $overrides);
    }

    /**
     * Create test asset status change data
     *
     * @param  array  $overrides  Data to override defaults
     */
    protected function createTestAssetStatusChangeData(array $overrides = []): array
    {
        return array_merge([
            'asset_id' => 1,
            'client_id' => 1,
            'old_status' => 'active',
            'new_status' => 'inactive',
            'source' => 'Manual',
            'user_id' => null,
        ], $overrides);
    }
}
