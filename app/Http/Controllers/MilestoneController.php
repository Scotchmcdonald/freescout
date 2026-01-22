<?php

namespace App\Http\Controllers;

use App\Models\Milestone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MilestoneController extends Controller
{
    /**
     * Display a listing of milestones.
     */
    public function index(Request $request)
    {
        $query = Milestone::with('assignedUser')->ordered();

        // Filter by project if specified
        if ($request->has('project_type') && $request->has('project_id')) {
            $query->forProject($request->project_type, $request->project_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $milestones = $query->get();

        // Calculate overall project progress
        $totalMilestones = $milestones->count();
        $achievedMilestones = $milestones->where('status', 'achieved')->count();
        $overallProgress = $totalMilestones > 0 
            ? round(($achievedMilestones / $totalMilestones) * 100, 1)
            : 0;

        // Statistics
        $stats = [
            'total' => $totalMilestones,
            'achieved' => $achievedMilestones,
            'in_progress' => $milestones->where('status', 'in_progress')->count(),
            'blocked' => $milestones->where('status', 'blocked')->count(),
            'pending' => $milestones->where('status', 'pending')->count(),
            'overdue' => $milestones->filter->isOverdue()->count(),
            'overall_progress' => $overallProgress,
        ];

        return view('milestones.index', compact('milestones', 'stats'));
    }

    /**
     * Show the form for creating a new milestone.
     */
    public function create()
    {
        $users = \App\Models\User::orderBy('first_name')->get();
        return view('milestones.create', compact('users'));
    }

    /**
     * Store a newly created milestone in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_type' => 'nullable|string|max:255',
            'project_id' => 'nullable|integer',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sequence_order' => 'required|integer|min:0',
            'status' => 'required|in:pending,in_progress,achieved,blocked,skipped',
            'progress_percentage' => 'required|numeric|min:0|max:100',
            'target_date' => 'nullable|date',
            'assigned_to' => 'nullable|exists:users,id',
            'notes' => 'nullable|string',
            'blockers' => 'nullable|string',
        ]);

        $milestone = Milestone::create($validated);

        return redirect()->route('milestones.index')
            ->with('success', 'Milestone created successfully');
    }

    /**
     * Display the specified milestone.
     */
    public function show(Milestone $milestone)
    {
        $milestone->load('assignedUser');

        return view('milestones.show', compact('milestone'));
    }

    /**
     * Show the form for editing the specified milestone.
     */
    public function edit(Milestone $milestone)
    {
        $users = \App\Models\User::orderBy('first_name')->get();
        return view('milestones.edit', compact('milestone', 'users'));
    }

    /**
     * Update the specified milestone in storage.
     */
    public function update(Request $request, Milestone $milestone)
    {
        $validated = $request->validate([
            'project_type' => 'nullable|string|max:255',
            'project_id' => 'nullable|integer',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sequence_order' => 'required|integer|min:0',
            'status' => 'required|in:pending,in_progress,achieved,blocked,skipped',
            'progress_percentage' => 'required|numeric|min:0|max:100',
            'target_date' => 'nullable|date',
            'assigned_to' => 'nullable|exists:users,id',
            'notes' => 'nullable|string',
            'blockers' => 'nullable|string',
        ]);

        $milestone->update($validated);

        return redirect()->route('milestones.index')
            ->with('success', 'Milestone updated successfully');
    }

    /**
     * Update milestone progress (AJAX endpoint).
     */
    public function updateProgress(Request $request, Milestone $milestone)
    {
        $request->validate([
            'progress' => 'required|numeric|min:0|max:100',
        ]);

        try {
            $milestone->updateProgress($request->progress);

            Log::info('Milestone progress updated', [
                'milestone_id' => $milestone->id,
                'progress' => $request->progress,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Progress updated successfully',
                'milestone' => [
                    'id' => $milestone->id,
                    'progress_percentage' => $milestone->progress_percentage,
                    'status' => $milestone->status,
                    'status_info' => $milestone->getStatusInfo(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update milestone progress', [
                'milestone_id' => $milestone->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update progress',
            ], 500);
        }
    }

    /**
     * Update milestone status (AJAX endpoint).
     */
    public function updateStatus(Request $request, Milestone $milestone)
    {
        $request->validate([
            'status' => 'required|in:pending,in_progress,achieved,blocked,skipped',
            'blockers' => 'nullable|string',
        ]);

        try {
            $data = ['status' => $request->status];

            // Handle status-specific logic
            switch ($request->status) {
                case 'achieved':
                    $milestone->markAsAchieved();
                    break;
                case 'blocked':
                    $milestone->markAsBlocked($request->blockers);
                    break;
                case 'in_progress':
                    $milestone->markAsInProgress();
                    break;
                default:
                    $milestone->update($data);
            }

            Log::info('Milestone status updated', [
                'milestone_id' => $milestone->id,
                'status' => $request->status,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully',
                'milestone' => [
                    'id' => $milestone->id,
                    'status' => $milestone->status,
                    'status_info' => $milestone->getStatusInfo(),
                    'progress_percentage' => $milestone->progress_percentage,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update milestone status', [
                'milestone_id' => $milestone->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update status',
            ], 500);
        }
    }

    /**
     * Remove the specified milestone from storage.
     */
    public function destroy(Milestone $milestone)
    {
        $milestone->delete();

        return redirect()->route('milestones.index')
            ->with('success', 'Milestone deleted successfully');
    }
}
