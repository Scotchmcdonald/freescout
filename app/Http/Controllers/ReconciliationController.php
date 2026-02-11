<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ReconciliationRun;
use App\Models\ReconciliationDiscrepancy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ReconciliationController extends Controller
{
    /**
     * Display reconciliation history dashboard.
     */
    public function index(): View
    {
        $runs = ReconciliationRun::with('discrepancies')
            ->orderBy('started_at', 'desc')
            ->limit(50)
            ->get();

        // Calculate metrics
        $recentRuns = ReconciliationRun::recent(30)->get();
        
        $metrics = [
            'total_runs' => $recentRuns->count(),
            'successful_runs' => $recentRuns->where('status', 'completed')
                ->where('critical_issues', 0)->count(),
            'failed_runs' => $recentRuns->where('status', 'failed')->count(),
            'critical_issues' => $recentRuns->sum('critical_issues'),
            'total_discrepancies' => $recentRuns->sum('total_discrepancies'),
            'auto_corrected' => $recentRuns->sum('auto_corrected'),
            'avg_success_rate' => $recentRuns->avg('success_rate') ?? 0,
        ];

        // Pending manual reviews across all recent runs
        $pendingReviews = ReconciliationDiscrepancy::whereHas('run', function (\Illuminate\Database\Eloquent\Builder $query) {
            /** @phpstan-var \Illuminate\Database\Eloquent\Builder<\App\Models\ReconciliationRun> $query */
            $query->recent(30);
        })->whereIn('resolution_status', ['pending', 'manual_review'])
          ->with('run')
          ->orderBy('severity', 'desc')
          ->get();

        // Trend data for last 12 runs
        $trendData = ReconciliationRun::orderBy('started_at', 'desc')
            ->limit(12)
            ->get()
            ->reverse()
            ->map(function ($run) {
                return [
                    'date' => $run->started_at->format('M d'),
                    'success_rate' => $run->success_rate ?? 0,
                    'discrepancies' => $run->total_discrepancies,
                    'critical' => $run->critical_issues,
                ];
            });

        return view('reconciliation.index', compact('runs', 'metrics', 'pendingReviews', 'trendData'));
    }

    /**
     * Show detailed view of a specific reconciliation run.
     */
    public function show(ReconciliationRun $run): View
    {
        $run->load(['discrepancies.resolver']);

        // Group discrepancies by entity type
        $discrepanciesByType = $run->discrepancies->groupBy('entity_type');

        // Group by severity
        $discrepanciesBySeverity = $run->discrepancies->groupBy('severity');

        // Group by resolution status
        $discrepanciesByStatus = $run->discrepancies->groupBy('resolution_status');

        return view('reconciliation.show', compact(
            'run',
            'discrepanciesByType',
            'discrepanciesBySeverity',
            'discrepanciesByStatus'
        ));
    }

    /**
     * Resolve a discrepancy.
     */
    public function resolve(Request $request, ReconciliationDiscrepancy $discrepancy): RedirectResponse
    {
        $request->validate([
            'resolution_action' => 'required|string|max:255',
            'resolution_notes' => 'nullable|string',
        ]);

        try {
            $discrepancy->update([
                'resolution_status' => 'resolved',
                'resolution_action' => $request->resolution_action,
                'resolution_notes' => $request->resolution_notes,
                'resolved_at' => now(),
                'resolved_by' => auth()->id(),
            ]);

            Log::info('Discrepancy resolved', [
                'discrepancy_id' => $discrepancy->id,
                'run_id' => $discrepancy->reconciliation_run_id,
                'resolved_by' => auth()->id(),
                'action' => $request->resolution_action,
            ]);

            return redirect()->back()
                ->with('success', 'Discrepancy resolved successfully');
        } catch (\Exception $e) {
            Log::error('Failed to resolve discrepancy', [
                'discrepancy_id' => $discrepancy->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to resolve discrepancy: ' . $e->getMessage());
        }
    }

    /**
     * Trigger a manual reconciliation run.
     */
    public function trigger(Request $request): RedirectResponse
    {
        $request->validate([
            'scope' => 'nullable|array',
        ]);

        try {
            $run = ReconciliationRun::create([
                'run_type' => 'manual',
                'status' => 'running',
                'started_at' => now(),
                'scope' => $request->scope,
                'triggered_by' => 'admin',
            ]);

            Log::info('Manual reconciliation triggered', [
                'run_id' => $run->id,
                'triggered_by' => auth()->id(),
            ]);

            // In production, this would dispatch a job to perform reconciliation
            // For now, we'll just create the run record
            
            return redirect()->route('reconciliation.show', $run)
                ->with('success', 'Reconciliation run started successfully');
        } catch (\Exception $e) {
            Log::error('Failed to trigger reconciliation', [
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to start reconciliation: ' . $e->getMessage());
        }
    }
}
