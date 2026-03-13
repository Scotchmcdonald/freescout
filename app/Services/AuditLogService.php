<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Models\Activity;

/**
 * Centralized audit logging service for sensitive operations.
 *
 * This service provides structured logging for:
 * - Financial operations (credit adjustments, payments)
 * - Security operations (permission changes, impersonation)
 * - Business operations (quote approvals, contract modifications)
 * - Data access (exports, bulk queries)
 *
 * All audit logs are stored in the activity_log table via spatie/laravel-activitylog.
 */
class AuditLogService
{
    /**
     * Log a sensitive operation.
     *
     * @param  string  $operation  Operation identifier
     * @param  Model|null  $subject  Model being operated on
     * @param  array<string, mixed>  $properties  Additional context
     * @param  string  $logName  Category for filtering
     * @param  Model|null  $causer  User performing the operation
     */
    public function logSensitiveOperation(
        string $operation,
        ?Model $subject = null,
        array $properties = [],
        string $logName = 'sensitive_operations',
        ?Model $causer = null
    ): Activity {
        // Enrich properties with request context
        $enrichedProperties = $this->enrichProperties($properties);

        $activity = ActivityLog::record(
            description: $operation,
            logName: $logName,
            properties: $enrichedProperties,
            subject: $subject,
            causer: $causer,
        );

        // Also log to Laravel log for redundancy
        Log::channel('audit')->info("Audit: {$operation}", [
            'log_name' => $logName,
            'causer_id' => $causer?->getKey(),
            'causer_type' => $causer ? get_class($causer) : null,
            'subject_id' => $subject?->getKey(),
            'subject_type' => $subject ? get_class($subject) : null,
            'properties' => $enrichedProperties,
        ]);

        return $activity;
    }

    /**
     * Query audit logs with filters.
     *
     * @param  array<string, mixed>  $filters
     * @return \Illuminate\Database\Eloquent\Builder<Activity>
     */
    public function queryLogs(array $filters = []): \Illuminate\Database\Eloquent\Builder
    {
        $query = Activity::query();

        if (isset($filters['log_name'])) {
            $query->where('log_name', $filters['log_name']);
        }

        if (isset($filters['causer_id'])) {
            $query->where('causer_id', $filters['causer_id']);
        }

        if (isset($filters['subject_type'])) {
            $query->where('subject_type', $filters['subject_type']);
        }

        if (isset($filters['subject_id'])) {
            $query->where('subject_id', $filters['subject_id']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['description_like'])) {
            $val = $filters['description_like'];
            $strVal = is_string($val) ? $val : '';
            $query->where('description', 'like', '%'.$strVal.'%');
        }

        return $query->latest();
    }

    /**
     * Get audit summary for a subject.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Activity>
     */
    public function getSubjectAuditTrail(Model $subject, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return Activity::query()
            ->where('subject_type', get_class($subject))
            ->where('subject_id', $subject->getKey())
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Get recent sensitive operations.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Activity>
     */
    public function getRecentSensitiveOperations(int $hours = 24, int $limit = 100): \Illuminate\Database\Eloquent\Collection
    {
        return Activity::query()
            ->whereIn('log_name', [
                'sensitive_operations',
                'financial_operations',
                'bulk_operations',
                'data_access',
            ])
            ->where('created_at', '>=', now()->subHours($hours))
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Enrich properties with request context.
     *
     * @param  array<string, mixed>  $properties
     * @return array<string, mixed>
     */
    protected function enrichProperties(array $properties): array
    {
        $request = request();

        return array_merge($properties, [
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
