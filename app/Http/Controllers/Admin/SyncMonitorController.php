<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SyncOperation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SyncMonitorController extends Controller
{
    /**
     * Display sync operations monitor
     */
    public function index(Request $request)
    {
        $query = SyncOperation::query();

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by source
        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }

        // Default to last 24 hours
        $hours = $request->get('hours', 24);
        $query->recent($hours);

        // Check for stalled operations and mark them
        $this->markStalledOperations();

        $operations = $query->paginate(20);

        // Get summary stats
        $stats = [
            'active' => SyncOperation::active()->count(),
            'completed_24h' => SyncOperation::recent(24)->where('status', 'completed')->count(),
            'failed_24h' => SyncOperation::recent(24)->where('status', 'failed')->count(),
            'stalled' => SyncOperation::where('status', 'stalled')->count(),
        ];

        // Get unique sources for filter
        $sources = SyncOperation::distinct()->pluck('source');

        return view('admin.sync-monitor.index', compact('operations', 'stats', 'sources'));
    }

    /**
     * Show detailed view of a sync operation
     */
    public function show(SyncOperation $operation)
    {
        return view('admin.sync-monitor.show', compact('operation'));
    }

    /**
     * Resume a stalled or paused operation
     */
    public function resume(SyncOperation $operation)
    {
        if (!in_array($operation->status, ['stalled', 'paused'])) {
            return back()->with('error', 'Only stalled or paused operations can be resumed.');
        }

        try {
            $operation->resume();

            // Dispatch job to continue sync based on checkpoint data
            $this->dispatchResume($operation);

            return back()->with('success', "Sync operation #{$operation->id} has been resumed.");
        } catch (\Exception $e) {
            Log::error("Failed to resume sync operation #{$operation->id}: " . $e->getMessage());
            return back()->with('error', 'Failed to resume operation: ' . $e->getMessage());
        }
    }

    /**
     * Retry a failed operation
     */
    public function retry(SyncOperation $operation)
    {
        if ($operation->status !== 'failed') {
            return back()->with('error', 'Only failed operations can be retried.');
        }

        try {
            // Create new operation with same parameters
            $newOperation = SyncOperation::start(
                $operation->operation_type,
                $operation->source,
                $operation->total_items
            );

            // Dispatch new sync job
            $this->dispatchRetry($newOperation);

            return redirect()
                ->route('admin.sync-monitor.show', $newOperation)
                ->with('success', "New sync operation #{$newOperation->id} has been started.");
        } catch (\Exception $e) {
            Log::error("Failed to retry sync operation: " . $e->getMessage());
            return back()->with('error', 'Failed to retry operation: ' . $e->getMessage());
        }
    }

    /**
     * Cancel a running operation
     */
    public function cancel(SyncOperation $operation)
    {
        if ($operation->status !== 'running') {
            return back()->with('error', 'Only running operations can be cancelled.');
        }

        $operation->update(['status' => 'cancelled']);

        return back()->with('success', "Sync operation #{$operation->id} has been cancelled.");
    }

    /**
     * Mark stalled operations (no progress in 5+ minutes)
     */
    protected function markStalledOperations(): void
    {
        SyncOperation::where('status', 'running')
            ->where('last_progress_at', '<', now()->subMinutes(5))
            ->update(['status' => 'stalled']);
    }

    /**
     * Dispatch resume job based on operation type
     */
    protected function dispatchResume(SyncOperation $operation): void
    {
        // Module-specific dispatch logic
        switch ($operation->source) {
            case 'GoogleAdmin':
                if (class_exists(\Modules\GoogleAdmin\Jobs\ResumeSyncJob::class)) {
                    \Modules\GoogleAdmin\Jobs\ResumeSyncJob::dispatch($operation);
                }
                break;

            case 'Action1':
                if (class_exists(\Modules\Action1\Jobs\ResumeSyncJob::class)) {
                    \Modules\Action1\Jobs\ResumeSyncJob::dispatch($operation);
                }
                break;

            default:
                throw new \Exception("Unknown sync source: {$operation->source}");
        }
    }

    /**
     * Dispatch retry job based on operation type
     */
    protected function dispatchRetry(SyncOperation $operation): void
    {
        // Module-specific dispatch logic
        switch ($operation->source) {
            case 'GoogleAdmin':
                if ($operation->operation_type === 'google_users' && class_exists(\Modules\GoogleAdmin\Jobs\SyncUsersJob::class)) {
                    \Modules\GoogleAdmin\Jobs\SyncUsersJob::dispatch($operation->id);
                }
                break;

            case 'Action1':
                if ($operation->operation_type === 'action1_devices' && class_exists(\Modules\Action1\Jobs\SyncDevicesJob::class)) {
                    \Modules\Action1\Jobs\SyncDevicesJob::dispatch($operation->id);
                }
                break;

            default:
                throw new \Exception("Unknown sync source: {$operation->source}");
        }
    }
}
