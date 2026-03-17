@extends('layouts.app')

@section('title', 'Reconciliation History')

@section('content')
<div class="py-6" x-data="{ activeFilter: 'all' }">
    <!-- Header -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-neutral-900">
                    Reconciliation History
                </h1>
                <p class="mt-1 text-sm text-neutral-600">
                    Monitor asset reconciliation runs and resolve discrepancies
                </p>
            </div>
            <button 
                onclick="document.getElementById('triggerModal').classList.remove('hidden')"
                class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 focus:bg-primary-700 active:bg-primary-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition ease-in-out duration-150"
                aria-label="Trigger manual reconciliation"
            >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Run Reconciliation
            </button>
        </div>
    </div>

    <!-- Metrics Cards -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Total Runs (Last 30 Days) -->
            <div @click="activeFilter = 'all'" 
                 :class="{ 'bg-primary-50': activeFilter === 'all' }"
                 class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-primary-500 cursor-pointer hover:bg-primary-50 transition-colors">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-neutral-600">Total Runs</p>
                        <p class="mt-2 text-3xl font-bold text-neutral-800">
                            {{ $metrics['total_runs'] }}
                        </p>
                        <p class="mt-1 text-xs text-neutral-500">Last 30 days</p>
                    </div>
                    <div class="p-3 rounded-full bg-primary-100">
                        <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Successful Runs -->
            <div @click="activeFilter = 'success'" 
                 :class="{ 'bg-success-50': activeFilter === 'success' }"
                 class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-success-500 cursor-pointer hover:bg-success-50 transition-colors">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-neutral-600">Successful</p>
                        <p class="mt-2 text-3xl font-bold text-success-600">
                            {{ $metrics['successful_runs'] }}
                        </p>
                        <p class="mt-1 text-xs text-neutral-500">
                            {{ $metrics['total_runs'] > 0 ? round(($metrics['successful_runs'] / $metrics['total_runs']) * 100) : 0 }}% success rate
                        </p>
                    </div>
                    <div class="p-3 rounded-full bg-success-100">
                        <svg class="w-6 h-6 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Critical Issues -->
            <div @click="activeFilter = 'critical'" 
                 :class="{ 'bg-danger-50': activeFilter === 'critical' }"
                 class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-danger-500 cursor-pointer hover:bg-danger-50 transition-colors">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-neutral-600">Critical Issues</p>
                        <p class="mt-2 text-3xl font-bold text-danger-600">
                            {{ $metrics['critical_issues'] }}
                        </p>
                        <p class="mt-1 text-xs text-neutral-500">Require attention</p>
                    </div>
                    <div class="p-3 rounded-full bg-danger-100">
                        <svg class="w-6 h-6 text-danger-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Pending Reviews -->
            <div @click="activeFilter = 'pending'" 
                 :class="{ 'bg-warning-50': activeFilter === 'pending' }"
                 class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-warning-500 cursor-pointer hover:bg-warning-50 transition-colors">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-neutral-600">Pending Reviews</p>
                        <p class="mt-2 text-3xl font-bold text-warning-600">
                            {{ $pendingReviews->count() }}
                        </p>
                        <p class="mt-1 text-xs text-neutral-500">Need manual review</p>
                    </div>
                    <div class="p-3 rounded-full bg-warning-100">
                        <svg class="w-6 h-6 text-warning-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Reconciliation Runs Table -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-neutral-200">
            @if($runs->isEmpty())
                <!-- Empty State -->
                <div class="text-center py-12" role="status" aria-live="polite">
                    <svg class="mx-auto h-12 w-12 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-neutral-900">No reconciliation runs yet</h3>
                    <p class="mt-2 text-sm text-neutral-600">
                        Start your first reconciliation to check for asset discrepancies
                    </p>
                    <button 
                        onclick="document.getElementById('triggerModal').classList.remove('hidden')"
                        class="mt-4 inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-md text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                        aria-label="Trigger first reconciliation"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Run Reconciliation
                    </button>
                </div>
            @else
                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200" role="table">
                        <thead class="bg-neutral-50">
                            <tr role="row">
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-700 uppercase tracking-wider">
                                    Run Details
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-700 uppercase tracking-wider">
                                    Status
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-700 uppercase tracking-wider">
                                    Items Checked
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-700 uppercase tracking-wider">
                                    Discrepancies
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-700 uppercase tracking-wider">
                                    Success Rate
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-700 uppercase tracking-wider">
                                    Duration
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-neutral-700 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($runs as $run)
                                @php
                                    $statusInfo = $run->getStatusInfo();
                                    $showRun = true;
                                    if ($activeFilter === 'success' && (!$run->isSuccessful())) $showRun = false;
                                    if ($activeFilter === 'critical' && $run->critical_issues === 0) $showRun = false;
                                    if ($activeFilter === 'pending' && $run->discrepancies->whereIn('resolution_status', ['pending', 'manual_review'])->isEmpty()) $showRun = false;
                                @endphp
                                <tr role="row"
                                    x-show="activeFilter === 'all' || 
                                           (activeFilter === 'success' && {{ $run->isSuccessful() ? 'true' : 'false' }}) ||
                                           (activeFilter === 'critical' && {{ $run->critical_issues > 0 ? 'true' : 'false' }}) ||
                                           (activeFilter === 'pending' && {{ $run->discrepancies->whereIn('resolution_status', ['pending', 'manual_review'])->isNotEmpty() ? 'true' : 'false' }})"
                                    x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0 transform scale-95"
                                    x-transition:enter-end="opacity-100 transform scale-100"
                                    class="hover:bg-neutral-50">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10 flex items-center justify-center rounded-lg bg-{{ $statusInfo['color'] }}-100">
                                                <svg class="h-6 w-6 text-{{ $statusInfo['color'] }}-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                                                </svg>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-neutral-900">
                                                    {{ ucfirst($run->run_type) }} Reconciliation
                                                </div>
                                                <div class="text-sm text-neutral-500">
                                                    {{ $run->started_at->format('M d, Y H:i') }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $statusInfo['color'] }}-100 text-{{ $statusInfo['color'] }}-800">
                                            {{ ucfirst($statusInfo['status']) }}
                                        </span>
                                        @if($run->critical_issues > 0)
                                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-danger-100 text-danger-800">
                                                {{ $run->critical_issues }} critical
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-900">
                                        {{ number_format($run->items_checked) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-neutral-900">
                                            {{ $run->total_discrepancies }}
                                        </div>
                                        @if($run->auto_corrected > 0)
                                            <div class="text-xs text-success-600">
                                                {{ $run->auto_corrected }} auto-corrected
                                            </div>
                                        @endif
                                        @if($run->manual_review_required > 0)
                                            <div class="text-xs text-warning-600">
                                                {{ $run->manual_review_required }} need review
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($run->success_rate !== null)
                                            <div class="flex items-center">
                                                <div class="text-sm font-medium text-neutral-900">
                                                    {{ number_format($run->success_rate, 1) }}%
                                                </div>
                                            </div>
                                            <div class="w-full bg-neutral-200 rounded-full h-1.5 mt-1">
                                                @php
                                                    $barColor = $run->success_rate >= 95 ? 'bg-success-500' : 
                                                               ($run->success_rate >= 85 ? 'bg-warning-500' : 'bg-danger-500');
                                                @endphp
                                                <div class="{{ $barColor }} h-1.5 rounded-full" 
                                                     style="width: {{ $run->success_rate }}%;">
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-neutral-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-600">
                                        {{ $run->duration ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="{{ route('reconciliation.show', $run) }}" 
                                           class="inline-flex items-center px-3 py-1 text-primary-600 hover:bg-primary-50 rounded-lg text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                                           aria-label="View details for run on {{ $run->started_at->format('M d, Y') }}">
                                            View Details
                                            <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                            </svg>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Trigger Manual Reconciliation Modal -->
<div id="triggerModal" class="hidden fixed inset-0 bg-neutral-900 bg-opacity-50 z-50 flex items-center justify-center" role="dialog" aria-modal="true" aria-labelledby="triggerModalTitle">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
        <form action="{{ route('reconciliation.trigger') }}" method="POST" id="triggerForm">
            @csrf
            <div class="px-6 py-4 border-b border-neutral-200">
                <h3 id="triggerModalTitle" class="text-lg font-semibold text-neutral-900">
                    Run Reconciliation
                </h3>
            </div>
            <div class="px-6 py-4">
                <p class="text-sm text-neutral-600 mb-4">
                    This will trigger a manual reconciliation run to check for discrepancies between your systems and external data sources.
                </p>
                <div class="bg-primary-50 border border-primary-200 rounded-lg p-4">
                    <div class="flex">
                        <svg class="h-5 w-5 text-primary-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div class="ml-3">
                            <p class="text-sm text-primary-700">
                                This process may take several minutes depending on the number of assets and data sources.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-neutral-200 flex justify-end space-x-3">
                <button 
                    type="button"
                    onclick="document.getElementById('triggerModal').classList.add('hidden')"
                    class="px-4 py-2 bg-white hover:bg-neutral-50 text-neutral-700 border border-neutral-300 rounded-md text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                >
                    Cancel
                </button>
                <button 
                    type="submit"
                    class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-md text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                    id="submitTrigger"
                >
                    Start Reconciliation
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Form submission loading state
document.getElementById('triggerForm').addEventListener('submit', function() {
    const button = document.getElementById('submitTrigger');
    button.disabled = true;
    button.innerHTML = '<svg class="animate-spin h-4 w-4 mr-2 inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Starting...';
});
</script>
@endsection
